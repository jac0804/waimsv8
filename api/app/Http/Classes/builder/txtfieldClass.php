<?php

namespace App\Http\Classes\builder;

use App\Http\Classes\coreFunctions;
use App\Http\Classes\othersClass;
use DB;
use Exception;
use Throwable;

class txtfieldClass
{
    // class
    // 1. sbccsreadonly - always readonly
    // 2. sbccsenablealways - always enabled

    private $style;
    private $fields = [];
    private $coreFunctions;
    private $othersClass;

    public function __construct()
    {
        $this->othersClass = new othersClass;
    }
    public function txtarray()
    {
        $this->style = 'font-size:90%;';
        $this->fields = array(
            'docno' => array(
                'name' => 'docno',
                'type' => 'lookup',
                'label' => 'Docno#',
                'class' => 'csdocno sbccsenablealways',
                'lookupclass' => 'docno',
                'action' => 'lookupdocno',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'addedparams' => []
            ),
            'client' => array(
                'name' => 'client',
                'type' => 'lookup',
                'label' => 'Customer',
                'class' => 'csclient sbccsreadonly',
                'lookupclass' => 'supplier',
                'action' => 'lookupclient',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),
            'tenants' => array(
                'name' => 'tenants',
                'type' => 'lookup',
                'label' => 'Tenants',
                'labeldata' => 'client~clientname',
                'class' => 'csclient sbccsreadonly',
                'lookupclass' => 'supplier',
                'action' => 'lookupclient',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),
            'bclient' => array(
                'name' => 'bclient',
                'type' => 'lookup',
                'label' => 'Customer',
                'class' => 'csclient sbccsreadonly',
                'lookupclass' => 'supplier',
                'action' => 'lookupclient',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),
            'clientname' => array(
                'name' => 'clientname',
                'type' => 'input',
                'label' => 'Name',
                'class' => 'csclientname',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'reseller' => array(
                'name' => 'reseller',
                'type' => 'input',
                'label' => 'reseller',
                'class' => 'csreseller',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'acctname' => array(
                'name' => 'acctname',
                'type' => 'input',
                'label' => 'Name',
                'class' => 'csacctname',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'systype' => array(
                'name' => 'systype',
                'type' => 'lookup',
                'label' => 'System Type',
                'class' => 'cssystype',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'tasktype' => array(
                'name' => 'tasktype',
                'type' => 'lookup',
                'label' => 'Task Type',
                'class' => 'cstasktype',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'bclientname' => array(
                'name' => 'bclientname',
                'type' => 'input',
                'label' => 'Name',
                'class' => 'csclientname',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'agparent' => array(
                'name' => 'agparent',
                'type' => 'lookup',
                'label' => 'Manager',
                'class' => 'csagparent sbccsreadonly',
                'lookupclass' => 'agent',
                'action' => 'lookupclient',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'whname' => array(
                'name' => 'whname',
                'type' => 'input',
                'label' => 'Warehouse Name',
                'class' => 'cswhname sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'reportedbyname' => array(
                'name' => 'reportedbyname',
                'type' => 'lookup',
                'label' => 'Reported by',
                'class' => 'csreportedbyname sbccsreadonly',
                'lookupclass' => 'employee',
                'action' => 'lookupclient',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'whreceivername' => array(
                'name' => 'whreceivername',
                'type' => 'lookup',
                'label' => 'Warehouse Receiver',
                'class' => 'cswhreceivername sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'action' => 'lookuppreparedby',
                'lookupclass' => 'powh_receiver',
            ),
            'customername' => array(
                'name' => 'customername',
                'type' => 'input',
                'label' => 'Customer Name',
                'class' => 'cscustomername',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 150
            ),
            'deliverytype' => array(
                'name' => 'deliverytype',
                'type' => 'lookup',
                'label' => 'Delivery Type',
                'class' => 'csdeliverytype sbccsreadonly',
                'lookupclass' => 'Deliverytype',
                'action' => 'lookupdeliverytype',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'statname' => array(
                'name' => 'statname',
                'type' => 'lookup',
                'label' => 'Priority Level',
                'class' => 'csdstatname sbccsreadonly',
                'lookupclass' => 'statname',
                'action' => 'lookupstatname',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),
            'subcategorystat' => array(
                'name' => 'subcategorystat',
                'type' => 'lookup',
                'label' => 'Sub Category Status',
                'class' => 'csdstatname sbccsreadonly',
                'lookupclass' => 'lookupsubcategorystat',
                'action' => 'lookupstatname',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),
            'requestorstat' => array(
                'name' => 'requestorstat',
                'type' => 'lookup',
                'label' => 'Requestor Status',
                'class' => 'csdstatname sbccsreadonly',
                'lookupclass' => 'lookuprequestorstat',
                'action' => 'lookupstatname',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),

            'prref' => array(
                'name' => 'prref',
                'type' => 'input',
                'label' => 'PR Reference',
                'class' => 'csprref',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),

            'partreqtype' => array(
                'name' => 'partreqtype',
                'type' => 'lookup',
                'label' => 'Part Request Type',
                'class' => 'cspartreqtype sbccsreadonly',
                'lookupclass' => 'partreqtype',
                'action' => 'lookuppartreqtype',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),
            'truck' => array(
                'name' => 'truck',
                'type' => 'lookup',
                'label' => 'Truck',
                'class' => 'cstruckname sbccsreadonly',
                'lookupclass' => 'truck',
                'action' => 'lookuptruck',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),
            'address' => array(
                'name' => 'address',
                'type' => 'input',
                'label' => 'Address',
                'class' => 'csaddress',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'checkinfo' => array(
                'name' => 'checkinfo',
                'type' => 'input',
                'label' => 'Checkinfo',
                'class' => 'cscheckinfo',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'addr' => array(
                'name' => 'addr',
                'type' => 'ctextarea',
                'label' => 'Address',
                'class' => 'csaddr',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 200
            ),
            'tin' => array(
                'name' => 'tin',
                'type' => 'input',
                'label' => 'TIN',
                'class' => 'cstin',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'businesstype' => array(
                'name' => 'businesstype',
                'type' => 'input',
                'label' => 'Business Type',
                'class' => 'cstin sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 250
            ),
            'regnum' => array(
                'name' => 'regnum',
                'type' => 'input',
                'label' => 'Registration number',
                'class' => 'csregnum',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'mincome' => array(
                'name' => 'mincome',
                'type' => 'input',
                'label' => 'Monthly Income (Applicant)',
                'class' => 'csmincome',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'purchaser' => array(
                'name' => 'purchaser',
                'type' => 'input',
                'label' => 'Purchaser',
                'class' => 'cspurchaser',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'mexp' => array(
                'name' => 'mexp',
                'type' => 'input',
                'label' => 'Monthly Expenses',
                'class' => 'csmexp',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'registername' => array(
                'name' => 'registername',
                'type' => 'input',
                'label' => 'Registered Name',
                'class' => 'csregistername',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'shipto' => array(
                'name' => 'shipto',
                'type' => 'cinput',
                'label' => 'Ship To',
                'class' => 'csshipto',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'revisionref' => array(
                'name' => 'revisionref',
                'type' => 'cinput',
                'label' => 'Revision Ref',
                'class' => 'csrevisionref',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 200
            ),
            'ms_freight' => array(
                'name' => 'ms_freight',
                'type' => 'input',
                'label' => 'Delivery Charge',
                'class' => 'csms_freight',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'mlcp_freight' => array(
                'name' => 'mlcp_freight',
                'type' => 'input',
                'label' => 'Charges Description',
                'class' => 'csms_freight',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'ship' => array(
                'name' => 'ship',
                'type' => 'input',
                'label' => 'Ship To',
                'class' => 'csship',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'lblgrossprofit' => array(
                'name' => 'lblgrossprofit',
                'type' => 'label',
                'label' => 'Total Gross Profit',
                'class' => '',
                'style' => 'font-weight:bold;font-size:25px;'
            ),
            'lblcostuom' => array(
                'name' => 'lblcostuom',
                'type' => 'label',
                'label' => 'COST/UOM',
                'class' => '',
                'style' => 'font-weight:bold;font-size:30px;'
            ),
            'lbltotalkg' => array(
                'name' => 'lbltotalkg',
                'type' => 'label',
                'label' => 'Total Kg',
                'class' => '',
                'style' => 'font-weight:bold;font-size:30px;'
            ),
            'lblshipping' => array(
                'name' => 'lblshipping',
                'type' => 'label',
                'label' => 'DEFAULT SHIPPING ADDRESS',
                'class' => '',
                'style' => 'font-weight:bold'
            ),
            'lblbilling' => array(
                'name' => 'lblbilling',
                'type' => 'label',
                'label' => 'DEFAULT BILLING ADDRESS',
                'class' => '',
                'style' => 'font-weight:bold'
            ),
            'shipid' => array(
                'name' => 'shipid',
                'type' => 'lookup',
                'label' => '',
                'class' => 'csshipid sbccsreadonly',
                'lookupclass' => 'wshipping',
                'action' => 'lookupwshipping',
                'readonly' => true,
                'style' => 'width:100%;max-width:35%;',
                'required' => false
            ),
            'shipping' => array(
                'name' => 'shipping',
                'type' => 'wysiwyg',
                'label' => 'Shipping Address',
                'class' => 'csshipping sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'instructions' => array(
                'name' => 'instructions',
                'type' => 'wysiwyg',
                'label' => 'Instructions',
                'class' => 'csinstructions sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'billid' => array(
                'name' => 'billid',
                'type' => 'lookup',
                'label' => '',
                'class' => 'csbillid sbccsreadonly',
                'lookupclass' => 'wbilling',
                'action' => 'lookupwbilling',
                'readonly' => true,
                'style' => 'width:100%;max-width:35%;',
                'required' => false
            ),
            'billing' => array(
                'name' => 'billing',
                'type' => 'wysiwyg',
                'label' => 'Billing Address',
                'class' => 'csbilling sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'dateid' => array(
                'name' => 'dateid',
                'type' => 'date',
                'label' => 'Date',
                'class' => 'csdateid',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'dateid2' => array(
                'name' => 'dateid2',
                'type' => 'date',
                'label' => 'Date',
                'class' => 'csdateid2',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'dateid3' => array(
                'name' => 'dateid3',
                'type' => 'date',
                'label' => 'Date',
                'class' => 'csdateid3',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'dateid4' => array(
                'name' => 'dateid4',
                'type' => 'date',
                'label' => 'Date',
                'class' => 'csdateid4',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'createdate' => array(
                'name' => 'createdate',
                'type' => 'date',
                'label' => 'Create Date',
                'class' => 'cscreatedate sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'postdate' => array(
                'name' => 'postdate',
                'type' => 'date',
                'label' => 'Post date',
                'class' => 'csdateid',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'returndate' => array(
                'name' => 'returndate',
                'type' => 'datetime',
                'label' => 'Return Date',
                'class' => 'csreturndate',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'voiddate' => array(
                'name' => 'voiddate',
                'type' => 'datetime',
                'label' => 'Void Date',
                'class' => 'csvoiddate',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'refunddate' => array(
                'name' => 'refunddate',
                'type' => 'date',
                'label' => 'Refund Date',
                'class' => 'csrefunddate',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'ottimein' => array(
                'name' => 'ottimein',
                'type' => 'time',
                'label' => 'OT In',
                'class' => 'csottimein sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'ottimeout' => array(
                'name' => 'ottimeout',
                'type' => 'time',
                'label' => 'OT Out',
                'class' => 'csottimeout',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'htime' => array(
                'name' => 'htime',
                'type' => 'time',
                'label' => 'Time',
                'class' => 'cshtime',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'othrs' => array(
                'name' => 'othrs',
                'field' => 'othrs',
                'type' => 'input',
                'label' => 'OT Hours',
                'class' => 'csothrs',
                'align' => 'text-left',
                'style' => $this->style,
                'readonly' => true
            ),
            'apothrs' => array(
                'name' => 'apothrs',
                'field' => 'apothrs',
                'type' => 'input',
                'label' => 'Aprroved OT Hours',
                'class' => 'csapothrs sbccsreadonly',
                'align' => 'text-left',
                'style' => $this->style,
                'readonly' => true
            ),
            'othrsextra' => array(
                'name' => 'othrsextra',
                'field' => 'othrsextra',
                'type' => 'input',
                'label' => 'OT > 8 Hours',
                'class' => 'csothrsextra',
                'align' => 'text-left',
                'style' => $this->style,
                'readonly' => true
            ),
            'apothrsextra' => array(
                'name' => 'apothrsextra',
                'field' => 'apothrsextra',
                'type' => 'input',
                'label' => 'Approved OT > 8 Hours',
                'class' => 'csapothrsextra sbccsreadonly',
                'align' => 'text-left',
                'style' => $this->style,
                'readonly' => true
            ),
            'ndiffot' => array(
                'name' => 'ndiffot',
                'field' => 'ndiffot',
                'type' => 'input',
                'label' => 'N-Diff OT Hrs',
                'align' => 'text-left',
                'style' => $this->style,
                'readonly' => true
            ),
            'ndiffothrs' => array(
                'name' => 'ndiffothrs',
                'field' => 'ndiffothrs',
                'type' => 'input',
                'label' => 'N-DIFF OT HOURS: ',
                'align' => 'text-left',
                'style' => $this->style,
                'readonly' => true
            ),
            'ndiffhrs' => array(
                'name' => 'ndiffhrs',
                'field' => 'ndiffhrs',
                'type' => 'input',
                'label' => 'N-DIFF HOURS',
                'align' => 'text-left',
                'style' => $this->style,
                'readonly' => true
            ),
            'apndiffhrs' => array(
                'name' => 'apndiffhrs',
                'field' => 'apndiffhrs',
                'type' => 'input',
                'label' => 'Approved N-Diff Hours',
                'align' => 'text-left',
                'style' => $this->style,
                'readonly' => true
            ),
            'apndiffothrs' => array(
                'name' => 'apndiffothrs',
                'field' => 'apndiffothrs',
                'type' => 'input',
                'label' => 'Approved N-Diff OT Hours',
                'align' => 'text-left',
                'style' => $this->style,
                'readonly' => true
            ),
            'instruct' => array(
                'name' => 'instruct',
                'field' => 'instruction',
                'type' => 'input',
                'label' => 'Notes',
                'align' => 'text-left',
                'style' => $this->style,
                'readonly' => true
            ),
            'entryot' => array(
                'name' => 'entryot',
                'field' => 'entryot',
                'type' => 'input',
                'label' => 'Apply OT Hours',
                'align' => 'text-left',
                'style' => $this->style,
                'readonly' => false
            ),
            'entryndiffot' => array(
                'name' => 'entryndiffot',
                'field' => 'entryndiffot',
                'type' => 'input',
                'label' => 'Apply N-Diff OT Hrs',
                'align' => 'text-left',
                'style' => $this->style,
                'readonly' => false
            ),
            'disposaldate' => array(
                'name' => 'disposaldate',
                'type' => 'date',
                'label' => 'Disposal Date',
                'class' => 'csdisposaldate',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'disposaldays' => array(
                'name' => 'disposaldays',
                'type' => 'input',
                'label' => 'Disposal Days',
                'class' => 'disposaldays sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'warrantydays' => array(
                'name' => 'warrantydays',
                'type' => 'input',
                'label' => 'Days',
                'class' => 'cswarrantydays sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'leasedays' => array(
                'name' => 'leasedays',
                'type' => 'input',
                'label' => 'Days',
                'class' => 'csleasedays sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'startinsured' => array(
                'name' => 'startinsured',
                'type' => 'date',
                'label' => 'Start Insurance',
                'class' => 'csstartinsured',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'endinsured' => array(
                'name' => 'endinsured',
                'type' => 'date',
                'label' => 'End Insurance',
                'class' => 'csendinsured',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'dateacquired' => array(
                'name' => 'dateacquired',
                'type' => 'date',
                'label' => 'Acquisition Date',
                'class' => 'csdateacquired',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'warrantyend' => array(
                'name' => 'warrantyend',
                'type' => 'date',
                'label' => 'Warranty Expiry',
                'class' => 'cswarrantyend',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'dateacquireddays' => array(
                'name' => 'dateacquireddays',
                'type' => 'cinput',
                'label' => 'yr/s',
                'class' => 'csdateacquireddays sbccsreadonly',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'datereq' => array(
                'name' => 'datereq',
                'type' => 'date',
                'label' => 'Date Requested',
                'class' => 'csdatereq',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'dateneed' => array(
                'name' => 'dateneed',
                'type' => 'date',
                'label' => 'Date Needed',
                'class' => 'csdateneed',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'releasedate' => array(
                'name' => 'releasedate',
                'type' => 'date',
                'label' => 'Release Date',
                'class' => 'csreleasedate',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'qtdateid' => array(
                'name' => 'qtdateid',
                'type' => 'date',
                'label' => 'Quotation Date',
                'class' => 'csdateid',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'due' => array(
                'name' => 'due',
                'type' => 'date',
                'label' => 'Due',
                'class' => 'csdue',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'scheddate' => array(
                'name' => 'scheddate',
                'type' => 'date',
                'label' => 'Schedule Date',
                'class' => 'csscheddate',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'wh' => array(
                'name' => 'wh',
                'type' => 'lookup',
                'label' => 'Warehouse',
                'class' => 'cswh sbccsreadonly',
                'lookupclass' => 'wh',
                'action' => 'lookupclient',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'wh2' => array(
                'name' => 'wh2',
                'type' => 'lookup',
                'label' => 'Destination Warehouse',
                'class' => 'cswarehouse2 sbccsreadonly',
                'lookupclass' => 'whs2',
                'action' => 'lookupclient',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'whinv' => array(
                'name' => 'client',
                'type' => 'lookup',
                'label' => 'Warehouse',
                'class' => 'cswh sbccsreadonly',
                'lookupclass' => 'whinv',
                'action' => 'lookupclient',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'dwhname' => array(
                'name' => 'dwhname',
                'type' => 'lookup',
                'label' => 'Warehouse Name',
                'labeldata' => 'wh~whname',
                'class' => 'cswhname sbccsreadonly',
                'lookupclass' => 'wh',
                'action' => 'lookupclient',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'dwhname2' => array(
                'name' => 'dwhname2',
                'type' => 'lookup',
                'label' => 'Source Warehouse',
                'labeldata' => 'whid2~wh2name',
                'class' => 'cswarehouse2 sbccsreadonly',
                'lookupclass' => 'whs2',
                'action' => 'lookupclient',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'dwhfrom' => array(
                'name' => 'dwhfrom',
                'type' => 'lookup',
                'label' => 'From:',
                'labeldata' => 'whfrom~whfromname',
                'class' => 'csdwhfrom sbccsreadonly',
                'lookupclass' => 'whfrom',
                'action' => 'lookupwhfromto',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'dwhto' => array(
                'name' => 'dwhto',
                'type' => 'lookup',
                'label' => 'To:',
                'labeldata' => 'whto~whtoname',
                'class' => 'csdwhto sbccsreadonly',
                'lookupclass' => 'whto',
                'action' => 'lookupwhfromto',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'dropoffwarehouse' => array(
                'name' => 'dropoffwarehouse',
                'type' => 'lookup',
                'label' => 'Drop Off Warehouse',
                'labeldata' => 'dowh~dowhname',
                'class' => 'cswhname sbccsreadonly',
                'lookupclass' => 'dowh',
                'action' => 'lookupclient',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'dropoffwhemp' => array(
                'name' => 'dropoffwhemp',
                'type' => 'lookup',
                'label' => 'Drop Off Warehouse',
                'class' => 'cswhname sbccsreadonly',
                'lookupclass' => 'dowh',
                'action' => 'lookupclient',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'procid' => array(
                'name' => 'procid',
                'type' => 'lookup',
                'label' => 'Procurement',
                'class' => 'csprocid sbccsreadonly',
                'lookupclass' => 'procid',
                'action' => 'lookup_procid',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'dwhref' => array(
                'name' => 'dwhref',
                'type' => 'lookup',
                'label' => 'Laying House WH Reference',
                'labeldata' => 'whref~whnameref',
                'class' => 'cswhname sbccsreadonly',
                'lookupclass' => 'whref',
                'action' => 'lookupclient',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'ddeptname' => array(
                'name' => 'ddeptname',
                'type' => 'lookup',
                'label' => 'For Department',
                'labeldata' => 'dept~deptname',
                'class' => 'csdept sbccsreadonly',
                'lookupclass' => 'lookupddeptname',
                'action' => 'lookupclient',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'deptrep' => array(
                'name' => 'deptrep',
                'type' => 'lookup',
                'label' => 'For Department',
                'labeldata' => 'deptid~deptname',
                'class' => 'csdept sbccsreadonly',
                'lookupclass' => 'lookupddeptname',
                'action' => 'lookupclient',
                'readonly' => true,
                'style' => $this->style,
                'required' => false

            ),
            'dept' => array(
                'name' => 'dept',
                'type' => 'lookup',
                'label' => 'Department Code',
                'class' => 'csdept sbccsreadonly',
                'lookupclass' => 'lookupdept',
                'action' => 'lookupclient',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'assessref' => array(
                'name' => 'assessref',
                'type' => 'lookup',
                'label' => 'Assesement',
                'class' => 'csassess',
                'lookupclass' => 'lookupassess',
                'action' => 'lookupassess',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'addedparams' => ['client', 'periodid']
            ),
            'approvalreason' => array(
                'name' => 'approvalreason',
                'type' => 'cinput',
                'label' => 'Approval Reason',
                'class' => 'csapprovalreason',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'yourref' => array(
                'name' => 'yourref',
                'type' => 'cinput',
                'label' => 'Yourref',
                'class' => 'csyourref',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'mileage' => array(
                'name' => 'mileage',
                'type' => 'cinput',
                'label' => 'Mileage/SMR',
                'class' => 'csmileage',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'nodays' => array(
                'name' => 'nodays',
                'type' => 'cinput',
                'label' => 'No of Days',
                'class' => 'csnodays',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'qtype' => array(
                'name' => 'qtype',
                'type' => 'lookup',
                'label' => 'Type',
                'class' => 'csqtype sbccsreadonly',
                'lookupclass' => 'lookupqtype',
                'action' => 'lookupqtype',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'sadesc' => array(
                'name' => 'sadesc',
                'type' => 'lookup',
                'label' => 'SA No.',
                'class' => 'cssadesc sbccsreadonly',
                'lookupclass' => 'lookupsadesc',
                'action' => 'lookupsadesc',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'addedparams' => ['client']
            ),
            'svsdesc' => array(
                'name' => 'svsdesc',
                'type' => 'lookup',
                'label' => 'SVS No.',
                'class' => 'cssvsdesc sbccsreadonly',
                'lookupclass' => 'lookupsadesc',
                'action' => 'lookupsadesc',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'addedparams' => ['client']
            ),
            'podesc' => array(
                'name' => 'podesc',
                'type' => 'lookup',
                'label' => 'PO No.',
                'class' => 'cspodesc sbccsreadonly',
                'lookupclass' => 'lookupsadesc',
                'action' => 'lookupsadesc',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'addedparams' => ['client']
            ),

            'potype' => array(
                'name' => 'potype',
                'type' => 'lookup',
                'label' => 'PO Type',
                'class' => 'cspotype sbccsreadonly',
                'action' => 'lookuppotype',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),

            'crref' => array(
                'name' => 'crref',
                'type' => 'cinput',
                'label' => 'OR #',
                'class' => 'cscrref',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'station' => array(
                'name' => 'station',
                'type' => 'cinput',
                'label' => 'POS Terminal',
                'class' => 'csstation',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'ref' => array(
                'name' => 'ref',
                'type' => 'cinput',
                'label' => 'Reference',
                'class' => 'csref',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'si2' => array(
                'name' => 'si2',
                'type' => 'cinput',
                'label' => 'SI #',
                'class' => 'cssi2',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 45
            ),
            'fdecimal' => array(
                'name' => 'fdecimal',
                'type' => 'input',
                'label' => 'Decimal',
                'class' => 'csdecimal',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
            ),
            'waybill' => array(
                'name' => 'waybill',
                'type' => 'cinput',
                'label' => 'Waybill',
                'class' => 'cswaybill',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'waybillamt' => array(
                'name' => 'waybillamt',
                'type' => 'cinput',
                'label' => 'Declared Amount',
                'class' => 'cswaybillamt',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'waybill' => array(
                'name' => 'waybill',
                'type' => 'lookup',
                'label' => 'Waybill',
                'class' => 'cswaybill',
                'lookupclass' => 'lookupwaybill',
                'action' => 'lookupwaybill',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'ourref' => array(
                'name' => 'ourref',
                'type' => 'cinput',
                'label' => 'Ourref',
                'class' => 'csourref',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'qty' => array(
                'name' => 'qty',
                'type' => 'input',
                'label' => 'Qty',
                'class' => 'csqty',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'ext' => array(
                'name' => 'ext',
                'type' => 'input',
                'label' => 'Total',
                'class' => 'csext',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'totalestweight' => array(
                'name' => 'totalestweight',
                'type' => 'input',
                'label' => 'Total Est Weight',
                'class' => 'cstotalestweight',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'totalactualweight' => array(
                'name' => 'totalactualweight',
                'type' => 'input',
                'label' => 'Total Actual Weight',
                'class' => 'cstotalactualweight',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'grossprofit' => array(
                'name' => 'grossprofit',
                'type' => 'input',
                'label' => 'Gross Profit',
                'class' => 'csext',
                'readonly' => true,
                'style' => 'font-weight:bold;font-size:25px;',
                'required' => false
            ),
            'costuom' => array(
                'name' => 'costuom',
                'type' => 'input',
                'label' => 'COST/UOM',
                'class' => 'csext',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'batchsize' => array(
                'name' => 'batchsize',
                'type' => 'input',
                'label' => 'Batch size',
                'class' => 'csqty',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'yield' => array(
                'name' => 'yield',
                'type' => 'input',
                'label' => 'Yield',
                'class' => 'csqty',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'lotno' => array(
                'name' => 'lotno',
                'type' => 'input',
                'label' => 'Lot No',
                'class' => 'cslotno',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'tqty' => array(
                'name' => 'tqty',
                'type' => 'input',
                'label' => 'Actual Weight',
                'class' => 'csqty',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'db' => array(
                'name' => 'db',
                'type' => 'input',
                'label' => 'Debit',
                'class' => 'csdb',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'cr' => array(
                'name' => 'cr',
                'type' => 'input',
                'label' => 'Credit',
                'class' => 'cscr',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'bal' => array(
                'name' => 'bal',
                'type' => 'input',
                'label' => 'Balance',
                'class' => 'csbal',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'passbook' => array(
                'name' => 'passbook',
                'type' => 'label',
                'label' => 'BASED ON PASSBOOK',
                'class' => 'cspassbook',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'begbal' => array(
                'name' => 'begbal',
                'type' => 'input',
                'label' => 'Beg. Balance',
                'class' => 'csbegbal',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'endbal' => array(
                'name' => 'endbal',
                'type' => 'input',
                'label' => 'End Balance',
                'class' => 'csendbal',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'clearbal' => array(
                'name' => 'clearbal',
                'type' => 'input',
                'label' => 'Cleared Balance',
                'class' => 'csclearbal',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'difference' => array(
                'name' => 'difference',
                'type' => 'input',
                'label' => 'Difference',
                'class' => 'csdifference',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'interest' => array(
                'name' => 'interest',
                'type' => 'input',
                'label' => 'Interest Earned',
                'class' => 'csinterest',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'intannum' => array(
                'name' => 'intannum',
                'type' => 'input',
                'label' => 'Interest Per Annum',
                'class' => 'csintannum',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'deduction' => array(
                'name' => 'deduction',
                'type' => 'input',
                'label' => 'Deduction',
                'class' => 'csdeduction',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'deposit' => array(
                'name' => 'deposit',
                'type' => 'input',
                'label' => 'Deposit',
                'class' => 'csdeposit',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'withdrawal' => array(
                'name' => 'withdrawal',
                'type' => 'input',
                'label' => 'Withdrawal',
                'class' => 'cswithdrawal',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'terms' => array(
                'name' => 'terms',
                'type' => 'lookup',
                'label' => 'Terms',
                'class' => 'csterms sbccsreadonly',
                'lookupclass' => 'terms',
                'action' => 'lookupterms',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'addedparams' => ['dateid', 'client']
            ),
            'cur' => array(
                'name' => 'cur',
                'type' => 'lookup',
                'label' => 'Currency',
                'class' => 'cscur sbccsreadonly',
                'action' => 'lookupcurrency',
                'lookupclass' => 'lookupcur',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'forex' => array(
                'name' => 'forex',
                'type' => 'cinput',
                'label' => 'Forex',
                'class' => 'csforex',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 10
            ),
            'cur2' => array(
                'name' => 'cur2',
                'type' => 'lookup',
                'label' => 'Currency',
                'class' => 'cscur sbccsreadonly',
                'action' => 'lookupcurrency',
                'lookupclass' => 'lookupcur',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'forex2' => array(
                'name' => 'forex2',
                'type' => 'cinput',
                'label' => 'Forex',
                'class' => 'csforex',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 10
            ),
            'tax' => array(
                'name' => 'tax',
                'type' => 'input',
                'label' => 'Tax',
                'class' => 'cstax sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'surcharge' => array(
                'name' => 'surcharge',
                'type' => 'input',
                'label' => 'Surcharge (%)',
                'class' => 'cssurcharge',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'totaltax' => array(
                'name' => 'totaltax',
                'type' => 'input',
                'label' => 'Total Tax',
                'class' => 'cstax sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'dvattype' => array(
                'name' => 'dvattype',
                'type' => 'lookup',
                'label' => 'Vat Type',
                'labeldata' => 'tax~vattype',
                'class' => 'csvattype sbccsreadonly',
                'lookupclass' => 'vattype',
                'action' => 'lookupvattype',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'rem' => array(
                'name' => 'rem',
                'type' => 'ctextarea',
                'label' => 'Notes',
                'class' => 'csrem',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'error' => false,
                'maxlength' => 1000
            ),
            'rem1' => array(
                'name' => 'rem1',
                'type' => 'input',
                'label' => 'DNotes',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 1000
            ),
            'porem' => array(
                'name' => 'porem',
                'type' => 'ctextarea',
                'label' => 'Notes',
                'class' => 'csporem',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'error' => false
            ),
            'rrrem' => array(
                'name' => 'rrrem',
                'type' => 'ctextarea',
                'label' => 'Notes',
                'class' => 'csrrrem',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'error' => false
            ),
            'returnrem' => array(
                'name' => 'returnrem',
                'type' => 'ctextarea',
                'label' => 'Return Remarks',
                'class' => 'csrem sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'error' => false,
                'maxlength' => 1000
            ),
            'rem2' => array(
                'name' => 'rem2',
                'type' => 'ctextarea',
                'label' => 'Notes',
                'class' => 'csrem2',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'error' => false,
                'maxlength' => 200
            ),
            'rem3' => array(
                'name' => 'rem3',
                'type' => 'ctextarea',
                'label' => 'Notes',
                'class' => 'csrem2',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'error' => false,
                'maxlength' => 200
            ),
            'insurance' => array(
                'name' => 'insurance',
                'type' => 'cinput',
                'label' => 'Insurance',
                'class' => 'csinsurance',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'error' => false,
                'maxlength' => 50
            ),
            'tasktitle' => array(
                'name' => 'tasktitle',
                'type' => 'cinput',
                'label' => 'Task Title',
                'class' => 'cstasktitle',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'error' => false,
                'maxlength' => 50
            ),
            'assignto' => array(
                'name' => 'assignto',
                'type' => 'lookup',
                'label' => 'Assign To',
                'class' => 'csassignto sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'lookupclass' => 'lookupassignto',
                'action' => 'lookupassignto'
            ),
            'errandtype' => array(
                'name' => 'errandtype',
                'type' => 'lookup',
                'label' => 'Errand Type',
                'class' => 'cserrandtype sbccsreadonly',
                'lookupclass' => 'errandtype',
                'action' => 'lookuperrandtype',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'contra' => array(
                'name' => 'contra',
                'type' => 'lookup',
                'label' => 'Account',
                'class' => 'cscontra sbccsreadonly',
                'lookupclass' => 'AP',
                'action' => 'lookupcontra',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'ddepdbname' => array(
                'name' => 'ddepdbname',
                'type' => 'lookup',
                'label' => 'Debit Depreciation',
                'class' => 'csdepdbname sbccsreadonly',
                'labeldata' => 'depdbcode~depdbname',
                'lookupclass' => 'DEPREDB',
                'action' => 'lookupcontra',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'ddepcrname' => array(
                'name' => 'ddepcrname',
                'type' => 'lookup',
                'label' => 'Credit Accu Depreciation',
                'class' => 'csdepcrname sbccsreadonly',
                'labeldata' => 'depcrcode~depcrname',
                'lookupclass' => 'DEPRECR',
                'action' => 'lookupcontra',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'dcentername' => array(
                'name' => 'dcentername',
                'type' => 'lookup',
                'label' => 'Branch',
                'labeldata' => 'center~centername',
                'class' => 'cscenter sbccsreadonly',
                'lookupclass' => 'rcenter',
                'action' => 'lookupcenter',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'ditemname' => array(
                'name' => 'ditemname',
                'type' => 'lookup',
                'label' => 'Itemname',
                'labeldata' => 'barcode~itemname',
                'class' => 'csitemname sbccsreadonly',
                'lookupclass' => 'lookupitem',
                'action' => 'lookupitem',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'dclientname' => array(
                'name' => 'dclientname',
                'type' => 'lookup',
                'label' => 'Supplier',
                'labeldata' => 'client~clientname',
                'class' => 'cscontra sbccsreadonly',
                'lookupclass' => 'rsupplier',
                'action' => 'lookupclient',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'personsinvolved' => array(
                'name' => 'personsinvolved',
                'type' => 'lookup',
                'label' => 'Persons Involved',
                'labeldata' => 'client~clientname',
                'class' => 'cscontra sbccsreadonly',
                'lookupclass' => 'lookuppersonsinvolved',
                'action' => 'lookuppersonsinvolved',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'dcentername' => array(
                'name' => 'dcentername',
                'type' => 'lookup',
                'label' => 'Branch',
                'labeldata' => 'center~centername',
                'class' => 'cscenter sbccsreadonly',
                'lookupclass' => 'rcenter',
                'action' => 'lookupcenter',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'dpurchaser' => array(
                'name' => 'dpurchaser',
                'type' => 'lookup',
                'label' => 'Purchaser',
                'labeldata' => 'purchasercode~purchasername',
                'class' => 'cscontra sbccsreadonly',
                'lookupclass' => 'employee',
                'action' => 'lookupclient',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'dattention' => array(
                'name' => 'dattention',
                'type' => 'lookup',
                'label' => 'Attention',
                'labeldata' => 'attention_code~attention_name',
                'class' => 'csdattention sbccsreadonly',
                'lookupclass' => 'attentionlookup',
                'action' => 'attentionlookup',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'aclientname' => array(
                'name' => 'aclientname',
                'type' => 'lookup',
                'label' => 'Applicant',
                'labeldata' => 'client~clientname',
                'class' => 'csaclientname sbccsreadonly',
                'lookupclass' => 'lookupallapplicants',
                'action' => 'lookupclient',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'dacnoname' => array(
                'name' => 'dacnoname',
                'type' => 'lookup',
                'label' => 'Account',
                'labeldata' => 'contra~acnoname',
                'class' => 'cscontra sbccsreadonly',
                'lookupclass' => 'AP',
                'action' => 'lookupcontra',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'dexpacnoname' => array(
                'name' => 'dexpacnoname',
                'type' => 'lookup',
                'label' => 'Purchase Expense Account',
                'labeldata' => 'contra2~acnoname2',
                'class' => 'cswaybill sbccsreadonly',
                'lookupclass' => 'EX',
                'action' => 'lookupcontra',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isacnoname' => array(
                'name' => 'isacnoname',
                'type' => 'lookup',
                'label' => 'Account',
                'labeldata' => 'contra~acnoname',
                'class' => 'cscontra sbccsreadonly',
                'lookupclass' => 'IS',
                'action' => 'lookupcontra',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'dprojectname' => array(
                'name' => 'dprojectname',
                'type' => 'lookup',
                'label' => 'Project',
                'labeldata' => 'projectcode~projectname',
                'class' => 'cscontra sbccsreadonly',
                'lookupclass' => 'project',
                'action' => 'lookupproject',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'consignee' => array(
                'name' => 'consignee',
                'type' => 'lookup',
                'label' => 'Consignee',
                'class' => 'csconsignee sbccsreadonly',
                'lookupclass' => 'consignee',
                'action' => 'lookupconsignee',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'conaddr' => array(
                'name' => 'conaddr',
                'type' => 'input',
                'label' => 'Consignee Address',
                'class' => 'sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'shipper' => array(
                'name' => 'shipper',
                'type' => 'lookup',
                'label' => 'Shipper',
                'class' => 'csshipper sbccsreadonly',
                'lookupclass' => 'shipper',
                'action' => 'lookupshipper',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'repitemgroup' => array(
                'name' => 'repitemgroup',
                'type' => 'lookup',
                'label' => 'Item Group',
                'class' => 'csrepitemgroup sbccsreadonly',
                'lookupclass' => 'lookuprepitemgroup',
                'action' => 'lookupproject',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'dprojectname2' => array(
                'name' => 'dprojectname2',
                'type' => 'lookup',
                'label' => 'Project',
                'labeldata' => 'projectcode2~projectname2',
                'class' => 'cscontra sbccsreadonly',
                'lookupclass' => 'project',
                'action' => 'lookupproject',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'contact' => array(
                'name' => 'contact',
                'type' => 'input',
                'label' => 'Contact',
                'class' => 'cscontact',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'acct' => array(
                'name' => 'acct',
                'type' => 'cinput',
                'label' => 'Contact Person',
                'class' => 'csacct',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'attention' => array(
                'name' => 'attention',
                'type' => 'cinput',
                'label' => 'Attention',
                'class' => 'csattention',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'tel' => array(
                'name' => 'tel',
                'type' => 'input',
                'label' => 'Telephone#',
                'class' => 'cstel',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'fax' => array(
                'name' => 'fax',
                'type' => 'input',
                'label' => 'Fax #',
                'class' => 'csfax',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'tel2' => array(
                'name' => 'tel2',
                'type' => 'input',
                'label' => 'Mobile #',
                'class' => 'cstel2',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'email' => array(
                'name' => 'email',
                'type' => 'input',
                'label' => 'Email',
                'class' => 'csemail',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'pemail' => array(
                'name' => 'pemail',
                'type' => 'input',
                'label' => 'Personal Email',
                'class' => 'csemail',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'password' => array(
                'name' => 'password',
                'type' => 'input',
                'label' => 'Password',
                'class' => 'cspassword',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'ename' => array(
                'name' => 'ename',
                'type' => 'input',
                'label' => 'Employer Name',
                'class' => 'csename',
                'readonly' => false,
                'style' => $this->style,
                'required' => true,
                'maxlength' => 50
            ),
            'monthly' => array(
                'name' => 'monthly',
                'type' => 'input',
                'label' => 'Monthly Income',
                'class' => 'csmonthly',
                'readonly' => false,
                'style' => $this->style,
                'required' => true,
                'maxlength' => 50
            ),
            'current1' => array(
                'name' => 'current1',
                'type' => 'input',
                'label' => 'Current Account#',
                'class' => 'cscurrent1',
                'readonly' => false,
                'style' => $this->style,
                'required' => true,
                'maxlength' => 50
            ),
            'current2' => array(
                'name' => 'current2',
                'type' => 'input',
                'label' => 'Current Bank',
                'class' => 'cscurrent2',
                'readonly' => false,
                'style' => $this->style,
                'required' => true,
                'maxlength' => 50
            ),
            'others1' => array(
                'name' => 'others1',
                'type' => 'input',
                'label' => 'Others Account#',
                'class' => 'csothers1',
                'readonly' => false,
                'style' => $this->style,
                'required' => true,
                'maxlength' => 50
            ),
            'others2' => array(
                'name' => 'others2',
                'type' => 'input',
                'label' => 'Others Bank',
                'class' => 'csothers2',
                'readonly' => false,
                'style' => $this->style,
                'required' => true,
                'maxlength' => 50
            ),
            'num' => array(
                'name' => 'num',
                'type' => 'input',
                'label' => 'Number',
                'class' => 'csnum',
                'readonly' => false,
                'style' => $this->style,
                'required' => true,
                'maxlength' => 50
            ),
            'pliss' => array(
                'name' => 'pliss',
                'type' => 'input',
                'label' => 'Place of issue',
                'class' => 'cspliss',
                'readonly' => false,
                'style' => $this->style,
                'required' => true,
                'maxlength' => 50
            ),
            'pword' => array(
                'name' => 'pword',
                'type' => 'password',
                'label' => 'App Password',
                'class' => 'cspword',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'istenant' => array(
                'name' => 'istenant',
                'type' => 'checkbox',
                'label' => 'Tenant',
                'class' => 'csistenant',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'iscustomer' => array(
                'name' => 'iscustomer',
                'type' => 'checkbox',
                'label' => 'Customer',
                'class' => 'csiscustomer',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'issupplier' => array(
                'name' => 'issupplier',
                'type' => 'checkbox',
                'label' => 'Supplier',
                'class' => 'csissupplier',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isagent' => array(
                'name' => 'isagent',
                'type' => 'checkbox',
                'label' => 'Agent',
                'class' => 'csisagent',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'istrucking' => array(
                'name' => 'istrucking',
                'type' => 'checkbox',
                'label' => 'Forwarder/Truck',
                'class' => 'csistrucking',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'iswarehouse' => array(
                'name' => 'iswarehouse',
                'type' => 'checkbox',
                'label' => 'Warehouse',
                'class' => 'csiswarehouse',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isassetwh' => array(
                'name' => 'isassetwh',
                'type' => 'checkbox',
                'label' => 'Asset Warehouse',
                'class' => 'csisassetwh',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isfa' => array(
                'name' => 'isfa',
                'type' => 'checkbox',
                'label' => 'Fixed Asset',
                'class' => 'csisfa',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isemployee' => array(
                'name' => 'isemployee',
                'type' => 'checkbox',
                'label' => 'Employee',
                'class' => 'csisemployee',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isstudent' => array(
                'name' => 'isstudent',
                'type' => 'checkbox',
                'label' => 'Student',
                'class' => 'csisstudent',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isinactive' => array(
                'name' => 'isinactive',
                'type' => 'checkbox',
                'label' => 'Inactive',
                'class' => 'csisinactive',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'ispermanent' => array(
                'name' => 'ispermanent',
                'type' => 'checkbox',
                'label' => 'Permanent',
                'class' => 'csispermanent',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'isnoninv' => array(
                'name' => 'isnoninv',
                'type' => 'checkbox',
                'label' => 'Non Inventory',
                'class' => 'csisnoninv',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isgeneric' => array(
                'name' => 'isgeneric',
                'type' => 'checkbox',
                'label' => 'Generic/General item',
                'class' => 'csisgeneric',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isnonserial' => array(
                'name' => 'isnonserial',
                'type' => 'checkbox',
                'label' => 'Non Serialized',
                'class' => 'csisnonserial',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isnsi' => array(
                'name' => 'isnsi',
                'type' => 'checkbox',
                'label' => 'No System Input(NSI)',
                'class' => 'csisnsi',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),


            'ists' => array(
                'name' => 'ists',
                'type' => 'checkbox',
                'label' => 'TS',
                'class' => 'csists',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'isinvoice' => array(
                'name' => 'isinvoice',
                'type' => 'checkbox',
                'label' => 'Invoice',
                'class' => 'csisinvoice',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'isserial' => array(
                'name' => 'isserial',
                'type' => 'checkbox',
                'label' => 'Serialized',
                'class' => 'csisserial',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isall' => array(
                'name' => 'isall',
                'type' => 'checkbox',
                'label' => 'All Branch/Warehouse',
                'class' => 'csisall',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isbuy1' => array(
                'name' => 'isbuy1',
                'type' => 'checkbox',
                'label' => 'BUY 1 TAKE 1',
                'class' => 'csisbuy1',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isamt' => array(
                'name' => 'isamt',
                'type' => 'checkbox',
                'label' => 'Amount',
                'class' => 'csisamt',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'refresh' => array(
                'name' => 'refresh',
                'type' => 'button',
                'label' => 'REFRESH',
                'class' => 'csrefresh',
                'action' => 'ar',
                'readonly' => true,
                'style' => 'height:100%',
                'required' => false
            ),
            'reset' => array(
                'name' => 'reset',
                'type' => 'button',
                'label' => 'RESET',
                'class' => 'csreset',
                'action' => 'reset',
                'readonly' => true,
                'style' => 'height:100%',
                'required' => false
            ),
            'reload' => array(
                'name' => 'reload',
                'type' => 'button',
                'label' => 'LOAD',
                'class' => 'csreload',
                'action' => 'reload',
                'readonly' => true,
                'style' => 'height:100%',
                'required' => false
            ),
            'loadhistory' => array(
                'name' => 'loadhistory',
                'type' => 'button',
                'label' => 'LOAD HISTORY',
                'class' => 'csloadhistory',
                'action' => 'history',
                'readonly' => true,
                'style' => 'height:100%',
                'required' => false
            ),
            'loadtrip' => array(
                'name' => 'backlisting',
                'type' => 'actionbtn',
                'label' => 'LOAD TRIP',
                'class' => 'btnloadtrip',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'refresh',
                'access' => 'save',
                'action' => 'loadtrip',
                'readonly' => true,
                'style' => 'height:100%',
                'required' => false
            ),
            'close' => array(
                'name' => 'close',
                'type' => 'button',
                'label' => 'CLOSE',
                'class' => 'csclose',
                'action' => 'close',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'post' => array(
                'name' => 'post',
                'type' => 'button',
                'label' => 'POST',
                'class' => 'cspost',
                'action' => 'post',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'disapproved' => array(
                'name' => 'disapproved',
                'type' => 'button',
                'label' => 'Disapprove',
                'class' => 'csdisapproved',
                'action' => 'disapproved',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'confirm' => true,
                'confirmlabel' => 'Disapproved?'
            ),
            'onhold' => array(
                'name' => 'onhold',
                'type' => 'button',
                'label' => 'On-Hold',
                'class' => 'csonhold',
                'action' => 'onhold',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'confirm' => true,
                'confirmlabel' => 'On Hold?'
            ),
            'reconcile' => array(
                'name' => 'reconcile',
                'type' => 'button',
                'label' => 'RECONCILE',
                'class' => 'csreconcile',
                'action' => 'reconcile',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'confirm' => true,
                'confirmlabel' => 'Proceed to reconcile?'
            ),
            'pickpo' => array(
                'name' => 'pickpo',
                'type' => 'button',
                'label' => 'PICK PO',
                'class' => 'cspickpo',
                'lookupclass' => 'pendingposummaryshortcut',
                'action' => 'pendingposummary',
                'action2' => 'lookup',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'confirm' => true,
                'confirmlabel' => 'Proceed to pick PO?'
            ),
            'pickmr' => array(
                'name' => 'pickmr',
                'type' => 'button',
                'label' => 'PICK MR',
                'class' => 'cspickmr',
                'lookupclass' => 'pendingmrsummaryshortcut',
                'action' => 'pendingmrsummary',
                'action2' => 'lookup',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'confirm' => true,
                'confirmlabel' => 'Proceed to pick MR?'
            ),
            'picktr' => array(
                'name' => 'picktr',
                'type' => 'button',
                'label' => 'PICK TR',
                'class' => 'cspicktr',
                'lookupclass' => 'pendingtrsummary',
                'action' => 'pendingtrsummary',
                'action2' => 'lookup',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'confirm' => true,
                'confirmlabel' => 'Proceed to pick TR?'
            ),
            'batchpostsj' => array(
                'name' => 'batchpostsj',
                'type' => 'button',
                'label' => 'POST BATCH SJ',
                'class' => 'csbatchpostsj',
                'lookupclass' => 'batchpostsj',
                'action' => 'batchpostsj',
                'action2' => 'stockstatusposted',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'access' => 'post',
                'confirm' => true,
                'confirmlabel' => 'Proceed to post batch SJ?'
            ),
            'generatemr' => array(
                'name' => 'generatemr',
                'type' => 'button',
                'label' => 'GENERATE MR',
                'class' => 'csgeneratemr',
                'lookupclass' => 'generatemr',
                'action' => 'generatemr',
                'action2' => 'stockstatusposted',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'access' => 'post',
                'confirm' => true,
                'confirmlabel' => 'Proceed to generate MR?'
            ),
            'update' => array(
                'name' => 'update',
                'type' => 'button',
                'label' => 'Save Changes',
                'class' => 'csupdate',
                'action' => 'update',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'selectall' => array(
                'name' => 'selectall',
                'type' => 'button',
                'icon' => 'check',
                'label' => 'Mark All',
                'class' => 'csselectall',
                'action' => 'selectall',
                'readonly' => true,
                'style' => 'font-size:100%;',
                'required' => false
            ),
            'unmarkall' => array(
                'name' => 'unmarkall',
                'type' => 'button',
                'icon' => 'close',
                'label' => 'Unmark All',
                'class' => 'csunmarkall',
                'action' => 'unmarkall',
                'readonly' => true,
                'style' => 'font-size:100%;',
                'required' => false
            ),
            'dlexcelmbtctxtfile' => array(
                'name' => 'downloadexcel',
                'type' => 'actionbtn',
                'label' => 'Download Metrobank Txt File',
                'class' => 'csupdate',
                'action' => 'dlexcelmbtctxtfile',
                'lookupclass' => 'dlexcelmbtctxtfile',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'access' => 'view'
            ),
            'dlexcelbpitxtfile' => array(
                'name' => 'downloadexcel',
                'type' => 'actionbtn',
                'label' => 'Download BPI Txt File',
                'class' => 'csupdate',
                'action' => 'dlexcelbpitxtfile',
                'lookupclass' => 'dlexcelbpitxtfile',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false,
                'access' => 'view'
            ),
            'dlexcelpricelistusd' => array(
                'name' => 'downloadexcel',
                'type' => 'actionbtn',
                'label' => 'Download Pricelist USD Template',
                'class' => 'csupdate',
                'action' => 'dlexcelpricelistusd',
                'lookupclass' => 'dlexcelpricelistusd',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'access' => 'view'
            ),
            'dlexcelpricelisttp' => array(
                'name' => 'downloadexcel',
                'type' => 'actionbtn',
                'label' => 'Download Transfer Price Template',
                'class' => 'csupdate',
                'action' => 'dlexcelpricelisttp',
                'lookupclass' => 'dlexcelpricelisttp',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'access' => 'view'
            ),
            'downloaditemexcel' => array(
                'name' => 'downloadexcel',
                'type' => 'actionbtn',
                'label' => 'Download Item Template',
                'class' => 'csupdate',
                'action' => 'downloaditemexcel',
                'lookupclass' => 'downloaditemexcel',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'access' => 'view'
            ),
            'downloaditemexcelmaster' => array(
                'name' => 'downloadexcel',
                'type' => 'actionbtn',
                'label' => 'Download Item Master',
                'class' => 'csupdate',
                'action' => 'downloaditemexcelmaster',
                'lookupclass' => 'downloaditemexcelmaster',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'access' => 'view'
            ),
            'downloaduomexcelmaster' => array(
                'name' => 'downloadexcel',
                'type' => 'actionbtn',
                'label' => 'Download UOM Master',
                'class' => 'csupdate',
                'action' => 'downloaduomexcelmaster',
                'lookupclass' => 'downloaduomexcelmaster',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'access' => 'view'
            ),
            'downloaditemuomexcelmaster' => array(
                'name' => 'downloadexcel',
                'type' => 'actionbtn',
                'label' => 'Download Item/UOM Master',
                'class' => 'csupdate',
                'action' => 'downloaditemuomexcelmaster',
                'lookupclass' => 'downloaditemuomexcelmaster',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'access' => 'view'
            ),
            'downloademployeeexcelmaster' => array(
                'name' => 'downloadexcel',
                'type' => 'actionbtn',
                'label' => 'Download Employee Master',
                'class' => 'csupdate',
                'action' => 'downloademployeeexcelmaster',
                'lookupclass' => 'downloademployeeexcelmaster',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'access' => 'view'
            ),
            'downloadcustomerexcelmaster' => array(
                'name' => 'downloadexcel',
                'type' => 'actionbtn',
                'label' => 'Download Customer Master',
                'class' => 'csupdate',
                'action' => 'downloadcustomerexcelmaster',
                'lookupclass' => 'downloadcustomerexcelmaster',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'access' => 'view'
            ),
            'downloadcustomerexcel' => array(
                'name' => 'downloadexcel',
                'type' => 'actionbtn',
                'label' => 'Download Customer Template',
                'class' => 'csupdate',
                'action' => 'downloadcustomerexcel',
                'lookupclass' => 'downloadcustomerexcel',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'access' => 'view'
            ),
            'downloademployeeexcel' => array(
                'name' => 'downloadexcel',
                'type' => 'actionbtn',
                'label' => 'Download Employee Template',
                'class' => 'csupdate',
                'action' => 'downloademployeeexcel',
                'lookupclass' => 'downloademployeeexcel',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'access' => 'view'
            ),
            'downloadwhexcel' => array(
                'name' => 'downloadexcel',
                'type' => 'actionbtn',
                'label' => 'Download Warehouse Template',
                'class' => 'csupdate',
                'action' => 'downloadwhexcel',
                'lookupclass' => 'downloadwhexcel',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'access' => 'view'
            ),
            'downloadsupplierexcel' => array(
                'name' => 'downloadexcel',
                'type' => 'actionbtn',
                'label' => 'Download Supplier Template',
                'class' => 'csupdate',
                'action' => 'downloadsupplierexcel',
                'lookupclass' => 'downloadsupplierexcel',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'access' => 'view'
            ),
            'downloadsupplieritemexcel' => array(
                'name' => 'downloadexcel',
                'type' => 'actionbtn',
                'label' => 'Download Supplier Item Template',
                'class' => 'csupdate',
                'action' => 'downloadsupplieritemexcel',
                'lookupclass' => 'downloadsupplieritemexcel',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'access' => 'view'
            ),
            'downloadagentexcel' => array(
                'name' => 'downloadexcel',
                'type' => 'actionbtn',
                'label' => 'Download Agent Template',
                'class' => 'csupdate',
                'action' => 'downloadagentexcel',
                'lookupclass' => 'downloadagentexcel',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'access' => 'view'
            ),
            'downloadpcexcel' => array(
                'name' => 'downloadexcel',
                'type' => 'actionbtn',
                'label' => 'Download PC Template',
                'class' => 'csupdate',
                'action' => 'downloadpcexcel',
                'lookupclass' => 'downloadpcexcel',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'access' => 'view'
            ),
            'downloaduomexcel' => array(
                'name' => 'downloadexcel',
                'type' => 'actionbtn',
                'label' => 'Download UOM Template',
                'class' => 'csupdate',
                'action' => 'downloaduomexcel',
                'lookupclass' => 'downloaduomexcel',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'access' => 'view'
            ),
            'downloadpricelistexcel' => array(
                'name' => 'downloadexcel',
                'type' => 'actionbtn',
                'label' => 'Download Price List Template',
                'class' => 'csupdate',
                'action' => 'downloadpricelistexcel',
                'lookupclass' => 'downloadpricelistexcel',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'access' => 'view'
            ),
            'downloadpricelistexcelmaster' => array(
                'name' => 'downloadexcel',
                'type' => 'actionbtn',
                'label' => 'Download Price List Master',
                'class' => 'csupdate',
                'action' => 'downloadpricelistexcelmaster',
                'lookupclass' => 'downloadpricelistexcelmaster',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'access' => 'view'
            ),
            'downloadpnpcsrexcelmaster' => array(
                'name' => 'downloadexcel',
                'type' => 'actionbtn',
                'label' => 'Download PNP/CSR Template',
                'class' => 'csupdate',
                'action' => 'downloadpnpcsrexcelmaster',
                'lookupclass' => 'downloadpnpcsrexcelmaster',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'access' => 'view'
            ),
            'downloadcontact' => array(
                'name' => 'downloadexcel',
                'type' => 'actionbtn',
                'label' => 'Download Contact Person Template',
                'class' => 'csupdate',
                'action' => 'downloadcontact',
                'lookupclass' => 'downloadcontact',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'access' => 'view'
            ),
            'downloadaddress' => array(
                'name' => 'downloadexcel',
                'type' => 'actionbtn',
                'label' => 'Download Address Template',
                'class' => 'csupdate',
                'action' => 'downloadaddress',
                'lookupclass' => 'downloadaddress',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'access' => 'view'
            ),
            'downloadrrexcel' => array(
                'name' => 'downloadexcel',
                'type' => 'actionbtn',
                'label' => 'Download RR Template',
                'class' => 'csupdate',
                'action' => 'downloadrrexcel',
                'lookupclass' => 'downloadrrexcel',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'access' => 'view'
            ),
            'downloadwnexcel' => array(
                'name' => 'downloadexcel',
                'type' => 'actionbtn',
                'label' => 'Download Water Connection Template',
                'class' => 'csupdate',
                'action' => 'downloadwnexcel',
                'lookupclass' => 'downloadwnexcel',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'access' => 'view'
            ),
            'uploadexcel' => array(
                'name' => 'uploadexcel',
                'type' => 'actionbtn',
                'label' => 'Upload Excel',
                'class' => 'csupdate',
                'action' => 'uploadexcel',
                'lookupclass' => 'uploadexcel',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'access' => 'save'
            ),
            'downloadexcel' => array(
                'name' => 'downloadexcel',
                'type' => 'downloadexcel',
                'label' => 'Download Excel',
                'class' => 'csupdate',
                'action' => 'downloadexcel',
                'lookupclass' => 'downloadexcel',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'exportcsv' => array(
                'name' => 'exportcsv',
                'type' => 'actionbtn',
                'label' => 'Export CSV',
                'class' => 'csupdate',
                'access' => 'view',
                'action' => 'exportcsv',
                'lookupclass' => 'exportcsv',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'readfile' => array(
                'name' => 'readfile',
                'type' => 'actionbtn',
                'label' => 'Read File',
                'class' => 'csupdate',
                'access' => 'view',
                'action' => 'readfile',
                'lookupclass' => 'readfile',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'viewprocess' => array(
                'name' => 'viewprocess',
                'type' => 'button',
                'label' => 'VIEW PROCESS',
                'class' => 'csupdate',
                'action' => 'viewprocess',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'recalc' => array(
                'name' => 'recalc',
                'type' => 'button',
                'label' => 'RECALC',
                'class' => 'cscrecalc',
                'action' => 'recalc',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'create' => array(
                'name' => 'create',
                'type' => 'button',
                'label' => 'CREATE',
                'class' => 'cscreate',
                'action' => 'create',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'saveclearpr' => array(
                'name' => 'saveclearpr',
                'type' => 'button',
                'label' => 'Save Cleared PR',
                'class' => 'cscreate',
                'action' => 'saveclearpr',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'generatecode' => array(
                'name' => 'generatecode',
                'type' => 'actionbtn',
                'label' => 'Generate Temporary barcode',
                'class' => 'cscreate',
                'lookupclass' => 'stockstatusposted',
                'action' => 'generatecode',
                'access' => 'generatecode',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'confirm' => true,
                'confirmlabel' => 'Generate Temporary barcode?'
            ),
            'postinout' => array(
                'name' => 'postinout',
                'type' => 'button',
                'label' => 'Post Actual In/Out',
                'class' => 'cspostinout',
                'action' => 'postinout',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'confirm' => true,
                'confirmlabel' => 'Post actual in/out?'
            ),
            'computetimecard' => array(
                'name' => 'computetimecard',
                'type' => 'button',
                'label' => 'Compute Timecard',
                'class' => 'cscomputetimecard',
                'action' => 'computetimecard',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'confirm' => true,
                'confirmlabel' => 'Do you want to compute timecard?'
            ),
            'computetimesheet' => array(
                'name' => 'computetimesheet',
                'type' => 'button',
                'label' => 'Compute Time Sheet',
                'class' => 'computetimesheet',
                'action' => 'computetimesheet',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'confirm' => true,
                'confirmlabel' => 'Do you want to compute time sheet?'
            ),
            'payrollclosing' => array(
                'name' => 'payrollclosing',
                'type' => 'button',
                'label' => 'Payroll Closing',
                'class' => 'payrollclosing',
                'action' => 'payrollclosing',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'confirm' => true,
                'confirmlabel' => 'Do you want to close this batch?'
            ),
            'payrollunclosing' => array(
                'name' => 'payrollunclosing',
                'type' => 'button',
                'label' => 'Payroll Unclosing',
                'class' => 'payrollunclosing',
                'action' => 'payrollunclosing',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'confirm' => true,
                'confirmlabel' => 'Do you want to unclose this batch?'
            ),
            'print' => array(
                'name' => 'print',
                'type' => 'button',
                'label' => 'Print',
                'class' => 'csprint',
                'action' => 'print',
                'action2' => 'print',
                'lookupclass' => 'doclistprint',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'barcode' => array(
                'name' => 'barcode',
                'type' => 'lookup',
                'label' => 'Barcode',
                'class' => 'csbarcode sbccsenablealways',
                'lookupclass' => 'barcode',
                'action' => 'lookupbarcode',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'itemname' => array(
                'name' => 'itemname',
                'type' => 'input',
                'label' => 'Itemname',
                'class' => 'csitemname',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'oraclecode' => array(
                'name' => 'oraclecode',
                'type' => 'input',
                'label' => 'Oracle Code',
                'class' => 'csoraclecode',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'shortname' => array(
                'name' => 'shortname',
                'type' => 'input',
                'label' => 'Short Description',
                'class' => 'csshortname',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 30
            ),
            'isqty' => array(
                'name' => 'isqty',
                'type' => 'input',
                'label' => 'Quantity',
                'class' => 'csisqty',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'replaceqty' => array(
                'name' => 'replaceqty',
                'type' => 'input',
                'label' => 'For Replacement Quantity',
                'class' => 'csisqty',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'uom' => array(
                'name' => 'uom',
                'type' => 'input',
                'label' => 'UOM',
                'class' => 'csuom',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 10
            ),
            'uom2' => array(
                'name' => 'uom2',
                'type' => 'lookup',
                'label' => 'UOM',
                'class' => 'csuom2 sbccsreadonly',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 10
            ),
            'uom3' => array(
                'name' => 'uom3',
                'type' => 'lookup',
                'label' => 'UOM',
                'class' => 'csuom3 sbccsreadonly',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 10
            ),
            'luom' => array(
                'name' => 'uom',
                'type' => 'lookup',
                'label' => 'UOM',
                'class' => 'csuom sbccsreadonly',
                'lookupclass' => 'uom',
                'action' => 'lookupuom',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'radiostatus' => array(
                'name' => 'status',
                'label' => 'Status',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Pending', 'value' => 0, 'color' => 'purple'],
                    ['label' => 'Approved', 'value' => 1, 'color' => 'green'],
                    ['label' => 'Reject', 'value' => 2, 'color' => 'red']
                )
            ),
            'promobasis' => array(
                'name' => 'promobasis',
                'type' => 'qradio',
                'items' => array(
                    'isqty' => ['label' => 'Quantity', 'val' => '0', 'color' => 'purple'],
                    'isamt' => ['label' => 'Amount', 'val' => '1', 'color' => 'green']
                )
            ),
            'objtype' => array(
                'name' => 'objtype',
                'label' => 'Question Format',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Multiple Choice', 'value' => 0, 'color' => 'purple'],
                    ['label' => 'Text', 'value' => 1, 'color' => 'green'],
                    ['label' => 'Multiple Select', 'value' => 2, 'color' => 'red'],
                    ['label' => 'Image Question', 'value' => 3, 'color' => 'orange'],
                    ['label' => 'Spelling', 'value' => 4, 'color' => 'blue']
                )
            ),
            'icondition' => array(
                'name' => 'icondition',
                'type' => 'qradio',
                'items' => array(
                    'new' => ['label' => 'New', 'val' => '0', 'color' => 'purple'],
                    'used' => ['label' => 'Used', 'val' => '1', 'color' => 'green'],
                    'lease' => ['label' => 'Lease', 'val' => '2', 'color' => 'red'],
                    'repair' => ['label' => 'For Repair', 'val' => '3', 'color' => 'blue']
                )
            ),
            'radioprint' => array(
                'name' => 'print',
                'label' => 'Print as',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
                    ['label' => 'excel', 'value' => 'excel', 'color' => 'red']
                )
            ),
            'radioemail' => array(
                'name' => 'sendmail',
                'label' => 'Email',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
                    ['label' => 'Re-send', 'value' => 'resend', 'color' => 'red']
                )
            ),
            'radioquotation' => array(
                'name' => 'radioquotation',
                'label' => 'Options',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Quotation Printing', 'value' => 'quoteprint', 'color' => 'red'],
                    ['label' => 'Instruction Form Printing', 'value' => 'instructionform', 'color' => 'red'],
                    ['label' => 'Proforma', 'value' => 'proforma', 'color' => 'red']
                )
            ),
            'radiosjafti' => array(
                'name' => 'radiosjafti',
                'label' => 'Options',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Delivery Note', 'value' => 'deliverynote', 'color' => 'red'],
                    ['label' => 'Sales Invoice', 'value' => 'salesinvoice', 'color' => 'red']

                )
            ),
            'radiosjaftilogo' => array(
                'name' => 'radiosjaftilogo',
                'label' => 'Print as ',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Without Logo', 'value' => 'woutlogo', 'color' => 'red'],
                    ['label' => 'With Logo', 'value' => 'wlogo', 'color' => 'red']
                )
            ),
            'radiopoafti' => array(
                'name' => 'radiopoafti',
                'label' => 'Options',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Local Order PDF File', 'value' => 'localorder', 'color' => 'red'],
                    ['label' => 'USD Generation PDF', 'value' => 'generation', 'color' => 'red']
                )
            ),
            'radioearningtype' => array(
                'name' => 'radioearningtype',
                'label' => 'Options',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Default', 'value' => 'default', 'color' => 'red'],
                    ['label' => 'Per Account', 'value' => 'peraccount', 'color' => 'red']

                )
            ),
            'isexpedite' => array(
                'name' => 'isexpedite',
                'type' => 'checkbox',
                'label' => 'Expedite',
                'class' => 'csisexpedite',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'isconfirmed' => array(
                'name' => 'isconfirmed',
                'type' => 'checkbox',
                'label' => 'Confirmed',
                'class' => 'csisconfirmed',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'ischqreleased' => array(
                'name' => 'ischqreleased',
                'type' => 'checkbox',
                'label' => 'Cheque Released',
                'class' => 'csischqreleased',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'ispaid' => array(
                'name' => 'ispaid',
                'type' => 'checkbox',
                'label' => 'Paid',
                'class' => 'csispaid',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'isacknowledged' => array(
                'name' => 'isacknowledged',
                'type' => 'checkbox',
                'label' => 'Acknowledged',
                'class' => 'csisacknowledged',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'isadv' => array(
                'name' => 'isadv',
                'type' => 'checkbox',
                'label' => 'COD/Advance Payment',
                'class' => 'csisadv',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'ispickupdate' => array(
                'name' => 'ispickupdate',
                'type' => 'checkbox',
                'label' => 'Pickup Date Required',
                'class' => 'csispickupdate',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),

            'iscanvassonly' => array(
                'name' => 'iscanvassonly',
                'type' => 'checkbox',
                'label' => 'Canvass Only',
                'class' => 'csiscanvassonly',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'serialized' => array(
                'name' => 'serialized',
                'type' => 'checkbox',
                'label' => 'Serialized',
                'class' => 'csserialized',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'subinv' => array(
                'name' => 'subinv',
                'type' => 'checkbox',
                'label' => 'Sub Inventory',
                'class' => 'csserialized',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),

            'ispartial' => array(
                'name' => 'ispartial',
                'type' => 'checkbox',
                'label' => 'Allow Partial',
                'class' => 'csispartial',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'isshipmentnotif' => array(
                'name' => 'isshipmentnotif',
                'type' => 'lookup',
                'lookupclass' => 'lookupyesno',
                'action' => 'lookuprandom',
                'label' => 'Shipment Permit Notification',
                'class' => 'csisshipmentnotif',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),
            'shipmentnotif' => array(
                'name' => 'shipmentnotif',
                'type' => 'ctextarea',
                'label' => '',
                'class' => 'csshipmentnotif sbccsenablealways',
                'readonly' => false,
                'style' => $this->style,
                'maxlength' => 200,
                'required' => false
            ),

            'radioposttype' => array(
                'name' => 'posttype',
                'label' => 'Type of Transaction',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Posted', 'value' => '0', 'color' => 'teal'],
                    ['label' => 'Unposted', 'value' => '1', 'color' => 'teal']
                )
            ),
            'radioinvoice' => array(
                'name' => 'invoice',
                'label' => 'Invoice to Follow',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Show', 'value' => '0', 'color' => 'orange'],
                    ['label' => 'Hide', 'value' => '1', 'color' => 'orange']
                )
            ),

            'expediteradio' => array(
                'name' => 'expediteradio',
                'label' => 'Other Type of Transaction',
                'type' => 'radio',
                'options' => array(


                    [
                        'label' => 'All',
                        'value' => '0',
                        'color' => 'teal'
                    ],
                    ['label' => 'Expedite', 'value' => '1', 'color' => 'teal']


                )
            ),


            'radiodatetype' => array(
                'name' => 'transdate',
                'label' => 'Date Option',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Transaction Date', 'value' => 'dateid', 'color' => 'teal'],
                    ['label' => 'Delivery Date', 'value' => 'deldate', 'color' => 'teal']
                )
            ),
            'radiopaidstatus' => array(
                'name' => 'paidstatus',
                'label' => 'Status',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Paid', 'value' => '0', 'color' => 'teal'],
                    ['label' => 'Unpaid', 'value' => '1', 'color' => 'teal'],
                    ['label' => 'All', 'value' => '2', 'color' => 'teal'],
                )
            ),
            //edited nov 26
            'radioreporttype' => array(
                'name' => 'reporttype',
                'label' => 'Type of Report',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Summarized', 'value' => '0', 'color' => 'orange'],
                    ['label' => 'Detailed', 'value' => '1', 'color' => 'orange']
                )
            ),
            'radioisassettag' => array(
                'name' => 'isassettag',
                'label' => 'Is Asset Tagging - For Detailed Listing option',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'All', 'value' => '0', 'color' => 'teal'],
                    ['label' => 'Yes', 'value' => '1', 'color' => 'teal'],
                    ['label' => 'No', 'value' => '2', 'color' => 'teal']
                )
            ),
            'radioreporttrnxtype' => array(
                'name' => 'reporttrnxtype',
                'label' => 'Trnx Type',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Regular', 'value' => 'regular', 'color' => 'orange'],
                    ['label' => 'Senior', 'value' => 'senior', 'color' => 'orange'],
                    ['label' => 'PWD', 'value' => 'pwd', 'color' => 'orange'],
                    ['label' => 'Diplomat', 'value' => 'diplomat', 'color' => 'orange'],
                    ['label' => 'All', 'value' => 'all', 'color' => 'orange']

                )
            ),
            'radioretagging' => array(
                'name' => 'tagging',
                'label' => 'Type of Client',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Customer', 'value' => '0', 'color' => 'orange'],
                    ['label' => 'Employee', 'value' => '1', 'color' => 'orange'],
                    ['label' => 'All', 'value' => '2', 'color' => 'orange']
                )
            ),
            'radioreporttypepcv' => array(
                'name' => 'reporttypepcv',
                'label' => 'Type of Report',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Listing', 'value' => '0', 'color' => 'orange'],
                    ['label' => 'Detailed', 'value' => '1', 'color' => 'orange'],
                    ['label' => 'Summarized', 'value' => '2', 'color' => 'orange']
                )
            ),
            'radioincludepcv' => array(
                'name' => 'reportincpcv',
                'label' => 'Include: ',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Unposted', 'value' => '0', 'color' => 'orange'],
                    ['label' => 'Posted/ Untagged', 'value' => '1', 'color' => 'orange'],
                    ['label' => 'Posted/ Tagged', 'value' => '2', 'color' => 'orange'],
                    ['label' => 'All', 'value' => '3', 'color' => 'orange']
                )
            ),
            'radiotaskerrand' => array(
                'name' => 'radiotaskerrand',
                'label' => 'Type of Report',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Pick up of Unit', 'value' => '0', 'color' => 'orange'],
                    ['label' => 'PPIO', 'value' => '1', 'color' => 'orange']
                )
            ),
            'radioreportlabeltype' => array(
                'name' => 'printlabeltype',
                'label' => 'Print Label by',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Box No.', 'value' => 'boxno', 'color' => 'orange'],
                    ['label' => 'SKU', 'value' => 'sku', 'color' => 'orange'],
                )
            ),
            'radionincentivetype' => array(
                'name' => 'incentivetype',
                'label' => 'Option',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'SJ - Dealer', 'value' => 'SD', 'color' => 'red'],
                    ['label' => 'SJ - Online', 'value' => 'SF', 'color' => 'red']
                )
            ),
            'radionincentivestatus' => array(
                'name' => 'incentivestatus',
                'label' => 'Printing Option',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Draft', 'value' => '0', 'color' => 'red'],
                    ['label' => 'Released', 'value' => '1', 'color' => 'red']
                )
            ),

            'radiobilling' => array(
                'name' => 'radiobilling',
                'label' => 'Options',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Default', 'value' => '0', 'color' => 'red'],
                    ['label' => 'Billing Invoice', 'value' => '1', 'color' => 'red']
                )
            ),

            'radiotitleheader' => array(
                'name' => 'radiotitleheader',
                'label' => 'Option Header Name',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Rod and Staff Agriventures Inc.', 'value' => '0', 'color' => 'red'],
                    ['label' => 'Summit Infinity Agricultural Farm Corporation', 'value' => '1', 'color' => 'red']
                )
            ),

            'radiosjeipi' => array(
                'name' => 'radiosjeipi',
                'label' => 'Options',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Default', 'value' => '0', 'color' => 'green'],
                    ['label' => 'Sales Invoice', 'value' => '1', 'color' => 'green'],
                    ['label' => 'Sales Invoice NextQuest - KMP', 'value' => '2', 'color' => 'green'],
                    ['label' => 'Transmittal', 'value' => '3', 'color' => 'green']
                )
            ),

            // GLEN 08.28.2020
            // FOR REPORTS
            'radioreportcustomerfilter' => array(
                'name' => 'customerfilter',
                'label' => 'Customer Filter',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Per Customer', 'value' => '0', 'color' => 'orange'],
                    ['label' => 'By Customer Group', 'value' => '1', 'color' => 'orange']
                )
            ),
            'radioreportitemtype' => array(
                'name' => 'itemtype',
                'label' => 'Item Type',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Local', 'value' => '(0)', 'color' => 'orange'],
                    ['label' => 'Import', 'value' => '(1)', 'color' => 'orange'],
                    ['label' => 'Both', 'value' => '(0,1)', 'color' => 'orange']
                )
            ),
            'radioreportanalyzedby' => array(
                'name' => 'analyzedby',
                'label' => 'Analyzed By',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Value Sold', 'value' => 'value', 'color' => 'orange'],
                    ['label' => 'Unit Sold', 'value' => 'unit', 'color' => 'orange']
                )
            ),
            'radioreportanalyzedbypurhcase' => array(
                'name' => 'analyzedby',
                'label' => 'Analyzed By',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Value Purchased', 'value' => 'value', 'color' => 'orange'],
                    ['label' => 'Unit Purchased', 'value' => 'unit', 'color' => 'orange']
                )
            ),
            'radioreportitemstatus' => array(
                'name' => 'itemstatus',
                'label' => 'Item Status',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Active', 'value' => '(0)', 'color' => 'orange'],
                    ['label' => 'Inactive', 'value' => '(1)', 'color' => 'orange'],
                    ['label' => 'Both', 'value' => '(0,1)', 'color' => 'orange']
                )
            ),
            'radiotypeofreport' => array(
                'name' => 'typeofreport',
                'label' => 'Type of Report',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Purchase Report', 'value' => 'report', 'color' => 'orange'],
                    ['label' => 'Purchase Less Return', 'value' => 'lessreturn', 'color' => 'orange'],
                    ['label' => 'Purchase Return', 'value' => 'return', 'color' => 'orange']
                )
            ),
            'radiotypeofreportsales' => array(
                'name' => 'typeofreport',
                'label' => 'Type of Report',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Sales Report', 'value' => 'report', 'color' => 'orange'],
                    ['label' => 'Sales Less Return', 'value' => 'lessreturn', 'color' => 'orange'],
                    ['label' => 'Sales Return', 'value' => 'return', 'color' => 'orange']
                )
            ),
            'radiotypeofreportdrsi' => array(
                'name' => 'typeofdrsi',
                'label' => 'Type of Report',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Sales SI', 'value' => 'si', 'color' => 'orange'],
                    ['label' => 'Sales DR', 'value' => 'dr', 'color' => 'orange']
                )
            ),
            'radiotypeofreportpendingsalesorder' => array(
                'name' => 'typeofreport',
                'label' => 'Type of Report',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Customer', 'value' => 'client', 'color' => 'orange'],
                    ['label' => 'Item', 'value' => 'item', 'color' => 'orange']
                )
            ),
            'radiosalescustomerperitem' => array(
                'name' => 'options',
                'label' => 'Option',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Amount', 'value' => 'sales', 'color' => 'orange'],
                    ['label' => 'Quantity', 'value' => 'qty', 'color' => 'orange']
                )
            ),
            'radiosortby' => array(
                'name' => 'sortby',
                'label' => 'Sort by',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Document #', 'value' => 'docno', 'color' => 'orange'],
                    ['label' => 'Date', 'value' => 'dateid', 'color' => 'orange']
                )
            ),
            'radiopaymenttype' => array(
                'name' => 'paymenttype',
                'label' => 'Sales Type',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Cash', 'value' => 'cash', 'color' => 'orange'],
                    ['label' => 'Charge', 'value' => 'charge', 'color' => 'orange'],
                    ['label' => 'Check', 'value' => 'check', 'color' => 'orange'],
                    ['label' => 'Deposit', 'value' => 'deposit', 'color' => 'orange'],
                    ['label' => 'All', 'value' => 'all', 'color' => 'orange']
                )
            ),
            'radiocollectiontype' => array(
                'name' => 'collection',
                'label' => 'Collection Type',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'PDC', 'value' => 'pdc', 'color' => 'teal'],
                    ['label' => 'Cash & Cheque', 'value' => 'cach', 'color' => 'teal']


                )
            ),
            'radiohgccompany' => array(
                'name' => 'radiohgccompany',
                'label' => 'List of Companies',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'HOUSEGEM', 'value' => 't0', 'color' => 'green'],
                    ['label' => 'T4TRIUMPH', 'value' => 't1', 'color' => 'green'],
                    ['label' => 'TAITAFALCON', 'value' => 't2', 'color' => 'green'],
                    ['label' => 'TEMPLEWIN', 'value' => 't3', 'color' => 'green']
                )
            ),
            'radioaticompany' => array(
                'name' => 'radioaticompany',
                'label' => 'List of Companies',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'SUPERFAB INC.', 'value' => 'c0', 'color' => 'green'],
                    ['label' => 'TGRAF INC.', 'value' => 'c1', 'color' => 'green'],
                    ['label' => 'AMERICAN TECHNOLOGIES, INC.', 'value' => 'c2', 'color' => 'green'],
                    ['label' => 'D.V.I. SOLUTIONS (PHILIPPINES) INC.', 'value' => 'c3', 'color' => 'green'],
                    ['label' => 'Public Format', 'value' => 'c4', 'color' => 'green']

                )
            ),

            'radiotechlabcomp' => array(
                'name' => 'radiotechlabcomp',
                'label' => 'Select: ',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Technolab', 'value' => 'c0', 'color' => 'green'],
                    ['label' => 'LabSolution', 'value' => 'c1', 'color' => 'green']
                )
            ),
            'radiovatfilter' => array(
                'name' => 'vatfilter',
                'label' => 'Vat',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Vat', 'value' => 'vat', 'color' => 'teal'],
                    ['label' => 'NVat', 'value' => 'nvat', 'color' => 'teal'],
                    ['label' => 'All', 'value' => 'all', 'color' => 'teal']
                )
            ),
            'radioitemsort' => array(
                'name' => 'itemsort',
                'label' => 'Sort by',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Barcode', 'value' => 'barcode', 'color' => 'teal'],
                    ['label' => 'Itemname', 'value' => 'itemname', 'color' => 'teal']
                )
            ),
            'radiolayoutformat' => array(
                'name' => 'layoutformat',
                'label' => 'Format',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Warehouse with balance only', 'value' => '1', 'color' => 'teal'],
                    ['label' => 'All Warehouse', 'value' => '0', 'color' => 'teal']
                )
            ),
            'radiosorting' => array(
                'name' => 'sorting',
                'label' => 'Sort by',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Ascending', 'value' => 'ASC', 'color' => 'teal'],
                    ['label' => 'Descending', 'value' => 'DESC', 'color' => 'teal']
                )
            ),
            'certifby' => array(
                'name' => 'certifby',
                'type' => 'input',
                'label' => 'Certified by',
                'class' => 'cscertifyby sbccsenablealways',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'tmpref' => array(
                'name' => 'tmpref',
                'type' => 'input',
                'label' => 'IRF No.',
                'class' => 'cstmpref',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'prepared' => array(
                'name' => 'prepared',
                'type' => 'input',
                'label' => 'Prepared by',
                'class' => 'csprepared sbccsenablealways',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'audited' => array(
                'name' => 'audited',
                'type' => 'input',
                'label' => 'Audited by',
                'class' => 'csaudited sbccsenablealways',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'approved' => array(
                'name' => 'approved',
                'type' => 'input',
                'label' => 'Approved by',
                'class' => 'csapproved sbccsenablealways',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'approved2' => array(
                'name' => 'approved2',
                'type' => 'input',
                'label' => 'Approved by',
                'class' => 'csapproved2',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'received' => array(
                'name' => 'received',
                'type' => 'input',
                'label' => 'Received by',
                'class' => 'csreceived',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'issued' => array(
                'name' => 'issued',
                'type' => 'input',
                'label' => 'Issued by',
                'class' => 'csissued',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'receivedate' => array(
                'name' => 'receivedate',
                'type' => 'date',
                'label' => 'Received Date',
                'class' => 'csreceivedate',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'checked' => array(
                'name' => 'checked',
                'type' => 'input',
                'label' => 'Checked by',
                'class' => 'cschecked sbccsenablealways',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'checked2' => array(
                'name' => 'checked2',
                'type' => 'input',
                'label' => 'Checked by',
                'class' => 'cschecked2',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'noted' => array(
                'name' => 'noted',
                'type' => 'input',
                'label' => 'Noted by',
                'class' => 'csnoted sbccsenablealways',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'delivered' => array(
                'name' => 'delivered',
                'type' => 'input',
                'label' => 'Delivered by',
                'class' => 'csdelivered sbccsenablealways',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'courier' => array(
                'name' => 'courier',
                'type' => 'input',
                'label' => 'Courier/Forwarder',
                'class' => 'cscourier',
                'lookupclass' => 'lookup_logistic_courier',
                'action' => 'lookupforwarder',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'forwarder' => array(
                'name' => 'forwarder',
                'type' => 'lookup',
                'label' => 'Forwarder',
                'class' => 'csforwarder sbccsreadonly',
                'lookupclass' => 'lookupforwarder',
                'action' => 'lookupforwarder',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'payor' => array(
                'name' => 'payor',
                'type' => 'input',
                'label' => 'Payor',
                'class' => 'cspayor sbccsenablealways',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'position' => array(
                'name' => 'position',
                'type' => 'input',
                'label' => 'Position',
                'class' => 'csposition',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'position2' => array(
                'name' => 'position2',
                'type' => 'input',
                'label' => 'Position',
                'class' => 'csposition2',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'position3' => array(
                'name' => 'position3',
                'type' => 'input',
                'label' => 'Position',
                'class' => 'csposition3',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),

            'requested' => array(
                'name' => 'requested',
                'type' => 'input',
                'label' => 'Requested by',
                'class' => 'csrequested',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'start' => array(
                'name' => 'start',
                'type' => 'date',
                'label' => 'Start Date',
                'class' => 'csstart',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'enddate' => array(
                'name' => 'enddate',
                'type' => 'date',
                'label' => 'End Date',
                'class' => 'csenddate',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'end' => array(
                'name' => 'end',
                'type' => 'date',
                'label' => 'End Date',
                'class' => 'csend',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'expiry1' => array(
                'name' => 'expiry1',
                'type' => 'date',
                'label' => 'Expiry Date 1',
                'class' => 'csexpiry1',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'expiry2' => array(
                'name' => 'expiry2',
                'type' => 'date',
                'label' => 'Expiry Date 2',
                'class' => 'csexpiry2',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'vesse' => array(
                'name' => 'vesselstatus',
                'type' => 'lookup',
                'label' => 'Status',
                'class' => 'csvesselstatus',
                'class' => 'csvesselstatus sbccsreadonly',
                'lookupclass' => 'lookupvesselstatus',
                'action' => 'lookupvesselstatus',
                'style' => $this->style,
                'required' => false,
                'readonly' => true,
            ),
            'oicname' => array(
                'name' => 'oicname',
                'type' => 'lookup',
                'label' => 'OIC',
                'class' => 'csoicname',
                'class' => 'csoicname sbccsreadonly',
                'lookupclass' => 'lookupvessel_OIC',
                'action' => 'lookupvessel_OIC',
                'style' => $this->style,
                'required' => false,
                'readonly' => true,
            ),
            'cleardate' => array(
                'name' => 'cleardate',
                'type' => 'date',
                'label' => 'Clear Date',
                'class' => 'cscleardate',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'createby' => array(
                'name' => 'createby',
                'type' => 'lookup',
                'label' => 'Create by:',
                'class' => 'cscreateby',
                'lookupclass' => 'lookupcreateby',
                'action' => 'lookupcreateby',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'opincentive' => array(
                'name' => 'opincentive',
                'type' => 'input',
                'label' => 'Operator Incentive',
                'class' => 'csopincentive',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            // GLEN 08.29.2020
            // FOR TESTING PURPOSE DELETE LATER
            'area' => array(
                'name' => 'area',
                'type' => 'lookup',
                'label' => 'Area',
                'class' => 'csarea',
                'lookupclass' => 'area',
                'action' => 'lookuparea',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'province' => array(
                'name' => 'province',
                'type' => 'lookup',
                'label' => 'Province',
                'class' => 'csprovince',
                'lookupclass' => 'province',
                'action' => 'lookupprovince',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'addedparams' => ['area', 'region']
            ),
            'region' => array(
                'name' => 'region',
                'type' => 'lookup',
                'label' => 'Region',
                'class' => 'csregion',
                'lookupclass' => 'region',
                'action' => 'lookupregion',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'addedparams' => ['area']
            ),
            'year' => array(
                'name' => 'year',
                'type' => 'input',
                'label' => 'Year',
                'class' => 'csyear',
                'readonly' => false,
                'style' => $this->style,
                'required' => true
            ),
            'byear' => array(
                'name' => 'byear',
                'type' => 'input',
                'label' => 'Year',
                'class' => 'csyear',
                'readonly' => false,
                'style' => $this->style,
                'required' => true
            ),
            'month' => array(
                'name' => 'month',
                'type' => 'input',
                'label' => 'Month',
                'class' => 'csmonth',
                'readonly' => false,
                'style' => $this->style,
                'required' => true
            ),
            'year2' => array(
                'name' => 'year2',
                'type' => 'input',
                'label' => 'Year',
                'class' => 'csyear',
                'readonly' => false,
                'style' => $this->style,
                'required' => true
            ),
            'month2' => array(
                'name' => 'month2',
                'type' => 'input',
                'label' => 'Month',
                'class' => 'csmonth sbccsenablealways',
                'readonly' => false,
                'style' => $this->style,
                'required' => true
            ),
            'dloantype' => array(
                'name' => 'dloantype',
                'type' => 'lookup',
                'label' => 'Loan Type',
                'labeldata' => 'code~codename',
                'class' => 'csloantype',
                'lookupclass' => 'lookuploantype',
                'action' => 'lookuploantype',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'divsion' => array(
                'name' => 'divsion',
                'type' => 'lookup',
                'label' => 'Group',
                'labeldata' => 'groupid~stockgrp',
                'class' => 'csdivision sbccsreadonly',
                'lookupclass' => 'lookupdivision',
                'action' => 'lookupdivision',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'brand' => array(
                'name' => 'brand',
                'type' => 'lookup',
                'label' => 'Brand',
                'labeldata' => 'brandid~brandname',
                'class' => 'csbrand sbccsreadonly',
                'lookupclass' => 'lookupbrand',
                'action' => 'lookupbrand',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'brandid' => array(
                'name' => 'brand',
                'type' => 'hidden',
                'class' => 'csbrand sbccsreadonly',
                'lookupclass' => 'lookupbrand',
                'action' => 'lookupbrand',
            ),
            'brandname' => array(
                'name' => 'brandname',
                'type' => 'lookup',
                'label' => 'Brand',
                'class' => 'csbrand sbccsreadonly',
                'lookupclass' => 'lookupbrand',
                'action' => 'lookupbrand',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'boxno' => array(
                'name' => 'boxno',
                'type' => 'lookup',
                'label' => 'BOX NO.',
                'class' => 'csboxno sbccsreadonly',
                'lookupclass' => 'lookupboxno',
                'action' => 'lookupboxno',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'class' => array(
                'name' => 'class',
                'type' => 'lookup',
                'label' => 'Class',
                'labeldata' => 'classid~classic',
                'class' => 'csclass sbccsreadonly',
                'lookupclass' => 'lookupclass',
                'action' => 'lookupclass',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'classid' => array(
                'name' => 'class',
                'type' => 'hidden',
                'class' => 'csclass sbccsreadonly',
                'lookupclass' => 'lookupclass_stock',
                'action' => 'lookupclass',
            ),
            'classname' => array(
                'name' => 'classname',
                'type' => 'lookup',
                'label' => 'Class',
                'class' => 'csclass sbccsreadonly',
                'lookupclass' => 'lookupclass_stock',
                'action' => 'lookupclass',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'model' => array(
                'name' => 'model',
                'type' => 'lookup',
                'label' => 'Model',
                'labeldata' => 'modelid~modelname',
                'class' => 'csmodel sbccsreadonly',
                'lookupclass' => 'lookupmodel',
                'action' => 'lookupmodel',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'modelid' => array(
                'name' => 'model',
                'type' => 'hidden',
                'class' => 'csmodel sbccsreadonly',
                'lookupclass' => 'lookupmodel_stock',
                'action' => 'lookupmodel',
            ),
            'modelname' => array(
                'name' => 'modelname',
                'type' => 'lookup',
                'label' => 'Model',
                'class' => 'csmodel sbccsreadonly',
                'lookupclass' => 'lookupmodel_stock',
                'action' => 'lookupmodel',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'category' => array(
                'name' => 'category',
                'type' => 'lookup',
                'label' => 'Category',
                'labeldata' => 'categoryid~categoryname',
                'class' => 'cscategory sbccsreadonly',
                'lookupclass' => 'lookupcategory',
                'action' => 'lookupcategory',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'categoryid' => array(
                'name' => 'category',
                'type' => 'hidden',
                'class' => 'cscategory sbccsreadonly',
                'lookupclass' => 'lookupcategory_stock',
                'action' => 'lookupcategory',
            ),
            'categoryname' => array(
                'name' => 'categoryname',
                'type' => 'lookup',
                'label' => 'Category',
                'class' => 'cscategory sbccsreadonly',
                'lookupclass' => 'lookupcategory_stock',
                'action' => 'lookupcategory',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'customercategory' => array(
                'name' => 'category_name',
                'type' => 'lookup',
                'label' => 'Cust-Category',
                'class' => 'cscategory_name sbccsreadonly',
                'lookupclass' => 'lookupcustcategory',
                'action' => 'lookupcustcategory',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'transtyperr' => array(
                'name' => 'transtyperr',
                'type' => 'lookup',
                'label' => 'Transaction Type',
                'class' => 'cscategory sbccsreadonly',
                'lookupclass' => 'lookuptranstyperr',
                'action' => 'lookuptranstyperr',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),
            'itemcategoryname' => array(
                'name' => 'itemcategoryname',
                'type' => 'lookup',
                'label' => 'Item Category',
                'class' => 'cscategory sbccsreadonly',
                'lookupclass' => 'lookupcategory_stock',
                'action' => 'lookupcategory',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'part' => array(
                'name' => 'part',
                'type' => 'lookup',
                'label' => 'Part',
                'labeldata' => 'partid~partname',
                'class' => 'cspart sbccsreadonly',
                'lookupclass' => 'lookuppart',
                'action' => 'lookuppart',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'partid' => array(
                'name' => 'part',
                'type' => 'hidden',
                'class' => 'cspart sbccsreadonly',
                'lookupclass' => 'lookuppart_stock',
                'action' => 'lookuppart',
            ),
            'partname' => array(
                'name' => 'partname',
                'type' => 'lookup',
                'label' => 'Part',
                'class' => 'cspart sbccsreadonly',
                'lookupclass' => 'lookuppart_stock',
                'action' => 'lookuppart',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'partno' => array(
                'name' => 'partno',
                'type' => 'input',
                'label' => 'SKU/Part No.',
                'class' => 'cspartno',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'frompart' => array(
                'name' => 'frompart',
                'type' => 'input',
                'label' => 'From',
                'class' => 'csfrompart',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'topart' => array(
                'name' => 'topart',
                'type' => 'input',
                'label' => 'To',
                'class' => 'cstopart',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'subcode' => array(
                'name' => 'subcode',
                'type' => 'input',
                'label' => 'Old SKU',
                'class' => 'cssubcode',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'othcode' => array(
                'name' => 'othcode',
                'type' => 'input',
                'label' => 'Other Barcode',
                'class' => 'csothcode',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'packaging' => array(
                'name' => 'packaging',
                'type' => 'input',
                'label' => 'Compatible Parts',
                'class' => 'cspartno',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'radiorepitemstock' => array(
                'name' => 'itemstock',
                'label' => 'Item Stock',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'With Balance', 'value' => '(1)', 'color' => 'orange'],
                    ['label' => 'Without Balance', 'value' => '(0)', 'color' => 'orange'],
                    ['label' => 'None', 'value' => '(0,1)', 'color' => 'orange']
                )
            ),
            'radiorepamountformat' => array(
                'name' => 'amountformat',
                'label' => 'Amount Format',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Show Selling Price', 'value' => 'isamt', 'color' => 'orange'],
                    ['label' => 'Show Latest Cost', 'value' => 'rrcost', 'color' => 'orange'],
                    ['label' => 'None', 'value' => 'none', 'color' => 'orange']
                )
            ),

            'reportusers' => array(
                'name' => 'reportusers',
                'type' => 'lookup',
                'label' => 'User',
                'labeldata' => 'userid~username',
                'class' => 'csusers sbccsreadonly',
                'lookupclass' => 'lookupusers',
                'action' => 'lookupusers',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'approvers' => array(
                'name' => 'approvers',
                'type' => 'lookup',
                'label' => 'Approver',
                'labeldata' => 'clientid~approver',
                'class' => 'csusers sbccsreadonly',
                'lookupclass' => 'lookupapprovers',
                'action' => 'lookupapprovers',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'appprovername1' => array(
                'name' => 'appprovername1',
                'type' => 'input',
                'label' => 'Approver',
                'class' => 'csemphead sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'appprovername2' => array(
                'name' => 'appprovername2',
                'type' => 'input',
                'label' => 'Approver',
                'class' => 'csemphead sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),


            'costcenter' => array(
                'name' => 'costcenter',
                'type' => 'lookup',
                'label' => 'Project',
                'labeldata' => 'code~name',
                'class' => 'cscostcenter sbccsreadonly',
                'lookupclass' => 'lookupcostcenter',
                'action' => 'lookupcostcenter',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'userlevel' => array(
                'name' => 'userlevel',
                'type' => 'lookup',
                'label' => 'User Level',
                'class' => 'csusers sbccsreadonly',
                'lookupclass' => 'lookuplevel',
                'action' => 'lookupusers',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'clientstatus' => array(
                'name' => 'status',
                'type' => 'lookup',
                'label' => 'Customer Status',
                'class' => 'csclientstatus sbccsreadonly',
                'lookupclass' => 'lookupclientstatus',
                'action' => 'lookupclientstatus',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'stat' => array(
                'name' => 'stat',
                'type' => 'input',
                'label' => 'Status',
                'class' => 'csstat',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'reqstat' => array(
                'name' => 'reqstat',
                'type' => 'lookup',
                'label' => 'Status',
                'class' => 'csreqstat',
                'lookupclass' => 'lookupreqstatus',
                'action' => 'lookupreqstatus',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'zipcode' => array(
                'name' => 'zipcode',
                'type' => 'input',
                'label' => 'Zipcode',
                'class' => 'cszipcode',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'territory' => array(
                'name' => 'territory',
                'type' => 'cinput',
                'label' => 'Territory',
                'class' => 'csterritory',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'crtype' => array(
                'name' => 'crtype',
                'type' => 'lookup',
                'label' => 'Credit Days Based On',
                'class' => 'cscrtype sbccsreadonly',
                'lookupclass' => 'lookupcrtype',
                'action' => 'lookupclientcrtype',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'crdays' => array(
                'name' => 'crdays',
                'type' => 'cinput',
                'label' => 'Credit Days',
                'class' => 'cscrdays ',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'dagentname' => array(
                'name' => 'dagentname',
                'type' => 'lookup',
                'label' => 'Agent',
                'labeldata' => 'agent~agentname',
                'class' => 'csdagentname sbccsreadonly',
                'lookupclass' => 'agent',
                'action' => 'lookupclient',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'agentname' => array(
                'name' => 'agentname',
                'type' => 'lookup',
                'label' => 'Agent',
                'class' => 'csagentname sbccsreadonly',
                'lookupclass' => 'agent',
                'action' => 'lookupclient',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'helpername' => array(
                'name' => 'helpername',
                'type' => 'lookup',
                'label' => 'Helper',
                'class' => 'cshelpername sbccsreadonly',
                'lookupclass' => 'helper',
                'action' => 'lookupclient',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'dsalesacct' => array(
                'name' => 'dsalesacct',
                'type' => 'lookup',
                'label' => 'Account',
                'labeldata' => 'rev~acnoname',
                'class' => 'csdsalesacct sbccsreadonly',
                'lookupclass' => 'SA',
                'action' => 'lookupcontra',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'daracct' => array(
                'name' => 'daracct',
                'type' => 'lookup',
                'label' => 'AR Account',
                'labeldata' => 'ass~assetname',
                'class' => 'csdsalesacct sbccsreadonly',
                'lookupclass' => 'AR',
                'action' => 'lookupcontra',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'pricegroup' => array(
                'name' => 'class',
                'type' => 'lookup',
                'label' => 'Price Group',
                'class' => 'cspricegroup sbccsreadonly',
                'lookupclass' => 'lookuppricegroup',
                'action' => 'lookuppricegroup',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'bstyle' => array(
                'name' => 'bstyle',
                'type' => 'input',
                'label' => 'Business Style',
                'class' => 'csbstyle',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'category' => array(
                'name' => 'category',
                'type' => 'lookup',
                'label' => 'Category',
                'class' => 'csdivision sbccsreadonly',
                'lookupclass' => 'lookupcategoryledger',
                'action' => 'lookupcategory',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'dparentcode' => array(
                'name' => 'dparentcode',
                'type' => 'lookup',
                'label' => 'Parent Code',
                'labeldata' => 'grpcode~parentname',
                'class' => 'csdparentcode sbccsreadonly',
                'lookupclass' => 'parentcode',
                'action' => 'lookupclient',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'minimum' => array(
                'name' => 'minimum',
                'type' => 'input',
                'label' => 'Minimum',
                'class' => 'csminimum',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'maximum' => array(
                'name' => 'maximum',
                'type' => 'input',
                'label' => 'Maximum',
                'class' => 'csmaximum',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'expiryday' => array(
                'name' => 'expiryday',
                'type' => 'input',
                'label' => 'Days of Expiry',
                'class' => 'csexpiryday',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'critical' => array(
                'name' => 'critical',
                'type' => 'input',
                'label' => 'Critical',
                'class' => 'cscritical',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'reorder' => array(
                'name' => 'reorder',
                'type' => 'input',
                'label' => 'Reorder',
                'class' => 'csreorder',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'body' => array(
                'name' => 'body',
                'type' => 'lookup',
                'label' => 'Body',
                'class' => 'csbody',
                'lookupclass' => 'lookupbody',
                'action' => 'lookupbody',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'subgroup' => array(
                'name' => 'subgroup',
                'type' => 'lookup',
                'label' => 'Sub Group',
                'class' => 'cssubgroup',
                'lookupclass' => 'lookupsubgroup',
                'action' => 'lookupsubgroup',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'serialno' => array(
                'name' => 'serialno',
                'type' => 'cinput',
                'label' => 'Serial No.',
                'class' => 'cssubgroup',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'sizeid' => array(
                'name' => 'sizeid',
                'type' => 'lookup',
                'label' => 'Size',
                'class' => 'cssize',
                'lookupclass' => 'lookupsize',
                'action' => 'lookupsize',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'color' => array(
                'name' => 'color',
                'type' => 'lookup',
                'label' => 'Color',
                'class' => 'cssize',
                'lookupclass' => 'lookupcoloritem',
                'action' => 'lookupcoloritem',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'colorstype' => array(
                'name' => 'colorstype',
                'type' => 'lookup',
                'label' => 'Color Type',
                'class' => 'cssize',
                'lookupclass' => 'colorstype',
                'action' => 'lookupcoloritem',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'subcat' => array(
                'name' => 'subcat',
                'type' => 'lookup',
                'label' => 'Sub-Category',
                'class' => 'cssubcat',
                'lookupclass' => 'lookupsubcatitem',
                'action' => 'lookupsubcatitem',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'subcatname' => array(
                'name' => 'subcatname',
                'type' => 'lookup',
                'label' => 'Sub-Category',
                'class' => 'cssubcatname sbccsreadonly',
                'lookupclass' => 'lookupsubcatitemstockcard',
                'action' => 'lookupsubcatitemstockcard',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'addedparams' => ['category']
            ),
            'dasset' => array(
                'name' => 'dasset',
                'type' => 'lookup',
                'label' => 'Asset',
                'labeldata' => 'asset~assetname',
                'class' => 'cscontra sbccsreadonly',
                'lookupclass' => 'asset',
                'action' => 'lookupcoa',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'dliability' => array(
                'name' => 'dliability',
                'type' => 'lookup',
                'label' => 'Liability',
                'labeldata' => 'liability~liabilityname',
                'class' => 'cscontra sbccsreadonly',
                'lookupclass' => 'liability',
                'action' => 'lookupcoa',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'drevenue' => array(
                'name' => 'drevenue',
                'type' => 'lookup',
                'label' => 'Revenue',
                'labeldata' => 'revenue~revenuename',
                'class' => 'cscontra sbccsreadonly',
                'lookupclass' => 'revenue',
                'action' => 'lookupcoa',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'dsalesreturn' => array(
                'name' => 'dsalesreturn',
                'type' => 'lookup',
                'label' => 'Sales Return',
                'labeldata' => 'salesreturn~salesreturnname',
                'class' => 'cscontra sbccsreadonly',
                'lookupclass' => 'salesreturn',
                'action' => 'lookupcoa',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'dexpense' => array(
                'name' => 'dexpense',
                'type' => 'lookup',
                'label' => 'Expense',
                'labeldata' => 'expense~expensename',
                'class' => 'cscontra sbccsreadonly',
                'lookupclass' => 'expense',
                'action' => 'lookupcoa',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isvat' => array(
                'name' => 'isvat',
                'type' => 'checkbox',
                'label' => 'Vatable',
                'class' => 'csisvat',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isimport' => array(
                'name' => 'isimport',
                'type' => 'checkbox',
                'label' => 'Imported',
                'class' => 'csisimport',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'israwmat' => array(
                'name' => 'israwmat',
                'type' => 'checkbox',
                'label' => 'Raw Material',
                'class' => 'israwmat',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'fg_isfinishedgood' => array(
                'name' => 'fg_isfinishedgood',
                'type' => 'checkbox',
                'label' => 'Finished Good',
                'class' => 'csfg_isfinishedgood',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'fg_isequipmenttool' => array(
                'name' => 'fg_isequipmenttool',
                'type' => 'checkbox',
                'label' => 'Equipment Tool',
                'class' => 'csfg_isequipmenttool',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isofficesupplies' => array(
                'name' => 'isofficesupplies',
                'type' => 'checkbox',
                'label' => 'Office Supplies',
                'class' => 'csfg_isequipmenttool',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'groupid' => array(
                'name' => 'groupid',
                'type' => 'lookup',
                'label' => 'Group',
                'class' => 'csdivision sbccsreadonly',
                'lookupclass' => 'lookupdivision',
                'action' => 'lookupdivision',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'stock_groupid' => array(
                'name' => 'groupid',
                'type' => 'hidden',
                'class' => 'csdgroupid sbccsreadonly',
                'lookupclass' => 'lookupgroup',
                'action' => 'lookupdivision',
            ),
            'stock_groupname' => array(
                'name' => 'stock_groupname',
                'type' => 'lookup',
                'label' => 'Group',
                'class' => 'csdgroupid sbccsreadonly',
                'lookupclass' => 'lookupgroup_stock',
                'action' => 'lookupdivision',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'stockgrp' => array(
                'name' => 'stockgrp',
                'type' => 'lookup',
                'label' => 'Group',
                'class' => 'csdgroupid sbccsreadonly',
                'lookupclass' => 'lookupgroup',
                'action' => 'lookupdivision',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'disc' => array(
                'name' => 'disc',
                'type' => 'input',
                'label' => 'Discount',
                'class' => 'csdisc',
                'readonly' => true,
                'style' => $this->style,
                'required' => true,
                'maxlength' => 20
            ),
            'ontrip' => array(
                'name' => 'ontrip',
                'type' => 'lookup',
                'label' => 'Logs Type',
                'class' => 'csontrip sbccsreadonly',
                'lookupclass' => 'lookupontrip',
                'action' => 'lookupontrip',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            // PRICES FOR STOCK CARD
            'amt' => array(
                'name' => 'amt',
                'type' => 'input',
                'label' => 'Retail Price (R)',
                'class' => 'csamt',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'amt2' => array(
                'name' => 'amt2',
                'type' => 'input',
                'label' => 'Wholesale Price (W)',
                'class' => 'csamt2',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'famt' => array(
                'name' => 'famt',
                'type' => 'input',
                'label' => 'Price A (A)',
                'class' => 'csamt',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'amt4' => array(
                'name' => 'amt4',
                'type' => 'input',
                'label' => 'Price B (B)',
                'class' => 'csamt4',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'amt5' => array(
                'name' => 'amt5',
                'type' => 'input',
                'label' => 'Price C (C)',
                'class' => 'csamt5',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'amt6' => array(
                'name' => 'amt6',
                'type' => 'input',
                'label' => 'Price D (D)',
                'class' => 'csamt6',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'amt7' => array(
                'name' => 'amt7',
                'type' => 'input',
                'label' => 'Price E (E)',
                'class' => 'csamt7',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'amt8' => array(
                'name' => 'amt8',
                'type' => 'input',
                'label' => 'Price F (F)',
                'class' => 'csamt8',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'amt9' => array(
                'name' => 'amt9',
                'type' => 'input',
                'label' => 'Price G (G)',
                'class' => 'csamt9',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'depre' => array(
                'name' => 'depre',
                'type' => 'input',
                'label' => 'Basis',
                'class' => 'csdepre',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'depreyrs' => array(
                'name' => 'depreyrs',
                'type' => 'input',
                'label' => 'Yr/s',
                'class' => 'csdepreyrs',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'saleprice' => array(
                'name' => 'saleprice',
                'type' => 'input',
                'label' => 'saleprice',
                'class' => 'cssaleprice',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'foramt' => array(
                'name' => 'foramt',
                'type' => 'input',
                'label' => 'Foreign Amount',
                'class' => 'csamtforamt',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'markup' => array(
                'name' => 'markup',
                'type' => 'input',
                'label' => 'Markup',
                'class' => 'csmarkup',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'totalmarkup' => array(
                'name' => 'totalmarkup',
                'type' => 'input',
                'label' => 'Total Markup',
                'class' => 'cstotalmarkup sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'totalweight' => array(
                'name' => 'totalweight',
                'type' => 'input',
                'label' => 'Weight',
                'class' => 'cstotalweight sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'totalcharges' => array(
                'name' => 'totalcharges',
                'type' => 'input',
                'label' => 'Total Charges',
                'class' => 'cstotalcharges sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'disc' => array(
                'name' => 'disc',
                'type' => 'input',
                'label' => 'Discount R (R)',
                'class' => 'csdisc',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'disc2' => array(
                'name' => 'disc2',
                'type' => 'input',
                'label' => 'Discount W (W)',
                'class' => 'csdisc2',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'disc3' => array(
                'name' => 'disc3',
                'type' => 'input',
                'label' => 'Discount A (A)',
                'class' => 'csdisc3',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'disc4' => array(
                'name' => 'disc4',
                'type' => 'input',
                'label' => 'Discount B (B)',
                'class' => 'csdisc4',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'disc5' => array(
                'name' => 'disc5',
                'type' => 'input',
                'label' => 'Discount C (C)',
                'class' => 'csdisc5',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'disc6' => array(
                'name' => 'disc6',
                'type' => 'input',
                'label' => 'Discount D (D)',
                'class' => 'csdisc6',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'disc7' => array(
                'name' => 'disc7',
                'type' => 'input',
                'label' => 'Discount E (E)',
                'class' => 'csdisc7',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'disc8' => array(
                'name' => 'disc8',
                'type' => 'input',
                'label' => 'Discount F (F)',
                'class' => 'csdisc8',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'disc9' => array(
                'name' => 'disc9',
                'type' => 'input',
                'label' => 'Discount G (G)',
                'class' => 'csdisc9',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'quota' => array(
                'name' => 'quota',
                'type' => 'input',
                'label' => 'Quota',
                'class' => 'csquota',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'comm' => array(
                'name' => 'comm',
                'type' => 'input',
                'label' => 'Incentives %',
                'class' => 'cscomm',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'commamt' => array(
                'name' => 'commamt',
                'type' => 'input',
                'label' => 'Commission',
                'class' => 'cscommamt',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'commvat' => array(
                'name' => 'commvat',
                'type' => 'input',
                'label' => 'VAT of Commission',
                'class' => 'cscommvat',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'netcomm' => array(
                'name' => 'netcomm',
                'type' => 'input',
                'label' => 'Net',
                'class' => 'csnetcomm sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'agrelease' => array(
                'name' => 'agrelease',
                'type' => 'lookup',
                'label' => 'Release date',
                'class' => 'csagrelease sbccsreadonly',
                'lookupclass' => 'agrelease',
                'action' => 'lookupagrelease',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),
            'radiocustreporttype' => array(
                'name' => 'reporttype',
                'label' => 'Prepared',
                'type' => 'radio',
                'options' => array(
                    ["label" => "Accounts Receivable", "value" => "ar", 'color' => 'red'],
                    ["label" => "Accounts Payable", "value" => "ap", 'color' => 'red'],
                    ["label" => "Postdated Checks", "value" => "pdc", 'color' => 'red'],
                    ["label" => "Return Checks", "value" => "rc", 'color' => 'red'],
                    ["label" => "Inventory", "value" => "stock", 'color' => 'red']
                )
            ),
            'loc' => array(
                'name' => 'loc',
                'type' => 'lookup',
                'label' => 'Location',
                'class' => 'csloc sbccsreadonly',
                'lookupclass' => 'lookuploc',
                'action' => 'lookuploc',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'ishold' => array(
                'name' => 'ishold',
                'type' => 'checkbox',
                'label' => 'Hold',
                'class' => 'csishold',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isdepartment' => array(
                'name' => 'isdepartment',
                'type' => 'checkbox',
                'label' => 'Department',
                'class' => 'csisdepartment',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isadmin' => array(
                'name' => 'isadmin',
                'type' => 'checkbox',
                'label' => 'Administrator',
                'class' => 'csisadmin',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'uv_ischecker' => array(
                'name' => 'uv_ischecker',
                'type' => 'checkbox',
                'label' => 'Checker',
                'class' => 'csuv_ischecker',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'uv_ispicker' => array(
                'name' => 'uv_ispicker',
                'type' => 'checkbox',
                'label' => 'Picker',
                'class' => 'csuv_ispicker',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isoverride' => array(
                'name' => 'isoverride',
                'type' => 'checkbox',
                'label' => 'Overriding',
                'class' => 'csisoverride',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'dewt' => array(
                'name' => 'dewt',
                'type' => 'lookup',
                'label' => 'EWT Rate',
                'labeldata' => 'ewt~ewtrate',
                'class' => 'csewt sbccsreadonly',
                'lookupclass' => 'ewt',
                'action' => 'lookupewt',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'dexcess' => array(
                'name' => 'dexcess',
                'type' => 'lookup',
                'label' => 'Excise Tax',
                'labeldata' => 'excess~excessrate',
                'class' => 'csexcess sbccsreadonly',
                'lookupclass' => 'excess',
                'action' => 'lookupewt',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'dmoduledoc' => array(
                'name' => 'dmoduledoc',
                'type' => 'lookup',
                'label' => 'Module',
                'labeldata' => 'moduledoc~modulename',
                'class' => 'csmodulelist sbccsreadonly',
                'lookupclass' => 'modulelist',
                'action' => 'lookupmodulelist',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'moduledesc' => array(
                'name' => 'moduledesc',
                'type' => 'lookup',
                'label' => 'Module',
                'class' => 'csmodulelist sbccsreadonly',
                'lookupclass' => 'barcodeassigning',
                'action' => 'lookupmodulelist',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'username' => array(
                'name' => 'username',
                'type' => 'lookup',
                'label' => 'User',
                'class' => 'csuserid sbccsreadonly',
                'lookupclass' => 'getusername',
                'action' => 'lookupusers',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'approvedate' => array(
                'name' => 'approvedate',
                'type' => 'datetime',
                'label' => 'Approve Date',
                'class' => 'csapprovedate sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'dmenulist'    => array(
                'name'        => 'dmenulist',
                'type'        => 'lookup',
                'label'       => 'Module',
                'labeldata'   => 'code~modulename',
                'class'       => 'csdmenulist sbccsreadonly',
                'lookupclass' => 'lookupmenulist',
                'action'      => 'lookupmenulist',
                'readonly'    => true,
                'style'       => $this->style,
                'required'    => true
            ),
            'daccesslist'    => array(
                'name'        => 'daccesslist',
                'type'        => 'lookup',
                'label'       => 'Access',
                'labeldata'   => 'accesscode~accessname',
                'class'       => 'csdmenulist sbccsreadonly',
                'lookupclass' => 'lookupaccesslist',
                'action'      => 'lookupaccesslist',
                'readonly'    => true,
                'style'       => $this->style,
                'required'    => true
            ),
            'dcategory' => array(
                'name' => 'dcategory',
                'type' => 'lookup',
                'label' => 'Category',
                'labeldata' => 'category~categoryname',
                'class' => 'cscategory sbccsreadonly',
                'lookupclass' => 'categoryledger',
                'action' => 'lookupcategory',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'dparentdept' => array(
                'name' => 'dparentdept',
                'type' => 'lookup',
                'label' => 'Parent Department',
                'labeldata' => 'department~rem1',
                'class' => 'csdparentdept sbccsreadonly',
                'lookupclass' => 'lookupparentdept',
                'action' => 'lookupclient',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'ddeanhead' => array(
                'name' => 'ddeanhead',
                'type' => 'lookup',
                'label' => 'Dean Head',
                'labeldata' => 'code~rem2',
                'class' => 'csdparentdept sbccsreadonly',
                'lookupclass' => 'lookupdean',
                'action' => 'lookupdean',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'intclient' => array(
                'name' => 'intclient',
                'type' => 'input',
                'label' => 'Order No.',
                'class' => 'cssoa',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),

            'wysiwygrem' => array(
                'name' => 'rem',
                'type' => 'wysiwyg',
                'label' => 'CONCERN',
                'class' => 'csrem',
                'readonly' => true,
                'style' => $this->style,
                'height' => '20rem',
                'required' => true
            ),


            'poterms' => array(
                'name' => 'rem',
                'type' => 'poterms',
                'label' => 'PO Terms',
                'class' => 'cspoterms',
                'readonly' => true,
                'style' => $this->style,
                'height' => '20rem',
                'required' => true
            ),

            'invoice' => array(
                'name' => 'yourref',
                'type' => 'lookup',
                'label' => 'Invoice',
                'class' => 'csinvoice sbccsreadonly',
                'lookupclass' => 'getinvoice',
                'action' => 'lookupinvoice',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'forexid' => array(
                'name' => 'forexid',
                'type' => 'hidden',
                'class' => 'csforexid sbccsreadonly',
                'lookupclass' => 'lookupcur',
                'action' => 'lookupcur',
            ),

            'dcur' => array(
                'name' => 'cur',
                'type' => 'lookup',
                'label' => 'Cur',
                'class' => 'cscur sbccsreadonly',
                'lookupclass' => 'lookupcur',
                'action' => 'lookupcur',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'charge1' => array(
                'name' => 'charge1',
                'type' => 'input',
                'label' => 'Retainer Fee',
                'class' => 'cscharge1',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isnonbdo' => array(
                'name' => 'isnonbdo',
                'type' => 'checkbox',
                'label' => 'Isnonbdo',
                'class' => 'csisnonbdo',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            // HMS
            'roomtype' => array(
                'name' => 'roomtype',
                'type' => 'input',
                'label' => 'Room Type',
                'class' => 'csroomtype',
                'readonly' => false,
                'style' => $this->style,
                'required' => true
            ),
            'inactive' => array(
                'name' => 'inactive',
                'type' => 'checkbox',
                'label' => 'Inactive',
                'class' => 'csinactive',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'issmoking' => array(
                'name' => 'issmoking',
                'type' => 'checkbox',
                'label' => 'Smoking',
                'class' => 'csissmoking',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'selectprefix' => array(
                'name' => 'selectprefix',
                'label' => 'Select prefix',
                'type' => 'qselect',
                'readonly' => false,
                'style' => $this->style,
                'options' => array()
            ),
            'searchby' => array(
                'name' => 'searchby',
                'label' => 'Search by',
                'type' => 'qselect',
                'readonly' => false,
                'options' => array(
                    ['label' => '', 'value' => ''],
                    ['label' => 'Class', 'value' => 'class'],
                    ['label' => 'Model', 'value' => 'model'],
                    ['label' => 'Brand', 'value' => 'brand']
                )
            ),
            'sjtype' => array(
                'name' => 'sjtype',
                'label' => 'Transaction Type',
                'type' => 'qselect',
                'readonly' => false,
                'options' => array(
                    ['label' => '', 'value' => ''],
                    ['label' => 'Sales Journal Dealer', 'value' => 'SD'],
                    ['label' => 'Sales Journal Branch', 'value' => 'SE'],
                    ['label' => 'Sales Journal Online', 'value' => 'SF'],
                    ['label' => 'Special Parts Request', 'value' => 'SH']
                )
            ),
            'sortby' => array(
                'name' => 'sortby',
                'label' => 'Sort by',
                'type' => 'qselect',
                'readonly' => false,
                'style' => $this->style,
                'options' => array(
                    ['label' => '', 'value' => '']
                )
            ),
            'statrem' => array(
                'name' => 'statrem',
                'label' => 'Status Remarks',
                'type' => 'qselect',
                'readonly' => false,
                'style' => $this->style,
                'options' => array(
                    ['label' => '', 'value' => '']
                )
            ),
            'msggroup' => array(
                'name' => 'msggroup',
                'label' => 'Select Group',
                'type' => 'qselect',
                'readonly' => false,
                'options' => array(
                    ['label' => 'ADMIN', 'value' => 'ADMIN'],
                    ['label' => 'EMPLOYEE', 'value' => 'EMPLOYEE'],
                    ['label' => 'STUDENT', 'value' => 'STUDENT'],
                )
            ),
            'roomcategory' => array(
                'name' => 'category',
                'label' => 'Category',
                'type' => 'qselect',
                'readonly' => false,
                'options' => array(
                    ['label' => 'DAILY', 'value' => 'DAILY'],
                    ['label' => 'HOURLY', 'value' => 'HOURLY'],
                )
            ),
            //add for grade entry options 2021.09.20
            'ehprint' => array(
                'name' => 'ehprint',
                'label' => 'Select Group',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'By Student', 'value' => 'ehstudent', 'color' => 'red'],
                    ['label' => 'By Component', 'value' => 'ehcomponent', 'color' => 'red'],
                )
            ),
            'eiassessment' => array(
                'name' => 'eiassessment',
                'label' => 'Assessment',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'With Assessment', 'value' => 'withassess', 'color' => 'red'],
                    ['label' => 'Without Assessment', 'value' => 'withoutassess', 'color' => 'red'],
                )
            ),
            'eischedule' => array(
                'name' => 'eischedule',
                'label' => 'Schedule',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'With Schedule', 'value' => 'withsched', 'color' => 'red'],
                    ['label' => 'Without Schedule', 'value' => 'withoutsched', 'color' => 'red'],
                )
            ),
            'eiwithbooks' => array(
                'name' => 'eiwithbooks',
                'label' => 'With Books',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'With Books', 'value' => 'withbooks', 'color' => 'red'],
                    ['label' => 'Books Only', 'value' => 'booksonly', 'color' => 'red'],
                    ['label' => 'Assessment Only', 'value' => 'assessmentonly', 'color' => 'red'],
                )
            ),
            'maxadult' => array(
                'name' => 'maxadult',
                'type' => 'input',
                'label' => 'No. of PAX',
                'class' => 'csmaxadult',
                'readonly' => false,
                'style' => $this->style,
                'required' => true
            ),
            'beds' => array(
                'name' => 'beds',
                'type' => 'input',
                'label' => 'No. of Beds',
                'class' => 'csmaxadult',
                'readonly' => false,
                'style' => $this->style,
                'required' => true
            ),
            'additional' => array(
                'name' => 'additional',
                'type' => 'input',
                'label' => 'Extra Pax Rate',
                'class' => 'csmadditional',
                'readonly' => false,
                'style' => $this->style,
                'required' => true
            ),
            // HMS

            //Student other info - en_studentinfo table
            'studentid' => array(
                'name' => 'studentid',
                'type' => 'input',
                'label' => 'Student ID',
                'class' => 'csstudentid',
                'readonly' => true,
                'style' => $this->style,
                'required' => true,
                'maxlength' => 20
            ),
            'isold' => array(
                'name' => 'isold',
                'type' => 'checkbox',
                'label' => 'Old',
                'class' => 'csisold',
                'readonly' => false,
                'style' => $this->style,
                'required' => true
            ),
            'isnew' => array(
                'name' => 'isnew',
                'type' => 'checkbox',
                'label' => 'New',
                'class' => 'csisnew',
                'readonly' => false,
                'style' => $this->style,
                'required' => true
            ),
            'isforeign' => array(
                'name' => 'isforeign',
                'type' => 'checkbox',
                'label' => 'Foreign',
                'class' => 'csisforeign',
                'readonly' => false,
                'style' => $this->style,
                'required' => true
            ),
            'isadddrop' => array(
                'name' => 'isadddrop',
                'type' => 'checkbox',
                'label' => 'Add/Drop',
                'class' => 'csisadddrop',
                'readonly' => false,
                'style' => $this->style,
                'required' => true
            ),
            'iscrossenrollee' => array(
                'name' => 'iscrossenrollee',
                'type' => 'checkbox',
                'label' => 'Cross Enrollee',
                'class' => 'csiscrossenrollee',
                'readonly' => false,
                'style' => $this->style,
                'required' => true
            ),
            'istransferee' => array(
                'name' => 'istransferee',
                'type' => 'checkbox',
                'label' => 'Transferee',
                'class' => 'csistransferee',
                'readonly' => false,
                'style' => $this->style,
                'required' => true
            ),
            'islateenrollee' => array(
                'name' => 'islateenrollee',
                'type' => 'checkbox',
                'label' => 'Late Enrollee',
                'class' => 'csislateenrollee',
                'readonly' => false,
                'style' => $this->style,
                'required' => true
            ),
            'radioregular' => array(
                'name' => 'radioregular',
                'type' => 'qradio',
                'items' => [
                    'isregular' => ['val' => 'R', 'label' => 'Regular', 'color' => 'teal'],
                    'isirregular' => ['val' => 'I', 'label' => 'Irregular', 'color' => 'orange']
                ]
            ),
            'radiostudent' => array(
                'name' => 'radiostudent',
                'type' => 'qradio',
                'items' => [
                    'isnew' => ['val' => 'N', 'label' => 'New', 'color' => 'teal'],
                    'isold' => ['val' => 'O', 'label' => 'Old', 'color' => 'orange']
                ]
            ),

            'fname' => array(
                'name' => 'fname',
                'type' => 'input',
                'label' => 'First Name',
                'class' => 'csfname',
                'readonly' => false,
                'style' => $this->style,
                'required' => true,
                'maxlength' => 100
            ),
            'mname' => array(
                'name' => 'mname',
                'type' => 'input',
                'label' => 'Middle Name',
                'class' => 'csmname',
                'readonly' => false,
                'style' => $this->style,
                'required' => true,
                'maxlength' => 100
            ),
            'lname' => array(
                'name' => 'lname',
                'type' => 'input',
                'label' => 'Last Name',
                'class' => 'cslname',
                'readonly' => false,
                'style' => $this->style,
                'required' => true,
                'maxlength' => 100
            ),
            'chinesename' => array(
                'name' => 'chinesename',
                'type' => 'input',
                'label' => 'Chinese Name',
                'class' => 'cschinesename',
                'readonly' => false,
                'style' => $this->style,
                'required' => true,
                'maxlength' => 100
            ),
            'lcoursecode' => array(
                'name' => 'coursecode',
                'type' => 'lookup',
                'label' => 'Course',
                'class' => 'cscurriculum sbccsreadonly',
                'lookupclass' => 'lookupcoursecode',
                'action' => 'lookupcoursecode',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
            ),
            'course' => array(
                'name' => 'course',
                'type' => 'lookup',
                'label' => 'Course',
                'class' => 'cscourse sbccsreadonly',
                'lookupclass' => 'lookupcourse',
                'action' => 'lookupcourse',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            //2021.09.20
            'ehstudentlookup' => array(
                'name' => 'ehstudentlookup',
                'type' => 'lookup',
                'label' => 'Student List',
                'class' => 'csehstudent',
                'lookupclass' => 'lookupehstudentlist',
                'action' => 'lookupehstudentlist',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'addedparams' => ['code']
            ),
            'ehcomponentlookup' => array(
                'name' => 'ehcomponentlookup',
                'type' => 'lookup',
                'label' => 'Component List',
                'class' => 'csehcomponent',
                'lookupclass' => 'lookupehcomponentlist',
                'action' => 'lookupehcomponentlist',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'addedparams' => ['code']
            ),
            'ehtopiclookup' => array(
                'name' => 'ehtopiclookup',
                'type' => 'lookup',
                'label' => 'Topic List',
                'class' => 'csehtopic',
                'lookupclass' => 'lookupehtopiclist',
                'action' => 'lookupehtopiclist',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'addedparams' => ['gccode', 'clientid', 'code']
            ),
            'ehquarterlookup' => array(
                'name' => 'ehquarterlookup',
                'type' => 'lookup',
                'label' => 'Quarter',
                'class' => 'csehquarter',
                'lookupclass' => 'lookupehquarter',
                'action' => 'lookupehquarter',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'emattendancelookup' => array(
                'name' => 'emattendancelookup',
                'type' => 'lookup',
                'label' => 'Attendance Type',
                'class' => 'csemattendance',
                'lookupclass' => 'lookupemattendance',
                'action' => 'lookupemattendance',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'coursename' => array(
                'name' => 'coursename',
                'type' => 'input',
                'label' => 'Course Name',
                'class' => 'cscoursename',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'branch' => array(
                'name' => 'branch',
                'type' => 'input',
                'label' => 'Branch',
                'class' => 'csbranch',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'gender' => array(
                'name' => 'gender',
                'type' => 'lookup',
                'label' => 'Gender',
                'class' => 'csgender sbccsreadonly',
                'lookupclass' => 'lookupgender',
                'action' => 'lookupgender',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'civilstatus' => array(
                'name' => 'civilstatus',
                'type' => 'lookup',
                'label' => 'Civil Status',
                'class' => 'cscivilstatus sbccsreadonly',
                'lookupclass' => 'lookupcivilstatus',
                'action' => 'lookupcivilstatus',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'bday' => array(
                'name' => 'bday',
                'type' => 'date',
                'label' => 'Birthday',
                'class' => 'csbday',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'bplace' => array(
                'name' => 'bplace',
                'type' => 'input',
                'label' => 'Birth Place',
                'class' => 'csbplace',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'nationality' => array(
                'name' => 'nationality',
                'type' => 'lookup',
                'label' => 'Nationality',
                'class' => 'csnationality',
                'lookupclass' => 'lookupnationality',
                'action' => 'lookupnationality',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'extramural' => array(
                'name' => 'extramural',
                'type' => 'input',
                'label' => 'Extramural',
                'class' => 'csextramural',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'haddr' => array(
                'name' => 'haddr',
                'type' => 'input',
                'label' => 'Home Address',
                'class' => 'cshaddr',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'htel' => array(
                'name' => 'htel',
                'type' => 'input',
                'label' => 'Home Tel. No.',
                'class' => 'cshtel',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'baddr' => array(
                'name' => 'baddr',
                'type' => 'input',
                'label' => 'Business Address',
                'class' => 'csbaddr',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'btel' => array(
                'name' => 'btel',
                'type' => 'input',
                'label' => 'Business Tel. No.',
                'class' => 'csbtel',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'guardian' => array(
                'name' => 'guardian',
                'type' => 'input',
                'label' => 'Guardian',
                'class' => 'csguardian',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'gtel' => array(
                'name' => 'gtel',
                'type' => 'input',
                'label' => 'Guardian Tel. No.',
                'class' => 'csgtel',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'elementary' => array(
                'name' => 'elementary',
                'type' => 'input',
                'label' => 'Elementary',
                'class' => 'cselementary',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'eyear' => array(
                'name' => 'eyear',
                'type' => 'input',
                'label' => 'Year',
                'class' => 'cseyear',
                'readonly' => false,
                'style' => 'width:70px;whiteSpace: normal;min-width:70px;',
                'required' => false,
                'maxlength' => 10
            ),
            'highschool' => array(
                'name' => 'highschool',
                'type' => 'input',
                'label' => 'Highschool',
                'class' => 'cshighschool',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'hyear' => array(
                'name' => 'hyear',
                'type' => 'input',
                'label' => 'Year',
                'class' => 'cshyear',
                'readonly' => false,
                'style' => 'width:70px;whiteSpace: normal;min-width:70px;',
                'required' => false,
                'maxlength' => 10
            ),
            'college' => array(
                'name' => 'college',
                'type' => 'input',
                'label' => 'College',
                'class' => 'cscollege',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'cyear' => array(
                'name' => 'cyear',
                'type' => 'input',
                'label' => 'Year',
                'class' => 'cscyear',
                'readonly' => false,
                'style' => 'width:70px;whiteSpace: normal;min-width:70px;',
                'required' => false,
                'maxlength' => 10
            ),
            'postschool' => array(
                'name' => 'postschool',
                'type' => 'input',
                'label' => 'Post School',
                'class' => 'cspostschool',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'pyear' => array(
                'name' => 'pyear',
                'type' => 'input',
                'label' => 'Year',
                'class' => 'cspyear',
                'readonly' => false,
                'style' => 'width:70px;whiteSpace: normal;min-width:70px;',
                'required' => false,
                'maxlength' => 10
            ),
            'company' => array(
                'name' => 'company',
                'type' => 'cinput',
                'label' => 'Company',
                'class' => 'cscompany',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'compcode' => array(
                'name' => 'compcode',
                'type' => 'lookup',
                'label' => 'Company Code',
                'class' => 'sbccsreadonly',
                'lookupclass' => 'lookuprxscompany',
                'action' => 'lookuprxscompany',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
            ),
            'blocklotroxas' => array(
                'name' => 'blocklotroxas',
                'type' => 'lookup',
                'label' => 'Blocklot Roxas',
                'class' => 'sbccsreadonly',
                'lookupclass' => 'lookupblocklotroxas',
                'action' => 'lookupblocklotroxas',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'addedparams' => ['compcode', 'subprojcode'],
            ),
            'amenity' => array(
                'name' => 'amenity',
                'type' => 'lookup',
                'label' => 'Amenity',
                'class' => 'sbccsreadonly',
                'lookupclass' => 'lookupamenityroxascode',
                'action' => 'lookupamenityroxascode',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'addedparams' => ['compcode'],
            ),
            'subamenity' => array(
                'name' => 'subamenity',
                'type' => 'lookup',
                'label' => 'Sub Amenity',
                'class' => 'sbccsreadonly',
                'lookupclass' => 'lookupsubamenityroxascode',
                'action' => 'lookupsubamenityroxascode',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'addedparams' => ['compcode', 'amenitycode'],
            ),


            'accountant' => array(
                'name' => 'accountant',
                'type' => 'input',
                'label' => 'Position',
                'class' => 'csaccountant',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'subjectcode' => array(
                'name' => 'subjectcode',
                'type' => 'lookup',
                'label' => 'Code',
                'class' => 'cssubjectcode sbccsenablealways',
                'lookupclass' => 'subjectcode',
                'action' => 'lookupdocno',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'subjectname' => array(
                'name' => 'subjectname',
                'type' => 'input',
                'label' => 'Description',
                'class' => 'cssubjectname',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'units' => array(
                'name' => 'units',
                'type' => 'input',
                'label' => 'Units',
                'class' => 'csunits',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'lecture' => array(
                'name' => 'lecture',
                'type' => 'input',
                'label' => 'Lecture',
                'class' => 'cslecture',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'laboratory' => array(
                'name' => 'laboratory',
                'type' => 'input',
                'label' => 'Laboratory',
                'class' => 'cslaboratory',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'hours' => array(
                'name' => 'hours',
                'type' => 'input',
                'label' => 'Hours',
                'class' => 'cshours',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'dlevel' => array(
                'name' => 'dlevel',
                'type' => 'lookup',
                'label' => 'Level',
                'class' => 'cslevel',
                'lookupclass' => 'lookuplevel',
                'action' => 'lookuplevel',
                'readonly' => false,
                'style' => $this->style,
                'required' => true
            ),
            'dcoreq' => array(
                'name' => 'dcoreq',
                'type' => 'lookup',
                'label' => 'Co-Requisite',
                'class' => 'cscoreq',
                'lookupclass' => 'lookupcoreq',
                'action' => 'lookupcoreq',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'dprereq1' => array(
                'name' => 'dprereq1',
                'type' => 'lookup',
                'label' => 'Pre-Requisite 1',
                'class' => 'csprereq1',
                'lookupclass' => 'lookupprereq1',
                'action' => 'lookupprereq1',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'dprereq2' => array(
                'name' => 'dprereq2',
                'type' => 'lookup',
                'label' => 'Pre-Requisite 2',
                'class' => 'csprereq2',
                'lookupclass' => 'lookupprereq2',
                'action' => 'lookupprereq2',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'dprereq3' => array(
                'name' => 'dprereq3',
                'type' => 'lookup',
                'label' => 'Pre-Requisite 3',
                'class' => 'csprereq3',
                'lookupclass' => 'lookupprereq3',
                'action' => 'lookupprereq3',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'dprereq4' => array(
                'name' => 'dprereq4',
                'type' => 'lookup',
                'label' => 'Pre-Requisite 4',
                'class' => 'csprereq4',
                'lookupclass' => 'lookupprereq4',
                'action' => 'lookupprereq4',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'period' => array(
                'name' => 'period',
                'type' => 'lookup',
                'label' => 'Period',
                'class' => 'csperiod sbccsreadonly',
                'lookupclass' => 'lookupperiod',
                'action' => 'lookupperiod',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'sy' => array(
                'name' => 'sy',
                'type' => 'lookup',
                'label' => 'School Year',
                'class' => 'cssy sbccsreadonly',
                'lookupclass' => 'lookupsy',
                'action' => 'lookupsy',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),
            'building' => array(
                'name' => 'building',
                'type' => 'input',
                'label' => 'Building Name',
                'class' => 'csbuilding',
                'readonly' => true,
                'style' => $this->style,
                'required' => true,
                'maxlength' => 100
            ),
            'floor' => array(
                'name' => 'floor',
                'type' => 'input',
                'label' => 'Floor',
                'class' => 'csfloor',
                'readonly' => true,
                'style' => $this->style,
                'required' => true,
                'maxlength' => 100
            ),
            'bldgname' => array(
                'name' => 'bldgname',
                'type' => 'input',
                'label' => 'Building Name',
                'class' => 'csbldgname',
                'readonly' => true,
                'style' => $this->style,
                'required' => true,
                'maxlength' => 100
            ),
            'bldgcode' => array(
                'name' => 'bldgcode',
                'type' => 'input',
                'label' => 'Building Code',
                'class' => 'csbldgcode',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),
            'room' => array(
                'name' => 'room',
                'type' => 'input',
                'label' => 'Room',
                'class' => 'csroom',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),
            'roomcode' => array(
                'name' => 'roomcode',
                'type' => 'lookup',
                'lookupclass' => 'lookuprooms2',
                'action' => 'lookuprooms2',
                'readonly' => true,
                'label' => 'Room Code',
                'class' => 'csroomcode sbccsreadonly',
                'style' => $this->style,
                'required' => false,
                'addedparams' => ['bldgid']
            ),
            'coursecode' => array(
                'name' => 'coursecode',
                'type' => 'input',
                'label' => 'Course',
                'class' => 'cscourse',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),
            'deptcode' => array(
                'name' => 'deptcode',
                'type' => 'lookup',
                'label' => 'Department Code',
                'style' => $this->style,
                'readonly' => true,
                'lookupclass' => 'coursedeptlookup',
                'action' => 'lookupdepartment'
            ),
            'deancode' => array(
                'name' => 'deancode',
                'type' => 'lookup',
                'label' => 'Dean Code',
                'style' => $this->style,
                'readonly' => true,
                'lookupclass' => 'inslookupdean',
                'action' => 'enlookupdean'
            ),
            'deanname' => array(
                'name' => 'deanname',
                'type' => 'input',
                'label' => 'Dean Name',
                'style' => $this->style,
                'readonly' => true,
                'lookupclass' => 'enlookupdean',
                'action' => 'enlookupdean'
            ),
            'curriculumcode' => array(
                'name' => 'curriculumcode',
                'type' => 'input',
                'label' => 'Curriculum',
                'class' => 'cscurriculum',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'curriculumname' => array(
                'name' => 'curriculumname',
                'type' => 'input',
                'label' => 'Curriculum Name',
                'class' => 'cscourse',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'curriculumdocno' => array(
                'name' => 'curriculumdocno',
                'type' => 'input',
                'label' => 'Curriculum Document',
                'class' => 'cscourse',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'assesscode' => array(
                'name' => 'assesscode',
                'type' => 'input',
                'label' => 'Assessment',
                'class' => 'csassessment',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'regcode' => array(
                'name' => 'regcode',
                'type' => 'input',
                'label' => 'Registration',
                'class' => 'csregistration',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'quartercode' => array(
                'name' => 'quartercode',
                'type' => 'lookup',
                'label' => 'Quarter',
                'class' => 'csquartercode',
                'lookupclass' => 'lookupquarter',
                'action' => 'lookupquarter',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'advisercode' => array(
                'name' => 'advisercode',
                'type' => 'lookup',
                'label' => 'Adviser',
                'class' => 'csinstructor sbccsreadonly',
                'lookupclass' => 'lookupadviser',
                'action' => 'lookupinstructor',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'advisername' => array(
                'name' => 'advisername',
                'type' => 'input',
                'label' => 'Adviser Name',
                'class' => 'csinstructorname',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'semester' => array(
                'name' => 'terms',
                'type' => 'lookup',
                'label' => 'Semester',
                'class' => 'cssemester sbccsreadonly',
                'lookupclass' => 'lookupsemester',
                'action' => 'lookupsemester',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'yr' => array(
                'name' => 'yr',
                'type' => 'lookup',
                'label' => 'Year',
                'class' => 'csyr',
                'lookupclass' => 'lookupyr',
                'action' => 'lookupyr',
                'readonly' => true,
                'style' => $this->style,
                'required' => true,
                'addedparams' => ['courseid']
            ),
            'section' => array(
                'name' => 'section',
                'type' => 'lookup',
                'label' => 'Section',
                'class' => 'cssection',
                'lookupclass' => 'lookupsection',
                'action' => 'lookupsection',
                'readonly' => true,
                'style' => $this->style,
                'required' => true,
                'addedparams' => ['courseid']
            ),
            //End of Student other info - en_studentinfo table

            // Curriculum
            'effectfromdate' => array(
                'name' => 'effectfromdate',
                'type' => 'date',
                'label' => 'Start Date',
                'class' => 'cseffectfromdate',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'effecttodate' => array(
                'name' => 'effecttodate',
                'type' => 'date',
                'label' => 'End Date',
                'class' => 'cseffecttodate',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'sumunits' => array(
                'name' => 'sumunits',
                'type' => 'textarea',
                'label' => 'Summary',
                'class' => 'cssumunits',
                'readonly' => true,
                'style' => '',
                'required' => false,
                'maxlength' => 200
            ),
            // End of Curriculum

            //INSTRUCTOR
            'department' => array(
                'name' => 'department',
                'type' => 'input',
                'label' => 'Department',
                'class' => 'csdept sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'rank' => array(
                'name' => 'rank',
                'type' => 'input',
                'label' => 'Rank',
                'class' => 'csrank',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 3
            ),
            'callname' => array(
                'name' => 'callname',
                'type' => 'input',
                'label' => 'Call Name',
                'class' => 'cscallname',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'levels' => array(
                'name' => 'levels',
                'type' => 'lookup',
                'label' => 'Level',
                'class' => 'cslevels',
                'lookupclass' => 'lookuplevel',
                'action' => 'lookuplevel',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'levelup' => array(
                'name' => 'levelup',
                'type' => 'lookup',
                'label' => 'Level Up',
                'class' => 'cslevelup',
                'lookupclass' => 'lookuplevelup',
                'action' => 'lookupcourse',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            //END OF INSTRUCTOR

            // HRIS,PAYROLL
            'empcode' => array(
                'name' => 'empcode',
                'type' => 'lookup',
                'label' => 'Employee Code',
                'class' => 'csEmployee sbccsreadonly',
                'lookupclass' => 'employee',
                'action' => 'lookupclient',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),

            'tempcode' => array(
                'name' => 'tempcode',
                'type' => 'lookup',
                'label' => 'To Employee Code',
                'class' => 'csEmployee sbccsreadonly',
                'lookupclass' => 'toemployee',
                'action' => 'lookupclient',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),
            'fempcode' => array(
                'name' => 'fempcode',
                'type' => 'lookup',
                'label' => 'From Employee Code',
                'class' => 'csEmployee sbccsreadonly',
                'lookupclass' => 'fromemployee',
                'action' => 'lookupclient',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),

            'empname' => array(
                'name' => 'empname',
                'type' => 'input',
                'label' => 'Employee Name',
                'class' => 'csempname sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'tempname' => array(
                'name' => 'tempname',
                'type' => 'input',
                'label' => 'Employee Name',
                'class' => 'csempname sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'fempname' => array(
                'name' => 'fempname',
                'type' => 'input',
                'label' => 'Employee Name',
                'class' => 'csempname sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'explanation' => array(
                'name' => 'explanation',
                'type' => 'textarea',
                'label' => 'Explanation',
                'class' => 'csexplanation',
                'readonly' => true,
                'style' => '',
                'required' => false,
                'maxlength' => 200
            ),
            'findings' => array(
                'name' => 'findings',
                'type' => 'textarea',
                'label' => 'Summary of Findings',
                'class' => 'csfindings',
                'readonly' => true,
                'style' => '',
                'required' => false,
                'maxlength' => 1000
            ),
            'comments' => array(
                'name' => 'comments',
                'type' => 'textarea',
                'label' => 'Comment',
                'class' => 'cscomment',
                'readonly' => true,
                'style' => '',
                'required' => false,
                'maxlength' => 200
            ),

            'tjobtitle' => array(
                'name' => 'tjobtitle',
                'type' => 'input',
                'label' => 'Job title',
                'class' => 'csjobtitle sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'fjobtitle' => array(
                'name' => 'fjobtitle',
                'type' => 'input',
                'label' => 'Job title',
                'class' => 'csjobtitle sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'purpose' => array(
                'name' => 'purpose',
                'type' => 'input',
                'label' => 'Purpose of Attending',
                'class' => 'cspurpose',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'purpose1' => array(
                'name' => 'purpose1',
                'type' => 'ctextarea',
                'label' => 'Purpose of Car Loan',
                'class' => 'cspurpose1',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 200
            ),
            'budget' => array(
                'name' => 'budget',
                'type' => 'input',
                'label' => 'Budget',
                'class' => 'csbudget',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'budgetreqno' => array(
                'name' => 'budgetreqno',
                'type' => 'input',
                'label' => 'Budget Request #',
                'class' => 'csbudgetreqno',
                'readonly' => false,
                'style' => $this->style,
                'required' => true,
                'maxlength' => 20,
                'addedparams' => ['purtype']
            ),
            'type' => array(
                'name' => 'type',
                'type' => 'lookup',
                'label' => 'Type',
                'class' => 'csType sbccsreadonly',
                'lookupclass' => 'lookuptrainingtype',
                'action' => 'lookuptrainingtype',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),
            'traintype' => array(
                'name' => 'type',
                'type' => 'lookup',
                'label' => 'Type',
                'class' => 'csType sbccsreadonly',
                'lookupclass' => 'lookuptrainingtype',
                'action' => 'lookuptrainingtype',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'title' => array(
                'name' => 'title',
                'type' => 'input',
                'label' => 'Title',
                'class' => 'cstype',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'venue' => array(
                'name' => 'venue',
                'type' => 'input',
                'label' => 'Venue',
                'class' => 'csvenue',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'hired' => array(
                'name' => 'hired',
                'type' => 'date',
                'label' => 'Date hired',
                'class' => 'cshired',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'lastdate' => array(
                'name' => 'lastdate',
                'type' => 'date',
                'label' => 'Last day of work',
                'class' => 'cslastdate',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'jobtitle' => array(
                'name' => 'jobtitle',
                'type' => 'input',
                'label' => 'Job title',
                'class' => 'csjobtitle',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'emphead' => array(
                'name' => 'emphead',
                'type' => 'lookup',
                'label' => 'Immediate Head',
                'class' => 'csemphead sbccsreadonly',
                'lookupclass' => 'emphead',
                'action' => 'lookupclient',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),
            'empheadname' => array(
                'name' => 'empheadname',
                'type' => 'input',
                'label' => 'Name',
                'class' => 'csempheadname sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'demphead' => array(
                'name' => 'demphead',
                'type' => 'input',
                'label' => 'Name',
                'class' => 'csemphead sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'cause' => array(
                'name' => 'cause',
                'type' => 'input',
                'label' => 'Cause of Separation',
                'class' => 'cscause',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'emplast' => array(
                'name' => 'emplast',
                'type' => 'input',
                'label' => 'Last Name',
                'class' => 'csemplast',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'empfirst' => array(
                'name' => 'empfirst',
                'type' => 'input',
                'label' => 'First Name',
                'class' => 'csempfirst',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'empmiddle' => array(
                'name' => 'empmiddle',
                'type' => 'input',
                'label' => 'Middle Name',
                'class' => 'csempmiddle',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'atype' => array(
                'name' => 'type',
                'type' => 'lookup',
                'label' => 'Type',
                'class' => 'cstype sbccsreadonly',
                'lookupclass' => 'lookupatype',
                'action' => 'lookupatype',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'jstatus' => array(
                'name' => 'jstatus',
                'type' => 'lookup',
                'label' => 'Status',
                'class' => 'csjstatus sbccsreadonly',
                'lookupclass' => 'lookupjstatus',
                'action' => 'lookupjstatus',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'appdate' => array(
                'name' => 'appdate',
                'type' => 'date',
                'label' => 'Date Applied',
                'class' => 'csappdate',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'idno' => array(
                'name' => 'idno',
                'type' => 'input',
                'label' => 'ID Barcode',
                'class' => 'csidno',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'city' => array(
                'name' => 'city',
                'type' => 'input',
                'label' => 'City/State',
                'class' => 'cscity',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'country' => array(
                'name' => 'country',
                'type' => 'input',
                'label' => 'Country',
                'class' => 'cscountry',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'age' => array(
                'name' => 'age',
                'type' => 'input',
                'label' => 'Age',
                'class' => 'csage sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 3
            ),
            'kgs' => array(
                'name' => 'kgs',
                'type' => 'input',
                'label' => 'Kgs',
                'class' => 'cskgs',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'child' => array(
                'name' => 'child',
                'type' => 'input',
                'label' => 'No.of Children',
                'class' => 'cschild',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 3
            ),
            'telno' => array(
                'name' => 'telno',
                'type' => 'input',
                'label' => 'Home No.',
                'class' => 'cstelno',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'mobileno' => array(
                'name' => 'mobileno',
                'type' => 'input',
                'label' => 'Mobile No.',
                'class' => 'csmobileno',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'mobile' => array(
                'name' => 'mobile',
                'type' => 'cinput',
                'label' => 'Owner Contact No.',
                'class' => 'csmobile',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'owner' => array(
                'name' => 'owner',
                'type' => 'cinput',
                'label' => 'Owner Name',
                'class' => 'csowner',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'prefix' => array(
                'name' => 'prefix',
                'type' => 'cinput',
                'label' => 'Prefix',
                'class' => 'csprefix',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 3
            ),
            'alias' => array(
                'name' => 'alias',
                'type' => 'input',
                'label' => 'Alias',
                'class' => 'csalias',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'citizenship' => array(
                'name' => 'citizenship',
                'type' => 'input',
                'label' => 'Citizenship',
                'class' => 'cscitizenship',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'religion' => array(
                'name' => 'religion',
                'type' => 'input',
                'label' => 'Religion',
                'class' => 'csreligion',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'maidname' => array(
                'name' => 'maidname',
                'type' => 'input',
                'label' => 'Maiden Name',
                'class' => 'csmaidname',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'empnoref' => array(
                'name' => 'empnoref',
                'type' => 'input',
                'label' => 'Old Employee No.',
                'class' => 'csempnoref',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'callsign' => array(
                'name' => 'callsign',
                'type' => 'input',
                'label' => 'Call Sign',
                'class' => 'cscallsign',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'mstatus' => array(
                'name' => 'status',
                'type' => 'lookup',
                'label' => 'Marital Status',
                'class' => 'csjmstatus sbccsreadonly',
                'lookupclass' => 'lookupmstatus',
                'action' => 'lookupcivilstatus',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'civilstat' => array(
                'name' => 'civilstat',
                'type' => 'lookup',
                'label' => 'Civil Status',
                'class' => 'csjcivilstat sbccsreadonly',
                'lookupclass' => 'lookupmstatus',
                'action' => 'lookupcivilstatus',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'remarks' => array(
                'name' => 'remarks',
                'type' => 'textarea',
                'label' => 'Remarks',
                'class' => 'csremarks',
                'readonly' => true,
                'style' => '',
                'required' => false,
                'maxlength' => 200
            ),
            'jobcode' => array(
                'name' => 'jobcode',
                'type' => 'lookup',
                'label' => 'Job Title',
                'class' => 'csjobtitle sbccsreadonly',
                'action' => 'lookupjobtitle',
                'lookupclass' => 'lookupjobtitle',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'relation' => array(
                'name' => 'relation',
                'type' => 'input',
                'label' => 'Relationship',
                'class' => 'csrelation ',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'jobdesc' => array(
                'name' => 'jobdesc',
                'type' => 'textarea',
                'label' => 'Job Desc',
                'class' => 'csjobdesc sbccsreadonly',
                'readonly' => true,
                'style' => '',
                'required' => true,
                'maxlength' => 200
            ),
            'mapp' => array(
                'name' => 'mapp',
                'type' => 'textarea',
                'label' => 'Mode of Application',
                'class' => 'csmapp',
                'readonly' => true,
                'style' => '',
                'required' => true,
                'maxlength' => 200
            ),
            'homeno' => array(
                'name' => 'homeno',
                'type' => 'input',
                'label' => 'Home No.',
                'class' => 'cshomeno',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'officeno' => array(
                'name' => 'officeno',
                'type' => 'input',
                'label' => 'Office No.',
                'class' => 'csofficeno',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'notes' => array(
                'name' => 'notes',
                'type' => 'input',
                'label' => 'Notes',
                'class' => 'csnotes',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 200
            ),
            'ext1' => array(
                'name' => 'ext1',
                'type' => 'input',
                'label' => 'Extention',
                'class' => 'csext',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'level' => array(
                'name' => 'level',
                'type' => 'lookup',
                'label' => 'Level',
                'class' => 'cslevel sbccsreadonly',
                'action' => 'lookupemplevel',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'tfaccount' => array(
                'name' => 'tfaccount',
                'type' => 'lookup',
                'label' => 'Account Code',
                'style' => $this->style,
                'readonly' => false,
                'lookupclass' => 'courseaccountlookup',
                'action' => 'lookupacno'
            ),
            'acnoname' => array(
                'name' => 'acnoname',
                'type' => 'input',
                'label' => 'Account Name',
                'style' => $this->style,
                'readonly' => false,
                'class' => 'csacnoname'
            ),
            'hacno' => array(
                'name' => 'hacno',
                'type' => 'input',
                'label' => 'Account #',
                'style' => $this->style,
                'readonly' => false,
                'class' => 'csacno'
            ),
            'ischinese' => array(
                'name' => 'ischinese',
                'type' => 'checkbox',
                'label' => 'Chinese',
                'class' => 'ischinese',
                'style' => $this->style,
                'readonly' => true,
                'required' => false
            ),
            'isdegree' => array(
                'name' => 'isdegree',
                'type' => 'checkbox',
                'label' => 'Degree',
                'class' => 'isdegree',
                'style' => $this->style,
                'readonly' => true,
                'required' => false
            ),
            'isundergraduate' => array(
                'name' => 'isundergraduate',
                'type' => 'checkbox',
                'label' => 'Under Graduate',
                'style' => $this->style,
                'class' => 'isundergraduate',
                'readonly' => true,
                'required' => false

            ),
            'idbarcode' => array(
                'name' => 'idbarcode',
                'type' => 'input',
                'label' => 'Biometric ID',
                'class' => 'csidbarcode',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'bankacct' => array(
                'name' => 'bankacct',
                'type' => 'input',
                'label' => 'Bank Account No.',
                'class' => 'csidbankacct',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'isactive' => array(
                'name' => 'isactive',
                'type' => 'checkbox',
                'label' => 'Active',
                'class' => 'csisactive',
                'readonly' => false,
                'style' => $this->style,
                'required' => true
            ),
            'atm' => array(
                'name' => 'atm',
                'type' => 'checkbox',
                'label' => 'ATM',
                'class' => 'csatm',
                'readonly' => false,
                'style' => $this->style,
                'required' => true
            ),
            'radioteu' => array(
                'name' => 'radioteu',
                'type' => 'qradio',
                'items' => [
                    'single' => ['val' => 'S', 'label' => 'Single', 'color' => 'teal'],
                    'married' => ['val' => 'M', 'label' => 'Married', 'color' => 'orange']
                ]
            ),
            'nodeps' => array(
                'name' => 'nodeps',
                'type' => 'input',
                'label' => 'No. of Dependents',
                'class' => 'csnodeps',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 3
            ),
            'blood' => array(
                'name' => 'blood',
                'type' => 'input',
                'label' => 'Blood Type',
                'class' => 'csblood',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'paymode' => array(
                'name' => 'paymode',
                'type' => 'lookup',
                'label' => 'Mode of Payment',
                'class' => 'cspaymode sbccsreadonly',
                'action' => 'lookuppaymode',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'paymodeemp' => array(
                'name' => 'paymodeemp',
                'type' => 'lookup',
                'label' => 'Mode of Payment',
                'class' => 'cspaymodeemp sbccsreadonly',
                'action' => 'lookuppaymodeemp',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'paymentdate' => array(
                'name' => 'paymentdate',
                'field' => 'paymentdate',
                'type' => 'date',
                'label' => 'Payment Date',
                'align' => 'text-left',
                'style' => 'width: 200px;whiteSpace: normal;min-width:20px;max-width:200px;',
                'readonly' => false
            ),
            'fullwordpaymode' => array(
                'name' => 'fullwordpaymode',
                'type' => 'input',
                'label' => 'Description',
                'class' => 'cspaymodename sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'emprate' => array(
                'name' => 'emprate',
                'type' => 'lookup',
                'label' => 'Area Rate',
                'class' => 'csemprate',
                'action' => 'lookupemprate sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'classrate' => array(
                'name' => 'classrate',
                'type' => 'input',
                'label' => 'Class Rate',
                'class' => 'csclassrate sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'leaserate' => array(
                'name' => 'leaserate',
                'type' => 'input',
                'label' => 'Lease Rate(per SQM)',
                'class' => 'csleaserate',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'acrate' => array(
                'name' => 'acrate',
                'type' => 'input',
                'label' => 'Aircon Rate(per SQM)',
                'class' => 'csacrate',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'cusarate' => array(
                'name' => 'cusarate',
                'type' => 'input',
                'label' => 'CUSA Rate(per SQM)',
                'class' => 'cscusarate',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'billtype' => array(
                'name' => 'billtype',
                'type' => 'lookup',
                'label' => 'Bill Type',
                'class' => 'csbilltype sbccsreadonly',
                'lookupclass' => 'lookup_billtype_mms',
                'action' => 'lookuprandom',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),


            'inspo' => array(
                'name' => 'inspo',
                'type' => 'lookup',
                'label' => 'TYPE',
                'class' => 'csinspos sbccsreadonly',
                'lookupclass' => 'lookup_inspos',
                'action' => 'lookuprandom',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),

            'rentcat' => array(
                'name' => 'rentcat',
                'type' => 'lookup',
                'label' => 'Rent Category',
                'class' => 'csrentcat sbccsreadonly',
                'lookupclass' => 'lookup_rentcat_mms',
                'action' => 'lookuprandom',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),
            'tenanttype' => array(
                'name' => 'tenanttype',
                'type' => 'lookup',
                'label' => 'Tenant Type',
                'class' => 'cstenanttype sbccsreadonly',
                'lookupclass' => 'lookup_tenanttype_mms',
                'action' => 'lookuprandom',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),
            'mcharge' => array(
                'name' => 'mcharge',
                'type' => 'input',
                'label' => 'Monthly Charge(if Rent Category is Daily Charge)',
                'class' => 'csmcharge',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'percentsales' => array(
                'name' => 'percentsales',
                'type' => 'input',
                'label' => 'Percentage Sales(% if Rent Category is %Sales)',
                'class' => 'cspercentsales',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'emulti' => array(
                'name' => 'emulti',
                'type' => 'input',
                'label' => 'Electricity Multiplier',
                'class' => 'csemulti',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'semulti' => array(
                'name' => 'semulti',
                'type' => 'input',
                'label' => 'S. Electricity Multiplier',
                'class' => 'cssemulti',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'wmulti' => array(
                'name' => 'wmulti',
                'type' => 'input',
                'label' => 'Water Multiplier',
                'class' => 'cswmulti',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'elecrate' => array(
                'name' => 'elecrate',
                'type' => 'input',
                'label' => 'Electricity Rate',
                'class' => 'cselecrate',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'selecrate' => array(
                'name' => 'selecrate',
                'type' => 'input',
                'label' => 'S. Electricity Rate',
                'class' => 'csselecrate',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'waterrate' => array(
                'name' => 'waterrate',
                'type' => 'input',
                'label' => 'Water Rate',
                'class' => 'cswaterrate',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'classification' => array(
                'name' => 'classification',
                'type' => 'lookup',
                'label' => 'Classification',
                'class' => 'csclassification',
                'lookupclass' => 'lookup_classification_mms',
                'action' => 'lookuprandom',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
            ),
            'msales' => array(
                'name' => 'msales',
                'type' => 'input',
                'label' => 'Monthly Sales(if Rent Category is %Sales)',
                'class' => 'csmsales',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'capacity' => array(
                'name' => 'capacity',
                'type' => 'input',
                'label' => 'Capacity in tons',
                'class' => 'cscapacity',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 25
            ),
            'rate' => array(
                'name' => 'rate',
                'type' => 'input',
                'label' => 'Rate',
                'class' => 'csrate',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'ratecategory' => array(
                'name' => 'ratecategory',
                'type' => 'lookup',
                'label' => 'Rate Category',
                'class' => 'csratecategory sbccsreadonly',
                'lookupclass' => 'lookup_ratecategory',
                'action' => 'lookup_ratecategory',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),
            'eratecatname' => array(
                'name' => 'eratecatname',
                'type' => 'lookup',
                'label' => 'Electricity Rate Category',
                'class' => 'cseratecatname sbccsreadonly',
                'lookupclass' => 'lookup_elect_ratecategory',
                'action' => 'lookup_ratecategory',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),
            'wratecatname' => array(
                'name' => 'wratecatname',
                'type' => 'lookup',
                'label' => 'Water Rate Category',
                'class' => 'cswratecatname sbccsreadonly',
                'lookupclass' => 'lookup_water_ratecategory',
                'action' => 'lookup_ratecategory',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),
            'supervisorcode' => array(
                'name' => 'supervisorcode',
                'type' => 'lookup',
                'label' => 'Supervisor Code',
                'class' => 'csemphead sbccsreadonly',
                'lookupclass' => 'supervisorcode',
                'action' => 'lookupclient',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),
            'supervisor' => array(
                'name' => 'supervisor',
                'type' => 'input',
                'label' => 'Supervisor',
                'class' => 'cssupervisor',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'isotapprover' => array(
                'name' => 'isotapprover',
                'type' => 'checkbox',
                'label' => 'OT Approver',
                'class' => 'csisotapprover',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'approvercode' => array(
                'name' => 'approvercode',
                'type' => 'lookup',
                'label' => 'Approver/HR Code',
                'class' => 'csemphead sbccsreadonly',
                'labeldata' => 'approvercode~approver',
                'lookupclass' => 'approvercode',
                'action' => 'lookupclient',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'otsupervisor' => array(
                'name' => 'otsupervisor',
                'type' => 'input',
                'label' => 'Supervisor',
                'class' => 'csemphead sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'emptype' => array(
                'name' => 'emptype',
                'type' => 'input',
                'label' => 'Type',
                'class' => 'csemptype sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'paygroup' => array(
                'name' => 'paygroup',
                'type' => 'input',
                'label' => 'Pay Group',
                'class' => 'cspaygroup sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'ddivname' => array(
                'name' => 'ddivname',
                'type' => 'lookup',
                'label' => 'Division',
                'labeldata' => 'division~divname',
                'class' => 'csdivision sbccsreadonly',
                'lookupclass' => 'lookupempdivision',
                'action' => 'lookupempdivision',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'divname' => array(
                'name' => 'divname',
                'type' => 'input',
                'label' => 'Division',
                'class' => 'csdivname sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'empdivname' => array(
                'name' => 'empdivname',
                'type' => 'lookup',
                'label' => 'Company',
                'class' => 'csempdivname sbccsreadonly',
                'lookupclass' => 'lookupempdivision',
                'action' => 'lookupempdivision',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'divrep' => array(
                'name' => 'divrep',
                'type' => 'lookup',
                'label' => 'Division',
                'labeldata' => 'divid~divname',
                'class' => 'csdivision sbccsreadonly',
                'lookupclass' => 'lookupempdivision',
                'action' => 'lookupempdivision',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'sectrep' => array(
                'name' => 'sectrep',
                'type' => 'lookup',
                'label' => 'Section',
                'labeldata' => 'sectid~sectname',
                'class' => 'cssect',
                'action' => 'lookupempsection',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'radioreportempstatus' => array(
                'name' => 'empstatus',
                'label' => 'Employee Status',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Active', 'value' => '(1)', 'color' => 'orange'],
                    ['label' => 'Resigned', 'value' => '(0)', 'color' => 'orange'],
                    ['label' => 'All', 'value' => '(0,1)', 'color' => 'orange']
                )
            ),
            'batchrep' => array(
                'name' => 'batchrep',
                'type' => 'lookup',
                'label' => 'Payroll Batch',
                'labeldata' => 'line~batch',
                'class' => 'csdivision sbccsreadonly',
                'lookupclass' => 'lookupbatchrep',
                'action' => 'lookupbatchrep',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'batch' => array(
                'name' => 'batch',
                'type' => 'lookup',
                'label' => 'Batch',
                'class' => 'csdept sbccsreadonly',
                'lookupclass' => 'lookupbatchrep',
                'action' => 'lookupbatchrep',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'joddivname' => array(
                'name' => 'joddivname',
                'type' => 'lookup',
                'label' => 'Division',
                'labeldata' => 'dcode~dname',
                'class' => 'csdivision sbccsreadonly',
                'lookupclass' => 'lookup_jodiv',
                'action' => 'lookupempdivision',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'deptname' => array(
                'name' => 'deptname',
                'type' => 'input',
                'label' => 'Department',
                'class' => 'csdept sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'dsectionname' => array(
                'name' => 'dsectionname',
                'type' => 'lookup',
                'label' => 'Section',
                'labeldata' => 'orgsection~sectname',
                'class' => 'cssect',
                'action' => 'lookupempsection',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'shiftcode' => array(
                'name' => 'shiftcode',
                'type' => 'lookup',
                'label' => 'Shift Code',
                'class' => 'csshiftcode',
                'action' => 'lookupshiftcode',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'shiftcode2' => array(
                'name' => 'shiftcode2',
                'type' => 'lookup',
                'label' => 'Shift Code To',
                'class' => 'csshiftcode',
                'action' => 'lookupshiftcode',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'sss' => array(
                'name' => 'sss',
                'type' => 'input',
                'label' => 'SSS#',
                'class' => 'cssssno',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'phic' => array(
                'name' => 'phic',
                'type' => 'input',
                'label' => 'Philhealth #',
                'class' => 'csphicno',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'hdmf' => array(
                'name' => 'hdmf',
                'type' => 'input',
                'label' => 'HDMF #',
                'class' => 'cshdmfno',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'chksss' => array(
                'name' => 'chksss',
                'type' => 'checkbox',
                'label' => '',
                'class' => 'cschksss',
                'readonly' => false,
                'style' => $this->style,
                'required' => true
            ),
            'chkphealth' => array(
                'name' => 'chkphealth',
                'type' => 'checkbox',
                'label' => '',
                'class' => 'cschkphealth',
                'readonly' => false,
                'style' => $this->style,
                'required' => true
            ),
            'chkpibig' => array(
                'name' => 'chkpibig',
                'type' => 'checkbox',
                'label' => '',
                'class' => 'cschkpibig',
                'readonly' => false,
                'style' => $this->style,
                'required' => true
            ),
            'chktin' => array(
                'name' => 'chktin',
                'type' => 'checkbox',
                'label' => '',
                'class' => 'cschktin',
                'readonly' => false,
                'style' => $this->style,
                'required' => true
            ),
            'lastbatch' => array(
                'name' => 'lastbatch',
                'type' => 'lookup',
                'label' => 'Last Payroll Batch#',
                'class' => 'cslastbatch sbccsreadonly',
                'action' => 'lookupempbatch',
                'lookupclass' => 'lookupempbatch',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'dyear' => array(
                'name' => 'dyear',
                'type' => 'input',
                'label' => 'Days/Year',
                'class' => 'csdyear',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'cola' => array(
                'name' => 'cola',
                'type' => 'input',
                'label' => 'COLA',
                'class' => 'cscola',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'mealdeduc' => array(
                'name' => 'mealdeduc',
                'type' => 'input',
                'label' => 'Meal Deduction',
                'class' => 'csmealdeduc',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'sssdef' => array(
                'name' => 'sssdef',
                'type' => 'input',
                'label' => 'SSS',
                'class' => 'cssssdef',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'philhdef' => array(
                'name' => 'philhdef',
                'type' => 'input',
                'label' => 'Philhealth',
                'class' => 'csphilhdef',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'pibigdef' => array(
                'name' => 'pibigdef',
                'type' => 'input',
                'label' => 'HDMF',
                'class' => 'cspibigdef',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'wtaxdef' => array(
                'name' => 'wtaxdef',
                'type' => 'input',
                'label' => 'W/tax',
                'class' => 'cswtaxdef',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'emprank' => array(
                'name' => 'emprank',
                'type' => 'input',
                'label' => 'Rank',
                'class' => 'csemprank sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'empstatus' => array(
                'name' => 'empstatus',
                'type' => 'input',
                'label' => 'Employment Status',
                'class' => 'csempstatus sbccsreadonly',
                'action' => 'empstatlookup',
                'lookupclass' => 'empstatlookup',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'emploc' => array(
                'name' => 'emploc',
                'type' => 'input',
                'label' => 'Location',
                'class' => 'csemploc sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'aplcode' => array(
                'name' => 'aplcode',
                'type' => 'input',
                'label' => 'Application Code',
                'class' => 'csaplcode sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'jgrade' => array(
                'name' => 'jgrade',
                'type' => 'input',
                'label' => 'Job Grade',
                'class' => 'csjgrade sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'agency' => array(
                'name' => 'agency',
                'type' => 'date',
                'label' => 'Agency',
                'class' => 'csagency',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'trainee' => array(
                'name' => 'trainee',
                'type' => 'date',
                'label' => 'Trainee',
                'class' => 'cstrainee',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'prob' => array(
                'name' => 'prob',
                'type' => 'date',
                'label' => 'Probation',
                'class' => 'csprob',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'probend' => array(
                'name' => 'probend',
                'type' => 'date',
                'label' => 'Probation',
                'class' => 'csprobend',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'resigned' => array(
                'name' => 'resigned',
                'type' => 'date',
                'label' => 'Resigned',
                'class' => 'csresigned',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'regular' => array(
                'name' => 'regular',
                'type' => 'date',
                'label' => 'Regular',
                'class' => 'csregular',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'irno' => array(
                'name' => 'irno',
                'type' => 'lookup',
                'label' => 'Ref Incident Report #',
                'class' => 'csrefx sbccsreadonly',
                'lookupclass' => 'incidentreportlookup',
                'action' => 'incidentreportlookup',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'irdesc' => array(
                'name' => 'irdesc',
                'type' => 'input',
                'label' => 'Incident Description',
                'class' => 'csirdesc sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'artcode' => array(
                'name' => 'artcode',
                'type' => 'lookup',
                'label' => 'Article',
                'class' => 'csartid sbccsreadonly',
                'lookupclass' => 'articlelookup',
                'action' => 'articlelookup',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'articlename' => array(
                'name' => 'articlename',
                'type' => 'input',
                'label' => 'Article Description',
                'class' => 'csarticlename sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'description' => array(
                'name' => 'description',
                'type' => 'input',
                'label' => 'Article Description',
                'class' => 'csdescription',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 200
            ),


            'chargedesc' => array(
                'name' => 'chargedesc',
                'type' => 'lookup',
                'label' => 'Description',
                'class' => 'csdesc sbccsreadonly',
                'lookupclass' => 'lookupcharge',
                'action' => 'lookupcharge',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'schedday' => array(
                'name' => 'schedday',
                'type' => 'input',
                'label' => 'Sched Days',
                'class' => 'csschedday',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'schedtime' => array(
                'name' => 'schedtime',
                'type' => 'input',
                'label' => 'Sched Time',
                'class' => 'csschedtime',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'sectioncode' => array(
                'name' => 'sectioncode',
                'type' => 'lookup',
                'label' => 'Section',
                'class' => 'csline sbccsreadonly',
                'lookupclass' => 'sectionlookup',
                'action' => 'sectionlookup',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'addedparams' => ['artcode']
            ),
            'sectionname' => array(
                'name' => 'sectionname',
                'type' => 'input',
                'label' => 'Section Description',
                'class' => 'cssectionname sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'divisionname' => array(
                'name' => 'divisionname',
                'type' => 'input',
                'label' => 'Division Description',
                'class' => 'csdivisionname sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'violationno' => array(
                'name' => 'violationno',
                'type' => 'lookup',
                'label' => '# Times Violated',
                'class' => 'csline sbccsreadonly',
                'lookupclass' => 'violationlookup',
                'action' => 'violationlookup',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'addedparams' => ['artcode', 'sectioncode']
            ),
            'penalty' => array(
                'name' => 'penalty',
                'type' => 'input',
                'label' => 'Penalty',
                'class' => 'cspenalty sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'penaltyamt' => array(
                'name' => 'penaltyamt',
                'type' => 'input',
                'label' => 'Penalty',
                'class' => 'cspenaltyamt',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'rebate' => array(
                'name' => 'rebate',
                'type' => 'input',
                'label' => 'Rebate',
                'class' => 'csrebate sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'numdays' => array(
                'name' => 'numdays',
                'type' => 'input',
                'label' => '# of Days',
                'class' => 'csnumdays sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'detail' => array(
                'name' => 'detail',
                'type' => 'input',
                'label' => 'Details',
                'class' => 'csdetail',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),

            'effectdate' => array(
                'name' => 'effectdate',
                'type' => 'date',
                'label' => 'Effectivity',
                'class' => 'cseffectivitydate',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'monthsno' => array(
                'name' => 'monthsno',
                'type' => 'input',
                'label' => 'Months #',
                'class' => 'csmonthsno',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 3
            ),
            'empno' => array(
                'name' => 'empno',
                'type' => 'input',
                'label' => 'Employee No.',
                'class' => 'csempno',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'idescription' => array(
                'name' => 'idescription',
                'type' => 'input',
                'label' => 'Incident Description',
                'class' => 'csempno',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'iplace' => array(
                'name' => 'iplace',
                'type' => 'input',
                'label' => 'Incident Place',
                'class' => 'csempno',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'idate' => array(
                'name' => 'idate',
                'type' => 'date',
                'label' => 'Incident Date',
                'class' => 'csempno',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'itime' => array(
                'name' => 'itime',
                'type' => 'time',
                'label' => 'Incident Time',
                'class' => 'csempno',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'itime1' => array(
                'name' => 'itime1',
                'type' => 'time',
                'label' => ' Time',
                'class' => 'cstime',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'itime2' => array(
                'name' => 'itime2',
                'type' => 'time',
                'label' => ' Time',
                'class' => 'cstime',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'itime3' => array(
                'name' => 'itime3',
                'type' => 'time',
                'label' => ' Time',
                'class' => 'cstime',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'itime4' => array(
                'name' => 'itime4',
                'type' => 'time',
                'label' => ' Time',
                'class' => 'cstime',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'itime5' => array(
                'name' => 'itime5',
                'type' => 'time',
                'label' => ' Time',
                'class' => 'cstime',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'itime6' => array(
                'name' => 'itime6',
                'type' => 'time',
                'label' => ' Time',
                'class' => 'cstime',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'itime7' => array(
                'name' => 'itime7',
                'type' => 'time',
                'label' => ' Time',
                'class' => 'cstime',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'itime8' => array(
                'name' => 'itime8',
                'type' => 'time',
                'label' => ' Time',
                'class' => 'cstime',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'itime9' => array(
                'name' => 'itime9',
                'type' => 'time',
                'label' => ' Time',
                'class' => 'cstime',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'itime10' => array(
                'name' => 'itime10',
                'type' => 'time',
                'label' => ' Time',
                'class' => 'cstime',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'idetails' => array(
                'name' => 'idetails',
                'type' => 'textarea',
                'label' => 'Incident Details',
                'class' => 'csempno',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 200
            ),
            'icomments' => array(
                'name' => 'icomments',
                'type' => 'textarea',
                'label' => 'Incident Comments',
                'class' => 'csempno',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 200
            ),
            'statdesc'  => array(
                'name'        => 'statdesc',
                'type'        => 'lookup',
                'label'       => 'Status Change',
                'class'       => 'csstatcode sbccsreadonly',
                'lookupclass' => 'statcodelookup',
                'action'      => 'statcodelookup',
                'readonly'    => true,
                'style'       => $this->style,
                'required'    => false
            ),
            'ftype'  => array(
                'name'        => 'ftype',
                'type'        => 'input',
                'label'       => 'From Type',
                'class'       => 'csftype sbccsreadonly',
                'readonly'    => true,
                'style'       => $this->style,
                'required'    => false
            ),
            'flevel'  => array(
                'name'        => 'flevel',
                'type'        => 'input',
                'label'       => 'From Level',
                'class'       => 'csflevel sbccsreadonly',
                'readonly'    => true,
                'style'       => $this->style,
                'required'    => false
            ),
            'fjobcode'  => array(
                'name'        => 'fjobcode',
                'type'        => 'input',
                'label'       => 'From Job Title',
                'class'       => 'csfjobcode sbccsreadonly',
                'readonly'    => true,
                'style'       => $this->style,
                'required'    => false
            ),
            'fjobname'  => array(
                'name'        => 'fjobname',
                'type'        => 'input',
                'label'       => 'From Job Title',
                'class'       => 'csfjobname sbccsreadonly',
                'readonly'    => true,
                'style'       => $this->style,
                'required'    => false
            ),
            'fempstatcode'  => array(
                'name'        => 'fempstatcode',
                'type'        => 'input',
                'label'       => 'From Emp. Status',
                'class'       => 'csffempstatcode sbccsreadonly',
                'readonly'    => true,
                'style'       => $this->style,
                'required'    => false
            ),
            'fempstatname'  => array(
                'name'        => 'fempstatname',
                'type'        => 'input',
                'label'       => 'From Emp. Status',
                'class'       => 'csffempstatcode sbccsreadonly',
                'readonly'    => true,
                'style'       => $this->style,
                'required'    => false
            ),
            'frank'  => array(
                'name'        => 'frank',
                'type'        => 'input',
                'label'       => 'From Rank',
                'class'       => 'csfrank sbccsreadonly',
                'readonly'    => true,
                'style'       => $this->style,
                'required'    => false
            ),
            'fjobgrade'  => array(
                'name'        => 'fjobgrade',
                'type'        => 'input',
                'label'       => 'From Job Grade',
                'class'       => 'csfjobgrade sbccsreadonly',
                'readonly'    => true,
                'style'       => $this->style,
                'required'    => false
            ),
            'fdeptcode'  => array(
                'name'        => 'fdeptcode',
                'type'        => 'input',
                'label'       => 'From Department',
                'class'       => 'csfdeptcode sbccsreadonly',
                'readonly'    => true,
                'style'       => $this->style,
                'required'    => false
            ),
            'fdeptname'  => array(
                'name'        => 'fdeptname',
                'type'        => 'input',
                'label'       => 'From Department',
                'class'       => 'csfdeptcode sbccsreadonly',
                'readonly'    => true,
                'style'       => $this->style,
                'required'    => false
            ),
            'flocation'  => array(
                'name'        => 'flocation',
                'type'        => 'input',
                'label'       => 'From Location',
                'class'       => 'csflocation sbccsreadonly',
                'readonly'    => true,
                'style'       => $this->style,
                'required'    => false
            ),
            'fpaymode'  => array(
                'name'        => 'fpaymode',
                'type'        => 'input',
                'label'       => 'From Mode of Payment',
                'class'       => 'csfpaymode sbccsreadonly',
                'readonly'    => true,
                'style'       => $this->style,
                'required'    => false
            ),
            'fpaygroup'  => array(
                'name'        => 'fpaygroup',
                'type'        => 'input',
                'label'       => 'From Pay Group',
                'class'       => 'csfpaygroup sbccsreadonly',
                'readonly'    => true,
                'style'       => $this->style,
                'required'    => false
            ),
            'fpaygroupname'  => array(
                'name'        => 'fpaygroupname',
                'type'        => 'input',
                'label'       => 'From Pay Group',
                'class'       => 'csfpaygroupname sbccsreadonly',
                'readonly'    => true,
                'style'       => $this->style,
                'required'    => false
            ),
            'fpayrate'  => array(
                'name'        => 'fpayrate',
                'type'        => 'input',
                'label'       => 'From Pay Rate',
                'class'       => 'csfpayrate sbccsreadonly',
                'readonly'    => true,
                'style'       => $this->style,
                'required'    => false
            ),
            'fallowrate'  => array(
                'name'        => 'fallowrate',
                'type'        => 'input',
                'label'       => 'From Allowance',
                'class'       => 'csfallowrate sbccsreadonly',
                'readonly'    => true,
                'style'       => $this->style,
                'required'    => false
            ),
            'fbasicrate'  => array(
                'name'        => 'fbasicrate',
                'type'        => 'input',
                'label'       => 'From Basic Salary',
                'class'       => 'csfbasicrate sbccsreadonly',
                'readonly'    => true,
                'style'       => $this->style,
                'required'    => false
            ),
            'fcola'  => array(
                'name'        => 'fcola',
                'type'        => 'input',
                'label'       => 'From COLA',
                'class'       => 'csfcola sbccsreadonly',
                'readonly'    => true,
                'style'       => $this->style,
                'required'    => false
            ),
            'fdivname'  => array(
                'name'        => 'fdivname',
                'type'        => 'input',
                'label'       => 'From Division',
                'class'       => 'csfdivname sbccsreadonly',
                'readonly'    => true,
                'style'       => $this->style,
                'required'    => false
            ),
            'fsectname'  => array(
                'name'        => 'fsectname',
                'type'        => 'input',
                'label'       => 'From Section',
                'class'       => 'csfsectname sbccsreadonly',
                'readonly'    => true,
                'style'       => $this->style,
                'required'    => false
            ),
            'tlevel'  => array(
                'name'        => 'tlevel',
                'type'        => 'lookup',
                'label'       => 'To Level',
                'class'       => 'cstlevel sbccsreadonly',
                'lookupclass' => 'emplevellookup',
                'action'      => 'emplevellookup',
                'readonly'    => true,
                'style'       => $this->style,
                'required'    => false
            ),
            'tjobcode'  => array(
                'name'        => 'tjobcode',
                'type'        => 'lookup',
                'label'       => 'To Job Title',
                'class'       => 'cstjobcode sbccsreadonly',
                'lookupclass' => 'empstatlookup',
                'action'      => 'lookupjobtitle',
                'readonly'    => true,
                'style'       => $this->style,
                'required'    => false
            ),
            'tjobname'  => array(
                'name'        => 'tjobname',
                'type'        => 'lookup',
                'label'       => 'To Job Title',
                'class'       => 'cstjobname sbccsreadonly',
                'lookupclass' => 'empstatlookup',
                'action'      => 'lookupjobtitle',
                'readonly'    => true,
                'style'       => $this->style,
                'required'    => false
            ),
            'tempstatcode'  => array(
                'name'        => 'tempstatcode',
                'type'        => 'lookup',
                'label'       => 'To Emp. Status',
                'class'       => 'cstempstatcode sbccsreadonly',
                'lookupclass' => 'empstatlookup',
                'action'      => 'empstatlookup',
                'readonly'    => true,
                'style'       => $this->style,
                'required'    => false
            ),
            'tempstatname'  => array(
                'name'        => 'tempstatname',
                'type'        => 'lookup',
                'label'       => 'To Emp. Status',
                'class'       => 'cstempstatcode sbccsreadonly',
                'lookupclass' => 'empstatlookup',
                'action'      => 'empstatlookup',
                'readonly'    => true,
                'style'       => $this->style,
                'required'    => false
            ),
            'trank'  => array(
                'name'        => 'trank',
                'type'        => 'lookup',
                'label'       => 'To Rank',
                'class'       => 'cstrank sbccsreadonly',
                'lookupclass' => 'empranklookup',
                'action'      => 'empranklookup',
                'readonly'    => true,
                'style'       => $this->style,
                'required'    => false
            ),
            'tjobgrade'  => array(
                'name'        => 'tjobgrade',
                'type'        => 'input',
                'label'       => 'To Job Grade',
                'class'       => 'cstjobgrade',
                'readonly'    => false,
                'style'       => $this->style,
                'required'    => false,
                'maxlength' => 100
            ),
            'tdeptname'  => array(
                'name'        => 'tdeptname',
                'type'        => 'input',
                'label'       => 'To Department',
                'class'       => 'cstdeptname sbccsreadonly',
                'readonly'    => false,
                'style'       => $this->style,
                'required'    => false
            ),
            'tdeptcode' => array(
                'name' => 'tdeptcode',
                'type' => 'lookup',
                'label' => 'To Department',
                'class' => 'cstdeptcode sbccsreadonly',
                'lookupclass' => 'tdeptcodelookup',
                'action' => 'lookupclient',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'tlocation' => array(
                'name' => 'tlocation',
                'type' => 'input',
                'label' => 'To Location',
                'class' => 'cstlocation',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100,
                'lookupclass' => 'whlocation',
                'action' => 'lookupclient',
            ),
            'tpaymode' => array(
                'name' => 'tpaymode',
                'type' => 'lookup',
                'label' => 'To Mode of Payment',
                'class' => 'cstdeptcode sbccsreadonly',
                'lookupclass' => 'tpaymodelookup',
                'action' => 'lookupmodeofpayment',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'paytype' => array(
                'name' => 'paytype',
                'type' => 'lookup',
                'label' => 'Payment Type',
                'class' => 'cstdeptcode sbccsreadonly',
                'lookupclass' => 'paytypelookup',
                'action' => 'lookuppaytype',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'tpaygroup' => array(
                'name' => 'tpaygroup',
                'type' => 'lookup',
                'label' => 'To Pay Group',
                'class' => 'cstpaygroup sbccsreadonly',
                'lookupclass' => 'tpaygrouplookup',
                'action' => 'paygrouplookup',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'tpaygroupname' => array(
                'name' => 'tpaygroupname',
                'type' => 'lookup',
                'label' => 'To Pay Group',
                'class' => 'cstpaygroup sbccsreadonly',
                'lookupclass' => 'tpaygrouplookup',
                'action' => 'paygrouplookup',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'tpayrate' => array(
                'name' => 'tpayrate',
                'type' => 'lookup',
                'label' => 'To Pay Rate',
                'class' => 'cstpayrate sbccsreadonly',
                'lookupclass' => 'tpayratelookup',
                'action' => 'payratelookup',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'tallowrate' => array(
                'name' => 'tallowrate',
                'type' => 'input',
                'label' => 'To Allowance',
                'class' => 'csttallowrate',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'tbasicrate' => array(
                'name' => 'tbasicrate',
                'type' => 'input',
                'label' => 'To Basic Salary',
                'class' => 'cstbasicrate',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'tcola' => array(
                'name' => 'tcola',
                'type' => 'input',
                'label' => 'To COLA',
                'class' => 'cstcola',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'tdivname'  => array(
                'name'        => 'tdivname',
                'type'        => 'input',
                'label'       => 'To Division',
                'class'       => 'cstdivname sbccsreadonly',
                'readonly'    => true,
                'style'       => $this->style,
                'required'    => false
            ),
            'tsectname'  => array(
                'name'        => 'tsectname',
                'type'        => 'input',
                'label'       => 'To Section',
                'class'       => 'csftectname sbccsreadonly',
                'readonly'    => true,
                'style'       => $this->style,
                'required'    => false
            ),
            'checkall' => array(
                'name' => 'checkall',
                'type' => 'checkbox',
                'label' => 'ALL',
                'class' => 'cscheckall',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
            ),
            'isuspended' => array(
                'name' => 'isuspended',
                'type' => 'checkbox',
                'label' => 'Suspended',
                'class' => 'csisuspended',
                'readonly' => false,
                'style' => $this->style,
                'required' => true
            ),
            'iswithhearing' => array(
                'name' => 'iswithhearing',
                'type' => 'checkbox',
                'label' => 'With Hearing',
                'class' => 'csiswithhearing',
                'readonly' => false,
                'style' => $this->style,
                'required' => true
            ),


            //button tabs
            'btndependents' => array(
                'name' => 'dependents',
                'type' => 'actionbtn',
                'label' => 'DEPENDENTS',
                'class' => 'btndependents',
                'lookupclass' => 'entryempdependents',
                'icon' => 'batch_prediction',
                'access' => 'view',
                'action' => 'payrollentry',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false
            ),
            'btneducation' => array(
                'name' => 'education',
                'type' => 'actionbtn',
                'label' => 'EDUCATION',
                'class' => 'btneducation',
                'lookupclass' => 'entryempeducation',
                'icon' => 'batch_prediction',
                'access' => 'view',
                'action' => 'payrollentry',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false
            ),
            'btnemployment' => array(
                'name' => 'employment',
                'type' => 'actionbtn',
                'label' => 'EMPLOYMENT',
                'class' => 'btnemployment',
                'lookupclass' => 'entryempemployment',
                'icon' => 'batch_prediction',
                'access' => 'view',
                'action' => 'payrollentry',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false
            ),
            'btnemprate' => array(
                'name' => 'emprate',
                'type' => 'actionbtn',
                'label' => 'RATE',
                'class' => 'btnemprate',
                'lookupclass' => 'viewemprate',
                'icon' => 'batch_prediction',
                'access' => 'view',
                'action' => 'payrollentry',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false
            ),
            'btnemploans' => array(
                'name' => 'emploans',
                'type' => 'actionbtn',
                'label' => 'LOANS',
                'class' => 'btnemploans',
                'lookupclass' => 'viewemploans',
                'icon' => 'batch_prediction',
                'access' => 'view',
                'action' => 'payrollentry',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false
            ),
            'btnempadvances' => array(
                'name' => 'empadvances',
                'type' => 'actionbtn',
                'label' => 'ADVANCES',
                'class' => 'btnempadvances',
                'lookupclass' => 'viewempadvances',
                'icon' => 'batch_prediction',
                'access' => 'view',
                'action' => 'payrollentry',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false
            ),
            'btnempcontract' => array(
                'name' => 'empcontract',
                'type' => 'actionbtn',
                'label' => 'CONTRACT',
                'class' => 'btnempcontract',
                'lookupclass' => 'entryempcontract',
                'icon' => 'batch_prediction',
                'access' => 'view',
                'action' => 'payrollentry',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false
            ),
            'btnempallowance' => array(
                'name' => 'empallowance',
                'type' => 'actionbtn',
                'label' => 'ALLOWANCE',
                'class' => 'btnempallowance',
                'lookupclass' => 'viewempallowances',
                'icon' => 'batch_prediction',
                'access' => 'view',
                'action' => 'payrollentry',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false
            ),
            'btnemptraining' => array(
                'name' => 'emptraining',
                'type' => 'actionbtn',
                'label' => 'TRAINING',
                'class' => 'btnemptraining',
                'lookupclass' => 'viewemptraining',
                'icon' => 'batch_prediction',
                'access' => 'view',
                'action' => 'payrollentry',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false
            ),
            'btnempturnover' => array(
                'name' => 'empturnover',
                'type' => 'actionbtn',
                'label' => 'TURNOVER/RETURN ITEMS',
                'class' => 'btnempturnover',
                'lookupclass' => 'viewempturnover',
                'icon' => 'batch_prediction',
                'access' => 'view',
                'action' => 'payrollentry',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false
            ),

            'empstat' => array(
                'name' => 'empstat',
                'type' => 'lookup',
                'label' => 'Status',
                'class' => 'csjstatus sbccsreadonly',
                'lookupclass' => 'lookupjstatus',
                'action' => 'lookupjstatus',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'setempstat' => array(
                'name' => 'setempstat',
                'type' => 'lookup',
                'label' => 'Applied Status',
                'class' => 'csjstatus sbccsreadonly',
                'lookupclass' => 'lookupleavesetstatus',
                'action' => 'lookupleavestatus',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'empdesc' => array(
                'name' => 'empdesc',
                'type' => 'lookup',
                'label' => 'Status',
                'class' => 'csjstatus sbccsreadonly',
                'lookupclass' => 'lookupjstatus',
                'action' => 'lookupjstatus',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'tdate1' => array(
                'name' => 'tdate1',
                'type' => 'date',
                'label' => 'Training Date From',
                'class' => 'cstdate1',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'tdate2' => array(
                'name' => 'tdate2',
                'type' => 'date',
                'label' => 'Training Date To',
                'class' => 'cstdate2',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'ttype' => array(
                'name' => 'ttype',
                'type' => 'lookup',
                'label' => 'Training Type',
                'class' => 'cscontra sbccsreadonly',
                'lookupclass' => 'lookuptrainingentrytype',
                'action' => 'lookuptrainingentrytype',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'payrolltype' => array(
                'name' => 'payrolltype',
                'type' => 'lookup',
                'label' => 'Payroll Type',
                'class' => 'cscontra sbccsreadonly',
                'lookupclass' => 'payrolltype',
                'action' => 'lookuprandom',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'employeetype' => array(
                'name' => 'employeetype',
                'type' => 'lookup',
                'label' => 'Employee Type',
                'class' => 'cscontra sbccsreadonly',
                'lookupclass' => 'employeetype',
                'action' => 'lookuprandom',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'speaker' => array(
                'name' => 'speaker',
                'type' => 'input',
                'label' => 'Speaker',
                'class' => 'cstype',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'cost' => array(
                'name' => 'cost',
                'type' => 'input',
                'label' => 'Training Cost',
                'class' => 'csamt',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'attendees' => array(
                'name' => 'attendees',
                'type' => 'textarea',
                'label' => 'Attendees',
                'class' => 'csremarks',
                'readonly' => true,
                'style' => '',
                'required' => false,
                'maxlength' => 200
            ),

            'dpersonel' => array(
                'name' => 'dpersonel',
                'type' => 'lookup',
                'label' => 'Requesting Personnel',
                'labeldata' => 'personnel~personnelname',
                'class' => 'csdpersonnel sbccsreadonly',
                'action' => 'lookupclient',
                'lookupclass' => 'personnel',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'headcount' => array(
                'name' => 'headcount',
                'type' => 'input',
                'label' => 'No. of Heads',
                'class' => 'csdheads',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),

            'hpref' => array(
                'name' => 'hpref',
                'type' => 'lookup',
                'label' => 'Hiring Preference',
                'class' => 'cscontra sbccsreadonly',
                'lookupclass' => 'lookuphpref',
                'action' => 'lookuphpref',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),

            'agerange' => array(
                'name' => 'agerange',
                'type' => 'input',
                'label' => 'Age Range',
                'class' => 'csagerange',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'educlevel' => array(
                'name' => 'educlevel',
                'type' => 'input',
                'label' => 'Educational Attainment',
                'class' => 'cseduclevel',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),

            'gpref' => array(
                'name' => 'gpref',
                'type' => 'lookup',
                'label' => 'Gender Preference',
                'class' => 'cscontra sbccsreadonly',
                'lookupclass' => 'gpref',
                'action' => 'lookupgender',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),

            'reasontype' => array(
                'name' => 'reasontype',
                'type' => 'lookup',
                'label' => 'Reason',
                'class' => 'csreasontype sbccsreadonly',
                'lookupclass' => 'lookupreasonhire',
                'action' => 'lookupreasonhire',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),
            'empstattype' => array(
                'name' => 'empstattype',
                'type' => 'lookup',
                'label' => 'Employment Status Type',
                'class' => 'csempstattype sbccsreadonly',
                'lookupclass' => 'lookupempstatus',
                'action' => 'lookupempstatus',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),
            'reason' => array(
                'name' => 'reason',
                'type' => 'textarea',
                'label' => 'Reason For Hiring',
                'class' => 'csreason',
                'readonly' => false,
                'style' => '',
                'required' => false,
                'maxlength' => 200,
                'error' => false,
            ),

            'hirereason' => array(
                'name' => 'hirereason',
                'type' => 'input',
                'label' => 'Reason',
                'class' => 'csreason',
                'readonly' => false,
                'style' => '',
                'required' => false,
                'maxlength' => 200,
                'error' => false,
            ),

            'remark' => array(
                'name' => 'remark',
                'type' => 'textarea',
                'label' => 'Remarks',
                'class' => 'csreason',
                'readonly' => false,
                'style' => '',
                'required' => false,
                'maxlength' => 200
            ),
            'qualification' => array(
                'name' => 'qualification',
                'type' => 'textarea',
                'label' => 'Other Qualification',
                'class' => 'csreason',
                'readonly' => false,
                'style' => '',
                'required' => false,
                'maxlength' => 200
            ),
            'skill' => array(
                'name' => 'skill',
                'type' => 'textarea',
                'label' => 'Skill Requirements',
                'class' => 'csreason',
                'readonly' => false,
                'style' => '',
                'required' => false,
                'maxlength' => 200
            ),
            'jobsumm' => array(
                'name' => 'jobsumm',
                'type' => 'textarea',
                'label' => 'Job Description',
                'class' => 'csreason',
                'readonly' => false,
                'style' => '',
                'required' => false,
                'maxlength' => 200
            ),

            'odometer' => array(
                'name' => 'odometer',
                'type' => 'input',
                'label' => 'ODO',
                'class' => 'csodometer',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'agentamt' => array(
                'name' => 'agentamt',
                'type' => 'input',
                'label' => 'Agent Amount',
                'class' => 'csamt',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),

            'agentid' => array(
                'name' => 'agentid',
                'type' => 'hidden',
                'class' => 'csagentid sbccsreadonly'
            ),
            'sgid' => array(
                'name' => 'sgid',
                'type' => 'hidden',
                'class' => 'cssgid sbccsreadonly'
            ),

            'prdstart' => array(
                'name' => 'prdstart',
                'type' => 'date',
                'label' => 'Period',
                'class' => 'csprdstart',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'empmonths' => array(
                'name' => 'empmonths',
                'type' => 'input',
                'label' => 'Months',
                'class' => 'csmonths',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'empdays' => array(
                'name' => 'empdays',
                'type' => 'input',
                'label' => 'Days',
                'class' => 'csdays',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),

            'prdend' => array(
                'name' => 'prdend',
                'type' => 'date',
                'label' => 'To',
                'class' => 'csprdend',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'acno' => array(
                'name' => 'acno',
                'type' => 'lookup',
                'label' => 'Account No.',
                'class' => 'cscontra sbccsreadonly',
                'lookupclass' => 'lookuppacno',
                'action' => 'lookuppacno',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),



            'days' => array(
                'name' => 'days',
                'type' => 'input',
                'label' => 'Entitled',
                'class' => 'csentitled',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'daytype' => array(
                'name' => 'daytype',
                'type' => 'input',
                'label' => 'Day type',
                'class' => 'csdaytype sbccsreadonly',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'ftime' => array(
                'name' => 'ftime',
                'type' => 'time',
                'label' => 'From',
                'class' => 'csftime',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'ttime' => array(
                'name' => 'ttime',
                'type' => 'time',
                'label' => 'To',
                'class' => 'csftime',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'breakinam' => array(
                'name' => 'breakinam',
                'type' => 'time',
                'label' => 'Break in Am',
                'class' => 'csbreakinam',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'breakoutam' => array(
                'name' => 'breakoutam',
                'type' => 'time',
                'label' => 'Break out Am',
                'class' => 'csbreakoutam',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'breakoutpm' => array(
                'name' => 'breakoutpm',
                'type' => 'time',
                'label' => 'Break out Pm',
                'class' => 'csbreakoutpm',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'breakinpm' => array(
                'name' => 'breakinpm',
                'type' => 'time',
                'label' => 'Break in Pm',
                'class' => 'csbreakinpm',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'sig' => array(
                'name' => 'sig',
                'type' => 'input',
                'label' => 'Significance (min)',
                'class' => 'cssig',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'flexit' => array(
                'name' => 'flexit',
                'type' => 'checkbox',
                'label' => 'Flexible Time',
                'class' => 'csflexit',
                'readonly' => false,
                'style' => $this->style,
                'required' => true
            ),
            'isonelog' => array(
                'name' => 'isonelog',
                'type' => 'checkbox',
                'label' => 'One Log Only',
                'class' => 'csisonelog',
                'readonly' => false,
                'style' => $this->style,
                'required' => true
            ),
            'isdefault' => array(
                'name' => 'isdefault',
                'type' => 'checkbox',
                'label' => 'Default Shift',
                'class' => 'csisdefault',
                'readonly' => false,
                'style' => $this->style,
                'required' => true
            ),
            'paymodetype' => array(
                'name' => 'paymodetype',
                'type' => 'lookup',
                'label' => '',
                'class' => 'cspaymodetype sbccsreadonly',
                'action' => 'lookuppaymodetype',
                'readonly' => true,
                'style' => $this->style,
                'required' => true,
                'addedparams' => ['paymode']
            ),
            'istax' => array(
                'name' => 'istax',
                'type' => 'checkbox',
                'label' => 'Annual Tax',
                'class' => 'csistax',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'is13' => array(
                'name' => 'is13',
                'type' => 'checkbox',
                'label' => '13 Month',
                'class' => 'csistax',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'priority' => array(
                'name' => 'priority',
                'type' => 'input',
                'label' => 'Deduction Priority',
                'class' => 'cspriority',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),

            'amortization' => array(
                'name' => 'amortization',
                'type' => 'input',
                'label' => 'Amortization',
                'class' => 'csamortization',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),

            'effdate' => array(
                'name' => 'effdate',
                'type' => 'date',
                'label' => 'Effectivity Date',
                'class' => 'cseffdate',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),

            'feffdate' => array(
                'name' => 'feffdate',
                'type' => 'input',
                'label' => 'Effectivity Date',
                'class' => 'csfeffdate',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),

            'w1' => array(
                'name' => 'w1',
                'type' => 'checkbox',
                'label' => 'Week 1',
                'class' => 'csw1',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'w2' => array(
                'name' => 'w2',
                'type' => 'checkbox',
                'label' => 'Week 2',
                'class' => 'csw2',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'w3' => array(
                'name' => 'w3',
                'type' => 'checkbox',
                'label' => 'Week 3',
                'class' => 'csw3',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'w4' => array(
                'name' => 'w4',
                'type' => 'checkbox',
                'label' => 'Week 4',
                'class' => 'csw4',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'w5' => array(
                'name' => 'w5',
                'type' => 'checkbox',
                'label' => 'Week 5',
                'class' => 'csw5',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'void' => array(
                'name' => 'void',
                'type' => 'checkbox',
                'label' => 'Void',
                'class' => 'csvoid',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'halt' => array(
                'name' => 'halt',
                'type' => 'checkbox',
                'label' => 'Void',
                'class' => 'cshalt',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'w13' => array(
                'name' => 'w13',
                'type' => 'checkbox',
                'label' => '13th',
                'class' => 'csw13',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'withoutdeduction' => array(
                'name' => 'withoutdeduction',
                'type' => 'checkbox',
                'label' => 'Without Deduction (SSS, PAG-IBIG, PHILHEALTH and TAX)',
                'class' => 'csisvat',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'ottype' => array(
                'name' => 'ottype',
                'type' => 'lookup',
                'label' => 'Type',
                'class' => 'csottype sbccsreadonly',
                'lookupclass' => 'lookupottype',
                'action' => 'lookupottype',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'drate' => array(
                'name' => 'drate',
                'type' => 'input',
                'label' => 'Item Rate',
                'class' => 'csdrate',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'dqty' => array(
                'name' => 'dqty',
                'type' => 'input',
                'label' => 'Quantity',
                'class' => 'csdqty',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'begqty' => array(
                'name' => 'begqty',
                'type' => 'input',
                'label' => 'Beg. Reading',
                'class' => 'csbegqty',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'poqty' => array(
                'name' => 'poqty',
                'type' => 'input',
                'label' => 'Quantity POed',
                'class' => 'cspoqty',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'issueqty' => array(
                'name' => 'issueqty',
                'type' => 'input',
                'label' => 'Quantity Issued',
                'class' => 'cspoqty',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'rrqty' => array(
                'name' => 'rrqty',
                'type' => 'input',
                'label' => 'Quantity Received',
                'class' => 'csrrqty',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'rrqty2' => array(
                'name' => 'rrqty2',
                'type' => 'input',
                'label' => 'Qty',
                'class' => 'csrrqty2 sbccsreadonly',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'rrcost' => array(
                'name' => 'rrcost',
                'type' => 'input',
                'label' => 'Cost',
                'class' => 'csrrcost',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'basepending' => array(
                'name' => 'basepending',
                'type' => 'input',
                'label' => 'Base Pending',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'rqcd' => array(
                'name' => 'rqcd',
                'type' => 'input',
                'label' => 'Request-Canvass Pending',
                'class' => 'csrqcd sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'qa' => array(
                'name' => 'qa',
                'type' => 'input',
                'label' => 'Pending',
                'class' => 'csqa sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'waivedqty' => array(
                'name' => 'waivedqty',
                'type' => 'input',
                'label' => 'Waived Qty',
                'class' => 'cswaivedqty sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'canvasstatus' => array(
                'name' => 'canvasstatus',
                'type' => 'input',
                'label' => 'Status',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'sanodesc' => array(
                'name' => 'sanodesc',
                'type' => 'input',
                'label' => 'SA #',
                'class' => 'cssanodesc sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'ismanual' => array(
                'name' => 'ismanual',
                'type' => 'input',
                'label' => 'Manual',
                'class' => 'csismanual sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'carem' => array(
                'name' => 'carem',
                'type' => 'input',
                'label' => 'Notes (Canvass Approver)',
                'class' => 'cscarem sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'daddon' => array(
                'name' => 'daddon',
                'type' => 'input',
                'label' => 'Adds-On',
                'class' => 'csdaddon',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'diqty' => array(
                'name' => 'diqty',
                'type' => 'input',
                'label' => 'Item Quantity',
                'class' => 'csdiqty',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'damt' => array(
                'name' => 'damt',
                'type' => 'input',
                'label' => 'Amount',
                'class' => 'csdamt',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'dcode' => array(
                'name' => 'dcode',
                'type' => 'lookup',
                'label' => 'Barcode',
                'class' => 'csdcode sbccsreadonly',
                'lookupclass' => 'lookupdcode',
                'action' => 'lookupdcode',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'dname' => array(
                'name' => 'dname',
                'type' => 'input',
                'label' => 'Item name',
                'class' => 'csdname',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'name' => array(
                'name' => 'name',
                'type' => 'input',
                'label' => 'Name',
                'class' => 'csname',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'emeter' => array(
                'name' => 'emeter',
                'type' => 'input',
                'label' => 'Electric Meter #',
                'class' => 'csemeter',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'wmeter' => array(
                'name' => 'wmeter',
                'type' => 'input',
                'label' => 'Water Meter #',
                'class' => 'cswmeter',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'semeter' => array(
                'name' => 'semeter',
                'type' => 'input',
                'label' => 'S. Electric Meter #',
                'class' => 'cssemeter',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'biometric' => array(
                'name' => 'biometric',
                'type' => 'lookup',
                'label' => 'Biometric Terminal',
                'class' => 'cssbiometic',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'modeofsales' => array(
                'name' => 'modeofsales',
                'type' => 'lookup',
                'label' => 'Mode of Sales',
                'class' => 'csline sbccsreadonly',
                'lookupclass' => 'modeofpayment',
                'action' => 'lookuprandom',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),
            'mode' => array(
                'name' => 'mode',
                'type' => 'qselect',
                'label' => 'Mode',
                'class' => 'csmode sbccsreadonly',
                'readonly' => false,
                'style' => $this->style,
                'options' => array(
                    ['label' => '', 'value' => '']
                )
            ),
            'changetime' => array(
                'name' => 'changetime',
                'type' => 'lookup',
                'label' => 'Change Time',
                'class' => 'cschangetime sbccsreadonly',
                'action' => 'lookupchangetime',
                'lookupclass' => 'lookupchangetime',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            // End of HRIS,PAYROLL

            //image
            'picture' => array(
                'name' => 'picture',
                'type' => 'image',
                'label' => 'Picture',
                'class' => 'csdivision',
                'folder' => 'product',
                'table' => 'item',
                'fieldid' => 'itemid',
                'lookupclass' => 'item',
                'action' => 'item',
                'readonly' => true,
                'style' => "height: 200px; max-width: 200px",
                'required' => false,
                'viewable' => true
            ),


            //message
            'clientlist' => array(
                'name' => 'clientlist',
                'type' => 'lookup',
                'label' => 'Choose one...',
                'class' => 'csline sbccsreadonly',
                'lookupclass' => 'clientlistlookup',
                'action' => 'clientlistlookup',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'addedparams' => ['msggroup']
            ),




            //assessment
            'schedcode' => array(
                'name' => 'schedcode',
                'type' => 'lookup',
                'label' => 'Schedule',
                'class' => 'csline sbccsreadonly',
                'lookupclass' => 'lookupsched',
                'action' => 'lookupsched',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'addedparams' => ['adviserid', 'yr', 'semid', 'periodid', 'syid']
            ),
            'modeofpayment' => array(
                'name' => 'modeofpayment',
                'type' => 'lookup',
                'label' => 'MOP',
                'class' => 'csline sbccsreadonly',
                'lookupclass' => 'lookupmodeofpay',
                'action' => 'lookupmodeofpay',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isnocrlimit' => array(
                'name' => 'isnocrlimit',
                'type' => 'checkbox',
                'label' => 'No CreditLimit',
                'class' => 'csisnocrlimit',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'creditinfo' => array(
                'name' => 'creditinfo',
                'type' => 'textarea',
                'label' => 'Credit Info',
                'class' => 'cscreditinfo sbccsreadonly',
                'readonly' => true,
                'style' => 'height:12em;',
                'required' => false
            ),
            'manpower' => array(
                'name' => 'manpower',
                'type' => 'textarea',
                'label' => 'Manpower',
                'class' => 'csmanpower sbccsreadonly',
                'readonly' => true,
                'style' => 'height:12em;',
                'required' => false
            ),
            'disapproved_remarks' => array(
                'name' => 'disapproved_remarks',
                'type' => 'textarea',
                'label' => 'Disapproved Remarks',
                'class' => 'csdisapproved_remarks sbccsreadonly',
                'readonly' => true,
                'style' => 'height:12em;',
                'required' => false
            ),

            //construction

            'tcp' => array(
                'name' => 'tcp',
                'type' => 'input',
                'label' => 'Total Contract Price',
                'class' => 'csamt',
                'readonly' => true,
                'style' => $this->style,
                'required' => true,
                'maxlength' => 20
            ),
            'ocp' => array(
                'name' => 'ocp',
                'type' => 'input',
                'label' => 'Original Contract Price',
                'class' => 'csamt',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'dollarprice' => array(
                'name' => 'dollarprice',
                'type' => 'input',
                'label' => 'Contract Price (Dollar)',
                'class' => 'csamt',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'dp' => array(
                'name' => 'dp',
                'type' => 'input',
                'label' => 'Downpayment(%)',
                'class' => 'csdp',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'retention' => array(
                'name' => 'retention',
                'type' => 'input',
                'label' => 'Retention(%)',
                'class' => 'csretention',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'closedate' => array(
                'name' => 'closedate',
                'type' => 'date',
                'label' => 'Closing Date',
                'class' => 'csclosedate sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'completed' => array(
                'name' => 'completed',
                'type' => 'input',
                'label' => '%Completed',
                'class' => 'sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'conduration' => array(
                'name' => 'conduration',
                'type' => 'input',
                'label' => 'Contract Duration',
                'class' => 'csduration',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'project' => array(
                'name' => 'project',
                'type' => 'lookup',
                'label' => 'Project',
                'class' => 'csproject',
                'lookupclass' => 'lookupproject',
                'action' => 'lookupproject',
                'readonly' => true,
                'style' => $this->style,
                'required' => true,
                'addedparams' => ['client']
            ),
            'projectname' => array(
                'name' => 'projectname',
                'type' => 'input',
                'label' => 'Project Name',
                'class' => 'sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'subprojectname' => array(
                'name' => 'subprojectname',
                'type' => 'input',
                'label' => 'Sub-project Name',
                'class' => 'sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),
            'subprojectname2' => array(
                'name' => 'subprojectname2',
                'type' => 'input',
                'label' => 'Sub-project Destination',
                'class' => 'sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),
            'stage' => array(
                'name' => 'stage',
                'type' => 'lookup',
                'label' => 'Stage',
                'class' => 'csstage sbccsreadonly',
                'lookupclass' => 'lookupstage',
                'action' => 'lookupstage',
                'readonly' => true,
                'style' => $this->style,
                'required' => true,
                'addedparams' => ['subproject']
            ),
            'codocno' => array(
                'name' => 'codocno',
                'type' => 'lookup',
                'label' => 'Construction Order',
                'class' => 'cscodocno sbccsreadonly',
                'lookupclass' => 'lookupconstructionorder',
                'action' => 'getconstructionorder',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'prdocno' => array(
                'name' => 'prdocno',
                'type' => 'lookup',
                'label' => 'Production Request',
                'class' => 'csprdocno sbccsreadonly',
                'lookupclass' => 'lookupproductionrequest',
                'action' => 'getproductionrequest',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'productionorder' => array(
                'name' => 'productionorder',
                'type' => 'lookup',
                'label' => 'Production Order',
                'class' => 'csproductionorder sbccsreadonly',
                'lookupclass' => 'lookupproductionorder',
                'action' => 'getproductionorder',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            // btnlink jumping directly to module transaction
            'blrr' => array(
                'name' => 'print',
                'type' => 'btnlink',
                'label' => 'Print',
                'class' => 'csprint',
                'action' => 'print',
                'readonly' => true,
                'url' => '/ledger/masterfile/supplier',
                'addedparams' => ['ledger', 'rr', 'view'],
                'style' => $this->style,
                'required' => false
            ),
            'blstockcard' => array(
                'name' => 'stockcard',
                'type' => 'btnlink',
                'label' => 'GO TO STOCKCARD',
                'class' => 'exit_to_app',
                'action' => 'stockcard',
                'readonly' => true,
                'url' => '/ledgergrid/masterfile/stockcard',
                'addedparams' => ['ledgergrid', 'stockcard', 'view'],
                'style' => 'font-size:100%;',
                'required' => false
            ),
            'blpayrollentry' => array(
                'name' => 'payrollentry',
                'type' => 'btnlink',
                'label' => 'GO TO PAYROLL ENTRY',
                'class' => 'exit_to_app',
                'action' => 'payrollentry',
                'readonly' => true,
                'url' => '/headtable/payrollcustomform/payrollentry',
                'addedparams' => ['headtable', 'payrollentry', 'view'],
                'style' => $this->style,
                'required' => false
            ),
            'bltimecard' => array(
                'name' => 'timecard',
                'type' => 'btnlink',
                'label' => 'GO TO EMPLOYEE`S TIMECARD',
                'class' => 'exit_to_app',
                'action' => 'timecard',
                'readonly' => true,
                'url' => '/headtable/payrollcustomform/payrollentry',
                'addedparams' => ['headtable', 'timecard', 'view'],
                'style' => $this->style,
                'required' => false
            ),
            'blotapproval' => array(
                'name' => 'entryotapproval',
                'type' => 'btnlink',
                'label' => 'GO TO OT APPROVAL',
                'class' => 'exit_to_app',
                'action' => 'entryotapproval',
                'readonly' => true,
                'url' => '/headtable/payrollentry',
                'addedparams' => ['headtable', 'entryotapproval', 'view'],
                'style' => $this->style,
                'required' => false
            ),
            'workloc' => array(
                'name' => 'workloc',
                'type' => 'input',
                'label' => 'Work Location',
                'class' => 'csstage',
                'readonly' => false,
                'style' => $this->style,
                'required' => true
            ),
            'workdesc' => array(
                'name' => 'workdesc',
                'type' => 'ctextarea',
                'label' => 'Work Description',
                'class' => 'csrem',
                'readonly' => true,
                'style' => '',
                'required' => true,
                'error' => false,
                'maxlength' => 200
            ),
            'checker' => array(
                'name' => 'checker',
                'type' => 'lookup',
                'label' => 'Checker',
                'class' => 'cschecker sbccsreadonly',
                'lookupclass' => 'lookupchecker',
                'action' => 'lookupchecker',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'checkerloc' => array(
                'name' => 'checkerloc',
                'type' => 'lookup',
                'label' => 'Checker Location',
                'class' => 'cscheckerloc sbccsreadonly',
                'lookupclass' => 'lookupcheckerloc',
                'action' => 'lookupcheckerloc',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'addedparams' => ['checkerid']
            ),
            'newchecker' => array(
                'name' => 'newchecker',
                'type' => 'lookup',
                'label' => 'New Checker',
                'class' => 'csnewchecker sbccsreadonly',
                'lookupclass' => 'lookupchecker',
                'action' => 'lookupchecker',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'postwhclr' => array(
                'name' => 'backlisting',
                'type' => 'actionbtn',
                'label' => 'FOR PICKING',
                'class' => 'btnpostwhclr',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'batch_prediction',
                'access' => 'edit',
                'action' => 'post',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false,
                'confirm' => true,
                'confirmlabel' => 'Proceed for picking?'
            ),
            'unlockwhclr' => array(
                'name' => 'backlisting',
                'type' => 'actionbtn',
                'label' => 'UNLOCK',
                'class' => 'btnunlockwhclr',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'lock_open',
                'access' => 'edit',
                'action' => 'unlock',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false,
                'confirm' => true,
                'confirmlabel' => 'Unlock transaction?'
            ),
            'scanpallet' => array(
                'name' => 'scanpallet',
                'type' => 'actionbtn',
                'label' => 'Scan Pallet',
                'class' => 'btnscanpallet',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'flash_on',
                'access' => 'view',
                'action' => 'scantext',
                'action2' => 'scanpallet',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false
            ),
            'scanitemloc' => array(
                'name' => 'scanitemloc',
                'type' => 'actionbtn',
                'label' => 'Scan Item Barcode',
                'class' => 'btnscanitemloc',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'flash_on',
                'access' => 'view',
                'action' => 'scantext',
                'action2' => 'scanitemloc',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false
            ),
            'forloading' => array(
                'name' => 'backlisting',
                'type' => 'actionbtn',
                'label' => 'FOR LOADING',
                'class' => 'btnforloading',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'archive',
                'access' => 'view',
                'action' => 'forloading',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false,
                'confirm' => true,
                'confirmlabel' => 'Prepare for loading?'
            ),
            'rescedule' => array(
                'name' => 'backlisting',
                'type' => 'actionbtn',
                'label' => 'Re-schedule',
                'class' => 'btnrescedule',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'event_busy',
                'access' => 'view',
                'action' => 'rescedule',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false,
                'confirm' => true,
                'confirmlabel' => 'Re-scedule delivery?'
            ),
            'donetodo' => array(
                'name' => 'backlisting',
                'type' => 'actionbtn',
                'label' => 'Done TO do',
                'class' => 'btndonetodo',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'refresh',
                'access' => 'save',
                'action' => 'donetodo',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false
            ),
            'updatepostedinfo' => array(
                'name' => 'backlisting',
                'type' => 'actionbtn',
                'label' => 'Update Category',
                'class' => 'btnupdatepostedinfo',
                'lookupclass' => 'updatepostedinfo',
                'icon' => 'edit',
                'access' => 'save',
                'action' => 'customformdialog',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false
            ),

            'loadinventorywithbal' => array(
                'name' => 'backlisting',
                'type' => 'actionbtn',
                'label' => 'Load Inventory With Balance',
                'class' => 'btnloadinventorywithbal',
                'lookupclass' => 'loadinventorywithbal',
                'icon' => 'refresh',
                'access' => 'save',
                'action' => 'customformdialog',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false
            ),
            'duplicatedoc' => array(
                'name' => 'duplicatedoc',
                'type' => 'actionbtn',
                'label' => 'Duplicate',
                'class' => 'btnduplicatedoc',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'file_copy',
                'access' => 'save',
                'action' => 'duplicatedoc',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false,
                'confirm' => true,
                'confirmlabel' => 'Duplicate this transaction?'
            ),
            'forrevision' => array(
                'name' => 'backlisting',
                'type' => 'actionbtn',
                'label' => 'For Revision',
                'class' => 'btnforrevision',
                'lookupclass' => 'updateremrevision',
                'icon' => 'refresh',
                'access' => 'save',
                'action' => 'customformdialog',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false
            ),
            'forapproval' => array(
                'name' => 'backlisting',
                'type' => 'actionbtn',
                'label' => 'For Approval',
                'class' => 'btnforapproval',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'done',
                'access' => 'save',
                'action' => 'forapproval',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false
            ),
            'ordered' => array(
                'name' => 'backlisting',
                'type' => 'actionbtn',
                'label' => 'Ordered',
                'class' => 'btnordered',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'done',
                'access' => 'save',
                'action' => 'ordered',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false
            ),
            'forreceiving' => array(
                'name' => 'backlisting',
                'type' => 'actionbtn',
                'label' => 'For Receiving',
                'class' => 'btnforreceiving',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'inventory',
                'access' => 'save',
                'action' => 'forreceiving',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false
            ),
            'forwtinput' => array(
                'name' => 'backlisting',
                'type' => 'actionbtn',
                'label' => 'For Weight Input',
                'class' => 'btnforwtinput',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'inventory',
                'access' => 'save',
                'action' => 'forwtinput',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false
            ),
            'intransit' => array(
                'name' => 'backlisting',
                'type' => 'actionbtn',
                'label' => 'Intransit',
                'class' => 'btnintransit',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'check',
                'access' => 'save',
                'action' => 'intransit',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false
            ),
            'forchecking' => array(
                'name' => 'backlisting',
                'type' => 'actionbtn',
                'label' => 'For Checking',
                'class' => 'btnforchecking',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'done',
                'access' => 'save',
                'action' => 'forchecking',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false
            ),
            'doneapproved' => array(
                'name' => 'backlisting',
                'type' => 'actionbtn',
                'label' => 'APPROVED',
                'class' => 'btndoneapproved',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'check',
                'access' => 'save',
                'action' => 'doneapproved',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false
            ),
            'doneinitialchecking' => array(
                'name' => 'backlisting',
                'type' => 'actionbtn',
                'label' => 'APPROVED (Initial Checking)',
                'class' => 'btndoneinitialchecking',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'check',
                'access' => 'save',
                'action' => 'doneinitialchecking',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false,
                'confirm' => true,
                'confirmlabel' => 'Approved this document?'
            ),
            'donefinalchecking' => array(
                'name' => 'backlisting',
                'type' => 'actionbtn',
                'label' => 'APPROVED (Final Checking)',
                'class' => 'btndonefinalchecking',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'check',
                'access' => 'save',
                'action' => 'donefinalchecking',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false,
                'confirm' => true,
                'confirmlabel' => 'Approved this document?'
            ),
            'posted' => array(
                'name' => 'backlisting',
                'type' => 'actionbtn',
                'label' => 'POST',
                'class' => 'btnposted',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'check',
                'access' => 'save',
                'action' => 'posted',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false
            ),
            'paid' => array(
                'name' => 'backlisting',
                'type' => 'actionbtn',
                'label' => 'PAID',
                'class' => 'btnposted',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'check',
                'access' => 'save',
                'action' => 'paid',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false
            ),
            'acknowledged' => array(
                'name' => 'backlisting',
                'type' => 'actionbtn',
                'label' => 'Acknowledged',
                'class' => 'btnposted',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'check',
                'access' => 'save',
                'action' => 'acknowledged',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false
            ),
            'advancesclr' => array(
                'name' => 'backlisting',
                'type' => 'actionbtn',
                'label' => 'ADVANCES CLEARED',
                'class' => 'btnposted',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'check',
                'access' => 'save',
                'action' => 'advancesclr',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false
            ),
            'soareceived' => array(
                'name' => 'backlisting',
                'type' => 'actionbtn',
                'label' => 'SOA Received',
                'class' => 'btnposted',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'check',
                'access' => 'save',
                'action' => 'soareceived',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false
            ),
            'tagpacked' => array(
                'name' => 'tagpacked',
                'type' => 'actionbtn',
                'label' => 'Load Pack House',
                'class' => 'btntagreleased',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'check',
                'access' => 'save',
                'action' => 'tagpacked',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false,
                'confirm' => true,
                'confirmlabel' => 'Continue load pack house?'
            ),
            'tagreleased' => array(
                'name' => 'tagreleased',
                'type' => 'actionbtn',
                'label' => 'Released',
                'class' => 'btntagreleased',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'check',
                'access' => 'save',
                'action' => 'tagreleased',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false,
                'confirm' => true,
                'confirmlabel' => 'Tag released?'
            ),
            'forposting' => array(
                'name' => 'forposting',
                'type' => 'actionbtn',
                'label' => 'FOR POSTING',
                'class' => 'btnposted',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'check',
                'access' => 'save',
                'action' => 'forposting',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false
            ),
            'checkissued' => array(
                'name' => 'checkissued',
                'type' => 'actionbtn',
                'label' => 'CHECK ISSUED',
                'class' => 'btnposted',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'check',
                'access' => 'save',
                'action' => 'checkissued',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false
            ),
            'forso' => array(
                'name' => 'forso',
                'type' => 'actionbtn',
                'label' => 'FOR SO',
                'class' => 'btnposted',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'check',
                'access' => 'save',
                'action' => 'forso',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false
            ),
            'foror' => array(
                'name' => 'foror',
                'type' => 'actionbtn',
                'label' => 'FOR Oracle Receiving',
                'class' => 'btnposted',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'check',
                'access' => 'save',
                'action' => 'foror',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false
            ),
            'itemscollected' => array(
                'name' => 'itemscollected',
                'type' => 'actionbtn',
                'label' => 'ITEMS COLLECTED',
                'class' => 'btnposted',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'check',
                'access' => 'save',
                'action' => 'itemscollected',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false,
                'confirm' => true,
                'confirmlabel' => 'Are you sure you want to tag as Items Collected?'
            ),
            'voidpayment' => array(
                'name' => 'voidpayment',
                'type' => 'actionbtn',
                'label' => 'VOID PAYMENT',
                'class' => 'btnposted',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'close',
                'access' => 'voidpayment',
                'action' => 'voidpayment',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false,
                'confirm' => true,
                'confirmlabel' => 'Are you sure you want to tag as Void Transaction?'
            ),
            'voidtrans' => array(
                'name' => 'voidtrans',
                'type' => 'actionbtn',
                'label' => 'VOID/END PROMOTION',
                'class' => 'btnposted',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'close',
                'access' => 'voidtrans',
                'action' => 'voidtrans',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false,
                'confirm' => true,
                'confirmlabel' => 'Are you sure you want to tag as Void/End this promotion?'
            ),
            'forwardop' => array(
                'name' => 'forwardop',
                'type' => 'actionbtn',
                'label' => 'FORWARDED TO OP',
                'class' => 'btnposted',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'check',
                'access' => 'save',
                'action' => 'forwardop',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false,
                'confirm' => true,
                'confirmlabel' => 'Are you sure you want to tag as Forwarded to OP?'
            ),
            'forwardacctg' => array(
                'name' => 'forwardacctg',
                'type' => 'actionbtn',
                'label' => 'FORWARDED TO Accounting',
                'class' => 'btnposted',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'check',
                'access' => 'save',
                'action' => 'forwardacctg',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false,
                'confirm' => true,
                'confirmlabel' => 'Are you sure you want to tag as Forwarded to Accounting?'
            ),
            'forwardencoder' => array(
                'name' => 'forwardencoder',
                'type' => 'actionbtn',
                'label' => 'FORWARDED TO ENCODER',
                'class' => 'btnposted',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'check',
                'access' => 'save',
                'action' => 'forwardencoder',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false,
                'confirm' => true,
                'confirmlabel' => 'Are you sure you want to tag as Forwarded to Encoder?'
            ),
            'forwardwh' => array(
                'name' => 'forwardwh',
                'type' => 'actionbtn',
                'label' => 'FORWARDED TO WAREHOUSE',
                'class' => 'btnposted',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'check',
                'access' => 'save',
                'action' => 'forwardwh',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false,
                'confirm' => true,
                'confirmlabel' => 'Are you sure you want to tag as Forwarded to Warehouse?'
            ),
            'forwardasset' => array(
                'name' => 'forwardasset',
                'type' => 'actionbtn',
                'label' => 'FORWARDED TO ASSET MNGT',
                'class' => 'btnposted',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'check',
                'access' => 'save',
                'action' => 'forwardasset',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false,
                'confirm' => true,
                'confirmlabel' => 'Are you sure you want to tag as Forwarded to Asset Management?'
            ),
            'forliquidation' => array(
                'name' => 'forliquidation',
                'type' => 'actionbtn',
                'label' => 'For Liquidation',
                'class' => 'btnposted',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'check',
                'access' => 'save',
                'action' => 'forliquidation',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false,
                'confirm' => true,
                'confirmlabel' => 'Are you sure you want to tag as for Liquidation?'
            ),
            'forclosing' => array(
                'name' => 'backlisting',
                'type' => 'actionbtn',
                'label' => 'Close',
                'class' => 'btnforclosing',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'close',
                'access' => 'save',
                'action' => 'forclosing',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false
            ),
            'location' => array(
                'name' => 'location',
                'type' => 'input',
                'label' => 'Location',
                'class' => 'csitemname',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'location2' => array(
                'name' => 'location2',
                'type' => 'lookup',
                'label' => 'Location',
                'class' => 'cslocation2',
                'lookupclass' => 'locationhead2',
                'action' => 'lookuplocation',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'pallet' => array(
                'name' => 'pallet',
                'type' => 'input',
                'label' => 'Pallet',
                'class' => 'csitemname',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'pallet2' => array(
                'name' => 'pallet2',
                'type' => 'lookup',
                'label' => 'Pallet',
                'class' => 'cspallet2',
                'lookupclass' => 'pallethead2',
                'action' => 'lookuppallet',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'transtype' => array(
                'name' => 'transtype',
                'type' => 'input',
                'label' => 'Transaction Type',
                'class' => 'cstranstype',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'rcvecheckerloc' => array(
                'name' => 'rcvecheckerloc',
                'type' => 'actionbtn',
                'label' => 'PICK FROM LOCATION',
                'class' => 'btnrcvecheckerloc',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'batch_prediction',
                'access' => 'view',
                'action' => 'receivecheckerloc',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false,
                'confirm' => true,
                'confirmlabel' => 'Pick this Transaction from Location?'
            ),

            'qrgenerator' => array(
                'name' => 'qrgenerator',
                'type' => 'lookup',
                'label' => 'Lookup',
                'class' => 'csdqrgenerator sbccsreadonly',
                'lookupclass' => 'lookuprep_qr_codegenerator',
                'action' => 'lookuprep_qr_codegenerator',
                'readonly' => true,
                'style' => $this->style,
                'required' => true,
                'addedparams' => ['reporttype']
            ),

            'addtask' => array(
                'name' => 'addtask',
                'type' => 'actionbtn',
                'label' => 'ADD TASK',
                'class' => 'btnaddtask',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'grade',
                'access' => 'additem',
                'action' => 'addtask',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false,
                'confirm' => true,
                'confirmlabel' => 'Add task?',
                'addedparams' => ['palletid', 'itemid']
            ),
            'checkerdone' => array(
                'name' => 'checkerdone',
                'type' => 'actionbtn',
                'label' => 'DONE',
                'class' => 'btncheckerdone',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'done_all',
                'access' => 'view',
                'action' => 'checkerdone',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false,
                'confirm' => true,
                'confirmlabel' => 'Done checking?'
            ),
            'plno' => array(
                'name' => 'plno',
                'type' => 'input',
                'label' => 'Packing List No.',
                'class' => 'csplno',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 30
            ),
            'shipmentno' => array(
                'name' => 'shipmentno',
                'type' => 'input',
                'label' => 'Shipment No.',
                'class' => 'csshipmentno',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 30
            ),
            'invoiceno' => array(
                'name' => 'invoiceno',
                'type' => 'cinput',
                'label' => 'Proforma Invoice No.',
                'class' => 'csinvoiceno',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 30
            ),
            'pono' => array(
                'name' => 'pono',
                'type' => 'cinput',
                'label' => 'PO No.',
                'class' => 'cspono',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 30
            ),
            'crno' => array(
                'name' => 'crno',
                'type' => 'cinput',
                'label' => 'CR#',
                'class' => 'cscrno',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 30
            ),
            'drno' => array(
                'name' => 'drno',
                'type' => 'cinput',
                'label' => 'DR#',
                'class' => 'csdrno',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 30
            ),
            'cmdocno' => array(
                'name' => 'cmdocno',
                'type' => 'lookup',
                'label' => 'Sales Return',
                'class' => 'cscmdocno sbccsreadonly',
                'lookupclass' => 'lookupcmdocref',
                'action' => 'lookupcmdocref',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'rslip' => array(
                'name' => 'rslip',
                'type' => 'cinput',
                'label' => 'Refund Slip',
                'class' => 'csrslip',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 30
            ),
            'sicsino' => array(
                'name' => 'sicsino',
                'type' => 'cinput',
                'label' => 'SI/CSI#',
                'class' => 'cssicsino',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 30
            ),
            'csino' => array(
                'name' => 'csino',
                'type' => 'cinput',
                'label' => 'CSI#',
                'class' => 'cscsino',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 30
            ),
            'chsino' => array(
                'name' => 'chsino',
                'type' => 'cinput',
                'label' => 'CHSI#',
                'class' => 'cschsino',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 30
            ),
            'swsno' => array(
                'name' => 'swsno',
                'type' => 'cinput',
                'label' => 'SWS#',
                'class' => 'csswsno',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 30
            ),
            'rfno' => array(
                'name' => 'rfno',
                'type' => 'cinput',
                'label' => 'RF#',
                'class' => 'csrfno',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 30
            ),
            'plateno' => array(
                'name' => 'plateno',
                'type' => 'cinput',
                'label' => 'Plate No.',
                'class' => 'csplateno',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 30
            ),
            'vinno' => array(
                'name' => 'vinno',
                'type' => 'cinput',
                'label' => 'VIN No.',
                'class' => 'csvinno',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 30
            ),
            'manufacturer' => array(
                'name' => 'manufacturer',
                'type' => 'cinput',
                'label' => 'Manufacturer',
                'class' => 'csmanufacturer',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 30
            ),
            'fyear' => array(
                'name' => 'fyear',
                'type' => 'cinput',
                'label' => 'Year',
                'class' => 'csfyear',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 30
            ),
            'fueltype' => array(
                'name' => 'fueltype',
                'type' => 'cinput',
                'label' => 'Fuel Type',
                'class' => 'csfueltype',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 30
            ),
            'engine' => array(
                'name' => 'engine',
                'type' => 'cinput',
                'label' => 'Engine',
                'class' => 'csengine',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 30
            ),
            'pricetype' => array(
                'name' => 'pricetype',
                'type' => 'lookup',
                'label' => 'Price Type',
                'class' => 'cspricegroup sbccsreadonly',
                'lookupclass' => 'lookuppricetype',
                'action' => 'lookuppricegroup',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            // GLEN 06.19.2021
            'port' => array(
                'name' => 'port',
                'type' => 'cinput',
                'label' => 'Port',
                'class' => 'csport',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'arrival' => array(
                'name' => 'arrival',
                'type' => 'cinput',
                'label' => 'Time Arrival',
                'class' => 'csarrival',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 4
            ),
            'departure' => array(
                'name' => 'departure',
                'type' => 'cinput',
                'label' => 'Time Departure',
                'class' => 'csdeparture',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 4
            ),
            'enginerpm' => array(
                'name' => 'enginerpm',
                'type' => 'cinput',
                'label' => 'Main Engine RPM.',
                'class' => 'csenginerpm',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'timeatsea' => array(
                'name' => 'timeatsea',
                'type' => 'cinput',
                'label' => 'Time At Sea',
                'class' => 'cstimeatsea',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'avespeed' => array(
                'name' => 'avespeed',
                'type' => 'cinput',
                'label' => 'Average Speed',
                'class' => 'csavespeed',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'enginefueloil' => array(
                'name' => 'enginefueloil',
                'type' => 'cinput',
                'label' => 'Main Engine Fuel Oil Consumption',
                'class' => 'csenginefueloil',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'cylinderoil' => array(
                'name' => 'cylinderoil',
                'type' => 'cinput',
                'label' => 'Cylinder Oil Consumption',
                'class' => 'cscylinderoil',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'enginelubeoil' => array(
                'name' => 'enginelubeoil',
                'type' => 'cinput',
                'label' => 'Main Engine Lube Oil Sump Tank Sounding',
                'class' => 'csenginelubeoil',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'hiexhaust' => array(
                'name' => 'hiexhaust',
                'type' => 'cinput',
                'label' => 'Highest Exhaust Temp/Cyl Nr.',
                'class' => 'cshiexhaust',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'loexhaust' => array(
                'name' => 'loexhaust',
                'type' => 'cinput',
                'label' => 'Lowest Exhaust Temp/Cyl Nr.',
                'class' => 'csloexhaust',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'exhaustgas' => array(
                'name' => 'exhaustgas',
                'type' => 'cinput',
                'label' => 'T/C Exhaust Gas Outlet Temperature',
                'class' => 'csexhaustgas',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'hicoolwater' => array(
                'name' => 'hicoolwater',
                'type' => 'cinput',
                'label' => 'Cool Water Highest/Cyl Nr.',
                'class' => 'cshicoolwater',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'locoolwater' => array(
                'name' => 'locoolwater',
                'type' => 'cinput',
                'label' => 'Cool Water Lowest/Cyl Nr.',
                'class' => 'cslocoolwater',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'lopress' => array(
                'name' => 'lopress',
                'type' => 'cinput',
                'label' => 'L.O. Press.',
                'class' => 'cslopress',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'fwpress' => array(
                'name' => 'fwpress',
                'type' => 'cinput',
                'label' => 'Cool F.W. Press',
                'class' => 'csfwpress',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'airpress' => array(
                'name' => 'airpress',
                'type' => 'cinput',
                'label' => 'Scay. Air Press',
                'class' => 'csairpress',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'airinletpress' => array(
                'name' => 'airinletpress',
                'type' => 'cinput',
                'label' => 'Scay. Air Inlet Temp',
                'class' => 'csairinletpress',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'coolerin' => array(
                'name' => 'coolerin',
                'type' => 'cinput',
                'label' => 'LO. Cooler In',
                'class' => 'cscoolerin',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'coolerout' => array(
                'name' => 'coolerout',
                'type' => 'cinput',
                'label' => 'LO. Cooler Out',
                'class' => 'cscoolerout',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'coolerfwin' => array(
                'name' => 'coolerfwin',
                'type' => 'cinput',
                'label' => 'F.W. Cooler F.W. In',
                'class' => 'cscoolerfwin',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'coolerfwout' => array(
                'name' => 'coolerfwout',
                'type' => 'cinput',
                'label' => 'F.W. Cooler F.W. Out',
                'class' => 'cscoolerfwout',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'seawatertemp' => array(
                'name' => 'seawatertemp',
                'type' => 'cinput',
                'label' => 'Sea Water Temp',
                'class' => 'csseawatertemp',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'engroomtemp' => array(
                'name' => 'engroomtemp',
                'type' => 'cinput',
                'label' => 'Eng Room Temp',
                'class' => 'csengroomtemp',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'begcash' => array(
                'name' => 'begcash',
                'type' => 'cinput',
                'label' => 'Cash Beginning',
                'class' => 'csbegcash',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'begcashlabel' => array(
                'name' => 'begcashlabel',
                'type' => 'cinput',
                'label' => 'Cash Beginning',
                'class' => 'csbegcashlabel sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'addcash' => array(
                'name' => 'addcash',
                'type' => 'cinput',
                'label' => 'Add Cash Received',
                'class' => 'csaddcash',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'addcashlabel' => array(
                'name' => 'addcashlabel',
                'type' => 'cinput',
                'label' => 'Add Cash Received',
                'class' => 'csaddcashlabel sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'totalcash' => array(
                'name' => 'totalcash',
                'type' => 'cinput',
                'label' => 'Total Cash',
                'class' => 'cstotalcash sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'totalcashlabel' => array(
                'name' => 'totalcashlabel',
                'type' => 'cinput',
                'label' => 'Total Cash',
                'class' => 'cstotalcashlabel sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'totalexpenses' => array(
                'name' => 'totalexpenses',
                'type' => 'cinput',
                'label' => 'Total Expenses',
                'class' => 'cstotalexpenses sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'totalexpenseslabel' => array(
                'name' => 'totalexpenseslabel',
                'type' => 'cinput',
                'label' => 'Total Expenses',
                'class' => 'cstotalexpenseslabel sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'cashbalance' => array(
                'name' => 'cashbalance',
                'type' => 'cinput',
                'label' => 'Cash Balance',
                'class' => 'cscashbalance sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'cashbalancelabel' => array(
                'name' => 'cashbalancelabel',
                'type' => 'cinput',
                'label' => 'Cash Balance',
                'class' => 'cscashbalancelabel sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'usagefeeamt' => array(
                'name' => 'usagefeeamt',
                'type' => 'cinput',
                'label' => 'Usage Fee/PPA Clearance',
                'class' => 'csusagefeeamt',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'usagefee' => array(
                'name' => 'usagefee',
                'type' => 'cinput',
                'label' => 'Usage Fee/PPA Clearance Notes',
                'class' => 'csusagefee',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'mooringamt' => array(
                'name' => 'mooringamt',
                'type' => 'cinput',
                'label' => 'Mooring/Unmooring',
                'class' => 'csmooringamt',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'mooring' => array(
                'name' => 'mooring',
                'type' => 'cinput',
                'label' => 'Mooring/Unmooring Notes',
                'class' => 'csmooring',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'coastguardclearanceamt' => array(
                'name' => 'coastguardclearanceamt',
                'type' => 'cinput',
                'label' => 'Coast Guard Clearance',
                'class' => 'cscoastguardclearanceamt',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'coastguardclearance' => array(
                'name' => 'coastguardclearance',
                'type' => 'cinput',
                'label' => 'Coast Guard Clearance Notes',
                'class' => 'cscoastguardclearance',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'pilotageamt' => array(
                'name' => 'pilotageamt',
                'type' => 'cinput',
                'label' => 'Pilotage',
                'class' => 'cspilotageamt',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'pilotage' => array(
                'name' => 'pilotage',
                'type' => 'cinput',
                'label' => 'Pilotage Notes',
                'class' => 'cspilotage',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'lifebouyamt' => array(
                'name' => 'lifebouyamt',
                'type' => 'cinput',
                'label' => 'Life Bouy/Marker',
                'class' => 'cslifebouyamt',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'lifebouy' => array(
                'name' => 'lifebouy',
                'type' => 'cinput',
                'label' => 'Life Bouy/Marker Notes',
                'class' => 'cslifebouy',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'bunkeringamt' => array(
                'name' => 'bunkeringamt',
                'type' => 'cinput',
                'label' => 'Bunkering Permit',
                'class' => 'csbunkeringamt',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'bunkering' => array(
                'name' => 'bunkering',
                'type' => 'cinput',
                'label' => 'Bunkering Permit Notes',
                'class' => 'csbunkering',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'sopamt' => array(
                'name' => 'sopamt',
                'type' => 'cinput',
                'label' => 'SOP',
                'class' => 'cssopamt',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'sop' => array(
                'name' => 'sop',
                'type' => 'cinput',
                'label' => 'SOP Notes',
                'class' => 'cssop',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'othersamt' => array(
                'name' => 'othersamt',
                'type' => 'cinput',
                'label' => 'Others',
                'class' => 'csothersamt',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'others' => array(
                'name' => 'others',
                'type' => 'cinput',
                'label' => 'Others Notes',
                'class' => 'csothers',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'purchaseamt' => array(
                'name' => 'purchaseamt',
                'type' => 'cinput',
                'label' => 'Purchases',
                'class' => 'cspurchaseamt',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'purchase' => array(
                'name' => 'purchase',
                'type' => 'cinput',
                'label' => 'Purchases Notes',
                'class' => 'cspurchase',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'crewsubsistenceamt' => array(
                'name' => 'crewsubsistenceamt',
                'type' => 'cinput',
                'label' => 'Crew Subsistence',
                'class' => 'cscrewsubsistenceamt',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'crewsubsistence' => array(
                'name' => 'crewsubsistence',
                'type' => 'cinput',
                'label' => 'Crew Subsistence Notes',
                'class' => 'cscrewsubsistence',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'waterexpamt' => array(
                'name' => 'waterexpamt',
                'type' => 'cinput',
                'label' => 'Water Expense',
                'class' => 'cswaterexpamt',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'waterexp' => array(
                'name' => 'waterexp',
                'type' => 'cinput',
                'label' => 'Water Expense Notes',
                'class' => 'cswaterexp',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'localtranspoamt' => array(
                'name' => 'localtranspoamt',
                'type' => 'cinput',
                'label' => 'Local Transportation',
                'class' => 'cslocaltranspoamt',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'localtranspo' => array(
                'name' => 'localtranspo',
                'type' => 'cinput',
                'label' => 'Local Transportation Notes',
                'class' => 'cslocaltranspo',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'others2amt' => array(
                'name' => 'others2amt',
                'type' => 'cinput',
                'label' => 'Others',
                'class' => 'csothers2amt',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'others2' => array(
                'name' => 'others2',
                'type' => 'cinput',
                'label' => 'Others Notes',
                'class' => 'csothers2',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'reqcash' => array(
                'name' => 'reqcash',
                'type' => 'cinput',
                'label' => 'Requested Cash',
                'class' => 'csreqcash',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'reqcashlabel' => array(
                'name' => 'reqcashlabel',
                'type' => 'cinput',
                'label' => 'Requested Cash',
                'class' => 'csreqcashlabel sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'loa' => array(
                'name' => 'loa',
                'type' => 'input',
                'label' => 'Life of Asset (Months)',
                'class' => 'csloa',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'warranty' => array(
                'name' => 'warranty',
                'type' => 'date',
                'label' => 'Warranty',
                'class' => 'cswarranty',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'leasedate' => array(
                'name' => 'leasedate',
                'type' => 'date',
                'label' => 'Lease Date',
                'class' => 'csleasedate',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'renewaldate' => array(
                'name' => 'renewaldate',
                'type' => 'date',
                'label' => 'Renewal Date',
                'class' => 'csrenewaldate',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'brdocno' => array(
                'name' => 'brdocno',
                'type' => 'lookup',
                'label' => 'BR #',
                'class' => 'csbrdocno sbccsreadonly',
                'lookupclass' => 'lookupbrdocno',
                'action' => 'lookupbrdocno',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'addedparams' => ['projectid', 'subproject']
            ),
            'iscontractor' => array(
                'name' => 'iscontractor',
                'type' => 'checkbox',
                'label' => 'Subcontractor',
                'class' => 'csiscontractor',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isconsign' => array(
                'name' => 'isconsign',
                'type' => 'checkbox',
                'label' => 'Consignment',
                'class' => 'csisconsign',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'isnopay' => array(
                'name' => 'isnopay',
                'type' => 'checkbox',
                'label' => 'No Pay',
                'class' => 'csisnopay',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'isconvert' => array(
                'name' => 'isconvert',
                'type' => 'checkbox',
                'label' => 'Convert',
                'class' => 'csisconvert',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'radiooption' => array(
                'name' => 'poption',
                'label' => 'Option',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Balance Sheet', 'value' => 0, 'color' => 'red'],
                    ['label' => 'Income Statement', 'value' => 1, 'color' => 'green']
                )
            ),

            'lblacquisition' => array(
                'name' => 'lblacquisition',
                'type' => 'label',
                'label' => 'Acquisition',
                'class' => 'cslblacquisition',
                'style' => 'font-weight:bold;font-size:20px'
            ),
            'lbldepreciation' => array(
                'name' => 'lbldepreciation',
                'type' => 'label',
                'label' => 'Depreciation',
                'class' => '',
                'style' => 'font-weight:bold;font-size:20px'
            ),
            'lbllocation' => array(
                'name' => 'lbllocation',
                'type' => 'label',
                'label' => 'Current Location',
                'class' => '',
                'style' => 'font-weight:bold;font-size:20px'
            ),
            'lblvehicleinfo' => array(
                'name' => 'lblvehicleinfo',
                'type' => 'label',
                'label' => 'Vehicle Info',
                'class' => '',
                'style' => 'font-weight:bold;font-size:20px'
            ),
            'lblrem' => array(
                'name' => 'lblrem',
                'type' => 'label',
                'label' => 'NOTES',
                'class' => '',
                'style' => 'font-weight:bold'
            ),
            'lblsource' => array(
                'name' => 'lblsource',
                'type' => 'label',
                'label' => 'SOURCE',
                'class' => '',
                'style' => 'font-weight:bold'
            ),
            'lbldestination' => array(
                'name' => 'lbldestination',
                'type' => 'label',
                'label' => 'DESTINATION',
                'class' => '',
                'style' => 'font-weight:bold'
            ),
            'lblpassbook' => array(
                'name' => 'lblpassbook',
                'type' => 'label',
                'label' => 'BASED ON PASSBOOK',
                'class' => '',
                'style' => 'font-weight:bold'
            ),
            'lblreconcile' => array(
                'name' => 'lblreconcile',
                'type' => 'label',
                'label' => 'RECONCILED',
                'class' => '',
                'style' => 'font-weight:bold'

            ),
            'lblearned' => array(
                'name' => 'lblearned',
                'type' => 'label',
                'label' => 'EARNED AND CHARGES',
                'class' => '',
                'style' => 'font-weight:bold'
            ),
            'lblcleared' => array(
                'name' => 'lblcleared',
                'type' => 'label',
                'label' => 'ITEMS MARKED CLEARED',
                'class' => '',
                'style' => 'font-weight:bold'
            ),
            'lblrecondate' => array(
                'name' => 'lblrecondate',
                'type' => 'label',
                'label' => 'Reconcile date as of:',
                'class' => '',
                'style' => 'font-weight:bold'
            ),
            'lblendingbal' => array(
                'name' => 'lblendingbal',
                'type' => 'label',
                'label' => 'Ending Balance:',
                'class' => '',
                'style' => 'font-weight:bold'
            ),
            'lblunclear' => array(
                'name' => 'lblunclear',
                'type' => 'label',
                'label' => 'Unclear:',
                'class' => '',
                'style' => 'font-weight:bold'
            ),
            'recondate' => array(
                'name' => 'recondate',
                'type' => 'input',
                'label' => '',
                'class' => 'csrecondate',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'endingbal' => array(
                'name' => 'endingbal',
                'type' => 'input',
                'label' => '',
                'class' => 'csendingbal',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'unclear' => array(
                'name' => 'unclear',
                'type' => 'input',
                'label' => '',
                'class' => 'csunclear',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'optiongatherby' => array(
                'name' => 'gatherby',
                'label' => 'Gather By',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Transaction Date', 'value' => 0, 'color' => 'red'],
                    ['label' => 'Check Date', 'value' => 1, 'color' => 'red'],
                    ['label' => 'Clear Date', 'value' => 2, 'color' => 'orange']
                )
            ),
            'optionstatus' => array(
                'name' => 'status',
                'label' => 'Status',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Unposted', 'value' => 0, 'color' => 'red'],
                    ['label' => 'Posted', 'value' => 1, 'color' => 'green']
                )
            ),
            'optionuploading' => array(
                'name' => 'utype',
                'label' => 'Option',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'New Items', 'value' => 'newitem', 'color' => 'green'],
                    ['label' => 'New Customers', 'value' => 'newcustomer', 'color' => 'green'],
                    ['label' => 'New Suppliers', 'value' => 'newsupplier', 'color' => 'green'],
                    ['label' => 'New Warehouses', 'value' => 'newwh', 'color' => 'green'],
                    ['label' => 'Update Supplier', 'value' => 'updatesupplier', 'color' => 'green'],
                    ['label' => 'Update Items', 'value' => 'updateitem', 'color' => 'green'],
                    ['label' => 'Update Customers', 'value' => 'updatecustomer', 'color' => 'green'],
                    ['label' => 'Update Warehouses', 'value' => 'updatewh', 'color' => 'green']
                )
            ),
            'plateno' => array(
                'name' => 'plateno',
                'type' => 'cinput',
                'label' => 'Plate Number',
                'class' => 'csplatenumber',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'revision' => array(
                'name' => 'revision',
                'type' => 'input',
                'label' => 'Revision',
                'class' => 'csrevision',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
            ),
            'dparentcodewh' => array(
                'name' => 'dparentcodewh',
                'type' => 'lookup',
                'label' => 'Parent Code',
                'labeldata' => 'parent~parentnamewh',
                'class' => 'csdparentcodewh sbccsreadonly',
                'lookupclass' => 'whparentcode',
                'action' => 'lookupclient',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'status' => array(
                'name' => 'status',
                'type' => 'input',
                'label' => 'Status',
                'class' => 'csstatus sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'status2' => array(
                'name' => 'status2',
                'type' => 'input',
                'label' => 'Status (Supervisor)',
                'class' => 'csstatus sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isapprover' => array(
                'name' => 'isapprover',
                'type' => 'checkbox',
                'label' => 'Approver',
                'class' => 'csisactive',
                'readonly' => false,
                'style' => $this->style,
                'required' => true
            ),
            'issupervisor' => array(
                'name' => 'issupervisor',
                'type' => 'checkbox',
                'label' => 'Supervisor',
                'class' => 'csisactive',
                'readonly' => false,
                'style' => $this->style,
                'required' => true
            ),
            'isbudgetapprover' => array(
                'name' => 'isbudgetapprover',
                'type' => 'checkbox',
                'label' => 'Budget Approver',
                'class' => 'csisactive',
                'readonly' => false,
                'style' => $this->style,
                'required' => true
            ),
            'isnobio' => array(
                'name' => 'isnobio',
                'type' => 'checkbox',
                'label' => 'No Biometric',
                'class' => 'csisactive',
                'readonly' => false,
                'style' => $this->style,
                'required' => true
            ),
            'rolename' => array(
                'name' => 'rolename',
                'type' => 'lookup',
                'label' => 'Role',
                'class' => 'csrolename sbccsreadonly',
                'lookupclass' => 'lookuprole',
                'action' => 'lookuprole',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'subcontractor' => array(
                'name' => 'subcontractor',
                'type' => 'lookup',
                'label' => 'Subcontractor',
                'class' => 'cssubcontractor sbccsreadonly',
                'lookupclass' => 'lookupsubcontractor',
                'action' => 'lookupsubcontractor',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'islabor' => array(
                'name' => 'islabor',
                'type' => 'checkbox',
                'label' => 'Labor/Services',
                'class' => 'csislabor',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'reqtrainname' => array(
                'name' => 'reqtrainname',
                'type' => 'lookup',
                'label' => 'Request Training',
                'class' => 'csrolename sbccsreadonly',
                'lookupclass' => 'lookupreqtrain',
                'action' => 'lookupreqtrain',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'frolename' => array(
                'name' => 'frolename',
                'type' => 'input',
                'label' => 'From Role',
                'class' => 'csfrolename sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'trolename' => array(
                'name' => 'trolename',
                'type' => 'lookup',
                'label' => 'To Role',
                'class' => 'csrolename sbccsreadonly',
                'lookupclass' => 'lookuprole',
                'action' => 'lookuprole',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'frprojectname' => array(
                'name' => 'frprojectname',
                'type' => 'input',
                'label' => 'From Project',
                'class' => 'csfrproject sbccsreadonly ',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),

            'toprojectname' => array(
                'name' => 'toprojectname',
                'type' => 'lookup',
                'label' => 'To Project',
                'class' => 'cstoproject',
                'lookupclass' => 'lookupproject',
                'action' => 'lookupproject',
                'readonly' => false,
                'style' => $this->style,
                'required' => true
            ),


            'ftruckname' => array(
                'name' => 'ftruckname',
                'type' => 'input',
                'label' => 'From Truck/Asset',
                'class' => 'csftruckname sbccsreadonly',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),

            'totruckname' => array(
                'name' => 'totruckname',
                'type' => 'lookup',
                'label' => 'To Truck/Asset',
                'class' => 'cstotruckname',
                'lookupclass' => 'lookupitem',
                'action' => 'lookupitem',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),

            'invdate' => array(
                'name' => 'invdate',
                'type' => 'date',
                'label' => 'Invoice Date',
                'class' => 'csinvdate',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'documenttype' => array(
                'name' => 'documenttype',
                'type' => 'lookup',
                'label' => 'Document Type',
                'class' => 'csdoctype sbccsreadonly',
                'lookupclass' => 'lookupdtdocumenttype',
                'action' => 'lookupdtdocumenttype',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'layref' => array(
                'name' => 'layref',
                'type' => 'input',
                'label' => 'Layaway Ref.',
                'class' => 'cslayref',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'poref' => array(
                'name' => 'poref',
                'type' => 'input',
                'label' => 'PO Ref.',
                'class' => 'csporef',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'soref' => array(
                'name' => 'soref',
                'type' => 'input',
                'label' => 'SO No.',
                'class' => 'cssoref',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'isewt' => array(
                'name' => 'isewt',
                'type' => 'checkbox',
                'label' => 'With EWT',
                'class' => 'csisewt',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'isexcess' => array(
                'name' => 'isexcess',
                'type' => 'checkbox',
                'label' => 'With Excise Tax',
                'class' => 'csisexcess',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'dtdivname' => array(
                'name' => 'dtdivname',
                'type' => 'lookup',
                'label' => 'Division',
                'class' => 'csdtdivname sbccsreadonly',
                'lookupclass' => 'lookupdtdivision',
                'action' => 'lookupdtdivision',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'ewtcode' => array(
                'name' => 'ewtcode',
                'type' => 'lookup',
                'label' => 'EWT Code',
                'field' => 'ewtcode',
                'align' => 'text-left',
                'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                'readonly' => true,
                'lookupclass' => 'hewt',
                'action' => 'lookupewt'
            ),
            'ewtrate' => array(
                'name' => 'ewtrate',
                'type' => 'input',
                'label' => 'EWT Rate',
                'field' => 'ewtrate',
                'align' => 'text-left',
                'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                'readonly' => true
            ),
            'isapproved' => array(
                'name' => 'isapproved',
                'type' => 'checkbox',
                'label' => 'Approved',
                'class' => 'csisapproved',
                'readonly' => false,
                'style' => $this->style,
                'required' => true
            ),
            'isreturned' => array(
                'name' => 'isreturned',
                'type' => 'checkbox',
                'label' => 'Returned',
                'class' => 'csisreturned',
                'readonly' => false,
                'style' => $this->style,
                'required' => true
            ),
            'isdeductible' => array(
                'name' => 'isdeductible',
                'type' => 'checkbox',
                'label' => 'Returned',
                'class' => 'csisdeductible',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'justify' => array(
                'name' => 'justify',
                'type' => 'lookup',
                'label' => 'Justification',
                'class' => 'csjustify sbccsreadonly',
                'lookupclass' => 'lookupjustify',
                'action' => 'lookupjustify',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'incident' => array(
                'name' => 'incident',
                'type' => 'lookup',
                'label' => 'Incident',
                'class' => 'csincident sbccsreadonly',
                'lookupclass' => 'lookupincident',
                'action' => 'lookupincident',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),

            'isrefunded' => array(
                'name' => 'isrefunded',
                'type' => 'checkbox',
                'label' => 'Refunded',
                'class' => 'csisrefunded',
                'readonly' => false,
                'style' => $this->style,
                'required' => true
            ),
            'isvewt' => array(
                'name' => 'isvewt',
                'type' => 'checkbox',
                'label' => 'With EWT (for RR with VAT)',
                'class' => 'csisvewt',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'dtdetail' => array(
                'name' => 'dtdetail',
                'type' => 'lookup',
                'label' => 'Detail',
                'align' => 'text-align',
                'style' => 'width:100%;whiteSpace:normal;',
                'readonly' => true,
                'lookupclass' => 'lookupdtdetails',
                'action' => 'lookupdtdetails'
            ),
            'dtissue' => array(
                'name' => 'dtissue',
                'type' => 'lookup',
                'label' => 'Issue',
                'align' => 'text-align',
                'style' => 'width:100%;whiteSpace:normal;',
                'readonly' => true,
                'lookupclass' => 'lookupdtissues',
                'action' => 'lookupdtissues'
            ),
            'dtrem' => array(
                'name' => 'dtrem',
                'type' => 'input',
                'label' => 'Notes',
                'align' => 'text-left',
                'style' => 'width:100%;whiteSpace:normal;',
                'readonly' => false
            ),
            'ispositem' => array(
                'name' => 'ispositem',
                'type' => 'checkbox',
                'label' => 'POS Item',
                'class' => 'csispositem',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isprintable' => array(
                'name' => 'isprintable',
                'type' => 'checkbox',
                'label' => 'Printable',
                'class' => 'csisprintable',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isbranch' => array(
                'name' => 'isbranch',
                'type' => 'checkbox',
                'label' => 'Branch',
                'class' => 'csisbranch',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'lblbranch' => array(
                'name' => 'lblbranch',
                'type' => 'label',
                'label' => 'Branch',
                'class' => '',
                'style' => 'font-weight:bold'
            ),
            'lbldateid' => array(
                'name' => 'lbldateid',
                'type' => 'label',
                'label' => 'Date',
                'class' => '',
                'style' => 'font-weight:bold'
            ),
            'isallitem' => array(
                'name' => 'isallitem',
                'type' => 'checkbox',
                'label' => 'All items',
                'class' => 'csisallitem',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'issynced' => array(
                'name' => 'issynced',
                'type' => 'checkbox',
                'label' => 'Sync to branch',
                'class' => 'csissynced',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isannual' => array(
                'name' => 'isannual',
                'type' => 'checkbox',
                'label' => 'Annual',
                'class' => 'csisannual',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'dbranchname' => array(
                'name' => 'dbranchname',
                'type' => 'lookup',
                'label' => 'Branch Name',
                'labeldata' => 'branchcode~branchname',
                'class' => 'cswhname sbccsreadonly',
                'lookupclass' => 'hbranch',
                'action' => 'lookupclient',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'branchname' => array(
                'name' => 'branchname',
                'type' => 'lookup',
                'label' => 'Branch',
                'class' => 'csbranch',
                'lookupclass' => 'dbranch',
                'action' => 'lookupclient',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'empbranchname' => array(
                'name' => 'empbranchname',
                'type' => 'lookup',
                'label' => 'Branch',
                'class' => 'csbranch',
                'lookupclass' => 'dbranch',
                'action' => 'lookupclient',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'rsbranch' => array(
                'name' => 'rsbranch',
                'type' => 'lookup',
                'label' => 'Branch Name',
                'labeldata' => 'tobranchcode~tobranchname',
                'class' => 'cswhname sbccsreadonly',
                'lookupclass' => 'lookuprsbranch',
                'action' => 'lookuprsbranch',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'salesgroup' => array(
                'name' => 'salesgroup',
                'type' => 'lookup',
                'label' => 'Sales Group',
                'class' => 'cssalesgroup sbccsreadonly',
                'lookupclass' => 'lookupsalesgroup',
                'action' => 'lookupsalesgroup',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'lblreceived' => array(
                'name' => 'lblreceived',
                'type' => 'label',
                'label' => 'RECEIVED!',
                'class' => '',
                'style' => 'font-family:Century Gothic;font-size:30px;font-weight:bold;background-color: yellow;'
            ),

            'lblattached' => array(
                'name' => 'lblattached',
                'type' => 'label',
                'label' => 'This transaction has attached documents.',
                'class' => '',
                'style' => 'font-family:Century Gothic;font-size:20px;font-weight:bold;color: red;'
            ),
            'lblinvreq' => array(
                'name' => 'lblinvreq',
                'type' => 'label',
                'label' => 'INVOICE REQUIRED!',
                'class' => '',
                'style' => 'font-family:Century Gothic;font-size:30px;font-weight:bold;background-color: yellow;'
            ),
            'lblforapproval' => array(
                'name' => 'lblforapproval',
                'type' => 'label',
                'label' => 'FOR APPROVAL!',
                'class' => '',
                'style' => 'font-family:Century Gothic;font-size:30px;font-weight:bold;background-color: yellow;'
            ),
            'lblapproved' => array(
                'name' => 'lblapproved',
                'type' => 'label',
                'label' => 'APPROVED!',
                'class' => '',
                'style' => 'font-family:Century Gothic;font-size:30px;font-weight:bold;background-color: yellow;'
            ),
            'lbllocked' => array(
                'name' => 'lbllocked',
                'type' => 'label',
                'label' => 'FOR REVIEW',
                'class' => '',
                'style' => 'font-family:Century Gothic;font-size:30px;font-weight:bold;background-color: yellow;'
            ),
            'lblitemdesc' => array(
                'name' => 'lblitemdesc',
                'type' => 'label',
                'label' => 'ITEM DESCRIPTION',
                'class' => '',
                'style' => 'font-weight:bold'
            ),
            'lblaccessories' => array(
                'name' => 'lblaccessories',
                'type' => 'label',
                'label' => 'ACCESSORIES',
                'class' => '',
                'style' => 'font-weight:bold'
            ),
            'lblsubmit' => array(
                'name' => 'lblsubmit',
                'type' => 'label',
                'label' => 'SUBMITTED',
                'class' => '',
                'style' => 'font-weight:bold;font-size:15px;font-family:Century Gothic;color: green;'
            ),
            'itemdescription' => array(
                'name' => 'itemdescription',
                'type' => 'wysiwyg',
                'label' => 'Item Desc',
                'class' => 'csitemdescription sbccsreadonly',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'itemdesc' => array(
                'name' => 'itemdesc',
                'type' => 'wysiwyg',
                'label' => 'Item Name',
                'class' => 'csitemdescription sbccsreadonly',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'itemdesc2' => array(
                'name' => 'itemdesc2',
                'type' => 'input',
                'label' => 'Item Name (PR)',
                'class' => 'csitemdescription sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'specs' => array(
                'name' => 'specs',
                'type' => 'input',
                'label' => 'Specification',
                'class' => 'csspecs sbccsreadonly',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'specs2' => array(
                'name' => 'specs2',
                'type' => 'input',
                'label' => 'Specifications (PR)',
                'class' => 'csspecs sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'duration' => array(
                'name' => 'duration',
                'type' => 'input',
                'label' => 'Duration',
                'class' => 'csspecs sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'accessories' => array(
                'name' => 'accessories',
                'type' => 'wysiwyg',
                'label' => 'Accessories',
                'class' => 'csaccessories sbccsreadonly',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'pickjo' => array(
                'name' => 'pickjo',
                'type' => 'button',
                'label' => 'PICK JO',
                'class' => 'cspickjo',
                'lookupclass' => 'pendingjbsummaryshortcut',
                'action' => 'pendingjbsummary',
                'action2' => 'lookup',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'confirm' => true,
                'confirmlabel' => 'Proceed to pick JO?'
            ),
            'transnumlog' => array(
                'name' => 'transnumlog',
                'type' => 'button',
                'label' => 'LOGS',
                'class' => 'cstransnumlogs',
                'lookupclass' => 'lookuplogs',
                'action' => 'lookupsetup',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
            ),
            'substage' => array(
                'name' => 'substage',
                'type' => 'input',
                'label' => 'Substage',
                'align' => 'text-left',
                'style' => 'width:100%;whiteSpace:normal;',
                'readonly' => false
            ),
            'designation' => array(
                'name' => 'designation',
                'type' => 'input',
                'label' => 'Designation',
                'class' => 'csdesignation',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'contactname' => array(
                'name' => 'contactname',
                'type' => 'input',
                'label' => 'Contact Name',
                'class' => 'cscontactname',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'source' => array(
                'name' => 'source',
                'type' => 'lookup',
                'label' => 'Source',
                'class' => 'cssource sbccsreadonly',
                'lookupclass' => 'lookupsource',
                'action' => 'lookupsource',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'sourcename' => array(
                'name' => 'sourcename',
                'type' => 'lookup',
                'label' => 'Source Description',
                'class' => 'cssourcedesc sbccsreadonly',
                'lookupclass' => 'lookupsourcename',
                'action' => 'lookupsourcename',
                'readonly' => true,
                'style' => $this->style,
                'required' => true,
                'addedparams' => ['source']
            ),
            'participant' => array(
                'name' => 'participant',
                'type' => 'lookup',
                'label' => 'Participant',
                'class' => 'csparticipant sbccsreadonly',
                'lookupclass' => 'lookupparticipant',
                'action' => 'lookupparticipant',
                'readonly' => true,
                'style' => $this->style,
                'required' => true,
                'addedparams' => ['source', 'sourceid']
            ),
            'industry' => array(
                'name' => 'industry',
                'type' => 'input',
                'label' => 'Industry',
                'class' => 'csindustry',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
            ),
            'agentcno' => array(
                'name' => 'agentcno',
                'type' => 'input',
                'label' => 'Sales Contact #',
                'class' => 'csagentcno',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
            ),
            'lbltimesetting' => array(
                'name' => 'lbltimesetting',
                'type' => 'label',
                'label' => 'Lead Time Settings',
                'class' => '',
                'style' => 'font-weight:bold'
            ),
            'leadfrom' => array(
                'name' => 'leadfrom',
                'type' => 'input',
                'label' => 'Lead Time From ',
                'class' => 'csleadfrom',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'leadto' => array(
                'name' => 'leadto',
                'type' => 'input',
                'label' => 'Lead Time To ',
                'class' => 'csleadto',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'leaddur' => array(
                'name' => 'leaddur',
                'type' => 'input',
                'label' => 'Lead Time Duration',
                'class' => 'csleaddur',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
            ),
            'advised' => array(
                'name' => 'advised',
                'type' => 'checkbox',
                'label' => 'To Be Advised',
                'class' => 'csisvalid',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'isvalid' => array(
                'name' => 'isvalid',
                'type' => 'checkbox',
                'label' => 'Is Other Validity',
                'class' => 'csisvalid',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'isnologin' => array(
                'name' => 'isnologin',
                'type' => 'checkbox',
                'label' => 'NO MORNING IN',
                'class' => 'csisnologin',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'isnologout' => array(
                'name' => 'isnologout',
                'type' => 'checkbox',
                'label' => 'NO AFTERNOON OUT',
                'class' => 'csisnologout',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'isnombrkin' => array(
                'name' => 'isnombrkin',
                'type' => 'checkbox',
                'label' => 'NO MORNING BREAK IN',
                'class' => 'csisnombrkin',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'isnombrkout' => array(
                'name' => 'isnombrkout',
                'type' => 'checkbox',
                'label' => 'NO MORNING BREAK OUT',
                'class' => 'csisnombrkout',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'isnolunchout' => array(
                'name' => 'isnolunchout',
                'type' => 'checkbox',
                'label' => 'NO LUNCH BREAK OUT',
                'class' => 'csisnolunchout',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'isnolunchin' => array(
                'name' => 'isnolunchin',
                'type' => 'checkbox',
                'label' => 'NO LUNCH BREAK IN',
                'class' => 'csisnolunchin',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'isnopbrkout' => array(
                'name' => 'isnopbrkout',
                'type' => 'checkbox',
                'label' => 'NO AFTERNOON BREAK OUT',
                'class' => 'csisnopbrkout',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'isnopbrkin' => array(
                'name' => 'isnopbrkin',
                'type' => 'checkbox',
                'label' => 'NO AFTERNOON BREAK IN',
                'class' => 'csisnopbrkin',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'isnologpin' => array(
                'name' => 'isnologpin',
                'type' => 'checkbox',
                'label' => 'NO AFTERNOON IN',
                'class' => 'csisnologpin',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'isnologunder' => array(
                'name' => 'isnologunder',
                'type' => 'checkbox',
                'label' => 'NO IN/OUT UNDERTIME',
                'class' => 'csisnologunder',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'ovaliddate' => array(
                'name' => 'ovaliddate',
                'type' => 'date',
                'label' => 'Other Validity Date',
                'class' => 'csovaliddate',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'datefiled' => array(
                'name' => 'datefiled',
                'type' => 'date',
                'label' => 'Date Filed',
                'class' => 'csdatefiled',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'datereturn' => array(
                'name' => 'datereturn',
                'type' => 'date',
                'label' => 'Date Return',
                'class' => 'csdatereturn',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'contactperson' => array(
                'name' => 'contactperson',
                'type' => 'cinput',
                'label' => 'Contact Person',
                'class' => 'cscontactperson',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'companyaddress' => array(
                'name' => 'companyaddress',
                'type' => 'cinput',
                'label' => 'Company Address',
                'class' => 'cscompanyaddress',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'ppio' => array(
                'name' => 'ppio',
                'type' => 'input',
                'label' => 'PPIO',
                'class' => 'csppio sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
            ),
            'preparedby' => array(
                'name' => 'preparedby',
                'type' => 'lookup',
                'label' => 'Prepared By',
                'class' => 'cspreparedby sbccsreadonly',
                'lookupclass' => 'lookuppreparedby',
                'action' => 'lookuppreparedby',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),
            'endorseby' => array(
                'name' => 'endorseby',
                'type' => 'input',
                'label' => 'Endorsed by',
                'class' => 'csendorseby',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
            ),
            'taxesandcharge' => array(
                'name' => 'taxesandcharge',
                'type' => 'input',
                'label' => 'Taxes And Charge',
                'class' => 'cstaxesandcharge',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'termsdetails' => array(
                'name' => 'termsdetails',
                'type' => 'ctextarea',
                'label' => 'Terms Details',
                'class' => 'cstermsdetails',
                'readonly' => false,
                'style' => '',
                'required' => false,
                'error' => false,
                'maxlength' => 100
            ),
            'proformainvoice' => array(
                'name' => 'proformainvoice',
                'type' => 'input',
                'label' => 'Proforma Invoice #',
                'class' => 'csproformainvoice',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'proformadate' => array(
                'name' => 'proformadate',
                'type' => 'date',
                'label' => 'Proforma Invoice Date',
                'class' => 'csproformadate',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'generateproforma' => array(
                'name' => 'generateproforma',
                'type' => 'button',
                'label' => 'GENERATE PROFORMA INV.',
                'class' => 'csgenproform',
                'action' => 'generateproforma',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'telesales' => array(
                'name' => 'telesales',
                'type' => 'input',
                'label' => 'Telesales',
                'class' => 'cstelesales',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'lineitem' => array(
                'name' => 'lineitem',
                'type' => 'input',
                'label' => 'Line Item Total',
                'class' => 'cslineitem',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'moq' => array(
                'name' => 'moq',
                'type' => 'input',
                'label' => 'Minimum Order Quantity',
                'class' => 'csamtmoq',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'mmoq' => array(
                'name' => 'mmoq',
                'type' => 'input',
                'label' => 'Multiple Order Quantity',
                'class' => 'csamtmmoq',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'crossref' => array(
                'name' => 'crossref',
                'type' => 'input',
                'label' => 'Cross Reference Total',
                'class' => 'cscrossref',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'nooffertotal' => array(
                'name' => 'nooffertotal',
                'type' => 'input',
                'label' => 'No Offer Total',
                'class' => 'csnooffertotal',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'ostech' => array(
                'name' => 'ostech',
                'type' => 'input',
                'label' => 'OS Technical',
                'class' => 'csostech',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'datesent' => array(
                'name' => 'datesent',
                'type' => 'date',
                'label' => 'Date Sent',
                'class' => 'csdatesent',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'dateforward' => array(
                'name' => 'dateforward',
                'type' => 'date',
                'label' => 'Date Forwarded to Tele',
                'class' => 'csdateforward',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'datequote' => array(
                'name' => 'datequote',
                'type' => 'lookup',
                'label' => 'Date Quote Received',
                'class' => 'csdatequote sbccsreadonly',
                'lookupclass' => 'lookupdate2',
                'action' => 'lookupdfq',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'shipcontactname' => array(
                'name' => 'shipcontactname',
                'type' => 'lookup',
                'label' => 'Shipping Contact Person',
                'class' => 'csshipcontactname sbccsreadonly',
                'lookupclass' => 'shipping',
                'action' => 'lookupcustomercontact',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'shipcontactno' => array(
                'name' => 'shipcontactno',
                'type' => 'input',
                'label' => 'Shipping Contact #',
                'class' => 'csshipcontactno sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'billcontactname' => array(
                'name' => 'billcontactname',
                'type' => 'lookup',
                'label' => 'Billing Contact Person',
                'class' => 'csbillcontactnamee sbccsreadonly',
                'lookupclass' => 'billing',
                'action' => 'lookupcustomercontact',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'billcontactno' => array(
                'name' => 'billcontactno',
                'type' => 'input',
                'label' => 'Billing Contact #',
                'class' => 'csbillcontactno sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'sodocno' => array(
                'name' => 'sodocno',
                'type' => 'lookuplink',
                'label' => 'SO #',
                'class' => 'cssodocno sbccsreadonly',
                'lookupclass' => 'pendingsqpohsummary',
                'action' => 'pendingsqpohsummary',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'link' => ['type' => 'jumpmodule', 'access' => 'view', 'action' => 'jumpmodule', 'lookupclass' => 'jumpmodule', 'addedparams' => ['sotrno', 'sodocno']]
            ),
            'vcur' => array(
                'name' => 'vcur',
                'type' => 'lookup',
                'label' => 'Vendor Currency',
                'class' => 'cscur sbccsreadonly',
                'action' => 'lookupcurrency',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'deldate' => array(
                'name' => 'deldate',
                'type' => 'date',
                'label' => 'Delivery Date',
                'class' => 'csdeldate',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'deladdress' => array(
                'name' => 'deladdress',
                'type' => 'textarea',
                'label' => 'Delivery Address',
                'class' => 'csdeladdress',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'podate' => array(
                'name' => 'podate',
                'type' => 'date',
                'label' => 'PO Date',
                'class' => 'cspodate',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'inhouse' => array(
                'name' => 'inhouse',
                'type' => 'input',
                'label' => 'In House #',
                'class' => 'csinhouse',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'validity' => array(
                'name' => 'validity',
                'type' => 'input',
                'label' => 'Validity',
                'class' => 'csvalidity',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'vendor' => array(
                'name' => 'vendor',
                'type' => 'lookup',
                'label' => 'Vendor',
                'class' => 'csvendor sbccsreadonly',
                'lookupclass' => 'lookupvendor',
                'action' => 'lookupvendor',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'customer' => array(
                'name' => 'customer',
                'type' => 'input',
                'label' => 'Customer',
                'class' => 'cscustomer',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'nobidtotal' => array(
                'name' => 'nobidtotal',
                'type' => 'input',
                'label' => 'No Bid Total',
                'class' => 'csnooffertotal',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'activity' => array(
                'name' => 'activity',
                'type' => 'ctextarea',
                'label' => 'Activity',
                'class' => 'csactivity',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'radiocustreporttypeafti' => array(
                'name' => 'reporttype',
                'label' => 'Type',
                'type' => 'radio',
                'options' => array(
                    ["label" => "1st Notice", "value" => "n1", 'color' => 'pink'],
                    ["label" => "2nd Notice", "value" => "n2", 'color' => 'pink'],
                    ["label" => "Past due", "value" => "n3", 'color' => 'pink']
                )
            ),
            'amount' => array(
                'name' => 'amount',
                'type' => 'input',
                'label' => 'Amount',
                'class' => 'csamount',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'commamt' => array(
                'name' => 'commamt',
                'type' => 'input',
                'label' => 'Commission Amount',
                'class' => 'cscommamt',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'petty' => array(
                'name' => 'petty',
                'type' => 'input',
                'label' => 'Petty Cash',
                'class' => 'cspetty',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),

            'probability' => array(
                'name' => 'probability',
                'type' => 'lookup',
                'label' => 'Probability',
                'class' => 'csprobability',
                'lookupclass' => 'lookupprobability',
                'action' => 'lookupprobability',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'taxdef' => array(
                'name' => 'taxdef',
                'type' => 'input',
                'label' => 'VAT Rate',
                'class' => 'cstaxdef',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'agentcode' => array(
                'name' => 'agentcode',
                'type' => 'cinput',
                'label' => 'Agent Code',
                'class' => 'csagentcode',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 3
            ),
            'chinesecourse' => array(
                'name' => 'chinesecourse',
                'type' => 'lookup',
                'label' => 'Chinese Course',
                'class' => 'cscourse sbccsreadonly',
                'lookupclass' => 'lookupchinesecourse',
                'action' => 'lookupcourse',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'chinesecoursename' => array(
                'name' => 'chinesecoursename',
                'type' => 'input',
                'label' => 'Chinese Course Name',
                'class' => 'cschinesecoursename',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'chineselevelup' => array(
                'name' => 'chineselevelupcode',
                'type' => 'lookup',
                'label' => 'Chinese Level Up',
                'class' => 'cslevelup',
                'lookupclass' => 'lookupchineselevelup',
                'action' => 'lookupcourse',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'whtype' => array(
                'name' => 'whtype',
                'type' => 'lookup',
                'label' => 'Warehouse Type',
                'class' => 'cswhtype sbccsreadonly',
                'lookupclass' => 'lookupwhtype',
                'action' => 'lookupwhtype',
            ),

            'whtype2' => array(
                'name' => 'whtype2',
                'type' => 'lookup',
                'label' => 'WH Type 2',
                'class' => 'cswhtype sbccsreadonly',
                'lookupclass' => 'lookupwhtype',
                'action' => 'lookupwhtype',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'whtype3' => array(
                'name' => 'whtype3',
                'type' => 'lookup',
                'label' => 'WH Type 3',
                'class' => 'cswhtype sbccsreadonly',
                'lookupclass' => 'lookupwhtype',
                'action' => 'lookupwhtype',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),


            'whfilter1' => array(
                'name' => 'whfilter1',
                'type' => 'lookup',
                'label' => 'WH Filter 1',
                'labeldata' => 'whcode1~whname1',
                'class' => 'cswhtype sbccsreadonly',
                'lookupclass' => 'lookupwhfilter1',
                'action' => 'lookupwhfilter',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),


            'whfilter2' => array(
                'name' => 'whfilter2',
                'type' => 'lookup',
                'label' => 'WH Filter 2',
                'labeldata' => 'whcode2~whname2',
                'class' => 'cswhtype sbccsreadonly',
                'lookupclass' => 'lookupwhfilter2',
                'action' => 'lookupwhfilter',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),


            'whfilter3' => array(
                'name' => 'whfilter3',
                'type' => 'lookup',
                'label' => 'WH Filter 3',
                'labeldata' => 'whcode3~whname3',
                'class' => 'cswhtype sbccsreadonly',
                'lookupclass' => 'lookupwhfilter3',
                'action' => 'lookupwhfilter',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'whfilter4' => array(
                'name' => 'whfilter4',
                'type' => 'lookup',
                'label' => 'WH Filter 4',
                'labeldata' => 'whcode4~whname4',
                'class' => 'cswhtype sbccsreadonly',
                'lookupclass' => 'lookupwhfilter4',
                'action' => 'lookupwhfilter',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),


            'invoicedate' => array(
                'name' => 'invoicedate',
                'type' => 'date',
                'label' => 'Supplier Invoice Date',
                'class' => 'csinvoicedate',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'subject' => array(
                'name' => 'subject',
                'type' => 'lookup',
                'label' => 'Subject',
                'class' => 'cssubject sbccsreadonly',
                'lookupclass' => 'lookupsubjectreport',
                'action' => 'lookupsubject',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'cc' => array(
                'name' => 'cc',
                'type' => 'input',
                'label' => 'CC',
                'class' => 'cscc',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'message' => array(
                'name' => 'message',
                'type' => 'wysiwyg',
                'label' => 'Message',
                'class' => 'csmessage',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'lblmessage' => array(
                'name' => 'lblmessage',
                'type' => 'label',
                'label' => 'Message',
                'class' => '',
                'style' => 'font-weight:bold'
            ),

            'modeofdelivery' => array(
                'name' => 'modeofdelivery',
                'type' => 'lookup',
                'label' => 'Mode Of Delivery',
                'class' => 'csline sbccsreadonly',
                'lookupclass' => 'lookupmodeofdel',
                'action' => 'lookupmodeofdel',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'driver' => array(
                'name' => 'driver',
                'type' => 'input',
                'label' => 'Endorse by/Driver/Assistant/Rider',
                'class' => 'csaddress',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'truckno' => array(
                'name' => 'truckno',
                'type' => 'input',
                'label' => 'Truck No',
                'class' => 'csaddress',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'receiveby' => array(
                'name' => 'receiveby',
                'type' => 'input',
                'label' => 'Receive by',
                'class' => 'csaddress',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),

            'receivedate' => array(
                'name' => 'receivedate',
                'type' => 'date',
                'label' => 'Receive Date',
                'class' => 'csdateid',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'couriername' => array(
                'name' => 'couriername',
                'type' => 'lookup',
                'label' => 'Courier Name',
                'class' => 'cscouriername sbccsenablealways',
                'lookupclass' => 'lookupcouriername',
                'action' => 'lookupcouriername',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'trackingno' => array(
                'name' => 'trackingno',
                'type' => 'input',
                'label' => 'Tracking No',
                'class' => 'csaddress',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'releaseby' => array(
                'name' => 'releaseby',
                'type' => 'input',
                'label' => 'Release by',
                'class' => 'csaddress',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),

            'releasedate' => array(
                'name' => 'releasedate',
                'type' => 'date',
                'label' => 'Release Date',
                'class' => 'csdateid',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'delcharge' => array(
                'name' => 'delcharge',
                'type' => 'input',
                'label' => 'Delivery Charge',
                'class' => 'csdelcharge',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),

            'purtype' => array(
                'name' => 'purtype',
                'type' => 'lookup',
                'label' => 'Purchases Type',
                'class' => 'cspurtype sbccsreadonly',
                'lookupclass' => 'lookuppurtype',
                'action' => 'lookuppurtype',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'requestorname' => array(
                'name' => 'requestorname',
                'type' => 'lookup',
                'label' => 'Requestor',
                'class' => 'csclient sbccsreadonly',
                'lookupclass' => 'requestor',
                'action' => 'lookupclient',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),

            'yourrefname' => array(
                'name' => 'yourrefname',
                'type' => 'lookup',
                'label' => 'PO #',
                'class' => 'csyourrefname sbccsreadonly',
                'lookupclass' => 'yourrefname',
                'action' => 'lookupsjref',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),

            'erpno' => array(
                'name' => 'erpno',
                'type' => 'input',
                'label' => 'ERP #',
                'class' => 'cserpno',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),

            'invno' => array(
                'name' => 'invno',
                'type' => 'input',
                'label' => 'INV #',
                'class' => 'csinvno',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),

            'tripdate' => array(
                'name' => 'tripdate',
                'type' => 'date',
                'label' => 'Trip Date',
                'class' => 'cstripdate',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),

            'shipdate' => array(
                'name' => 'shipdate',
                'type' => 'date',
                'label' => 'Ship Date',
                'class' => 'csshipdate',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'shipreceived' => array(
                'name' => 'shipreceived',
                'type' => 'date',
                'label' => 'Ship Received',
                'class' => 'csshipreceived',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'complain' => array(
                'name' => 'complain',
                'type' => 'ctextarea',
                'label' => 'Complain',
                'class' => 'cscomplain',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'error' => false,
                'maxlength' => 200
            ),

            'recommend' => array(
                'name' => 'recommend',
                'type' => 'ctextarea',
                'label' => 'Recommend',
                'class' => 'csrecommend',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'error' => false,
                'maxlength' => 200
            ),

            'returndate_sup' => array(
                'name' => 'returndate_sup',
                'type' => 'date',
                'label' => 'Return to Supplier Date',
                'class' => 'csreturndate_sup',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),

            'returndate_cust' => array(
                'name' => 'returndate_cust',
                'type' => 'date',
                'label' => 'Return to Customer Date',
                'class' => 'csreturndate_cust',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'returndate_supby' => array(
                'name' => 'returndate_supby',
                'type' => 'input',
                'label' => 'Return By',
                'class' => 'returndate_supby',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),

            'returndate_custby' => array(
                'name' => 'returndate_custby',
                'type' => 'input',
                'label' => 'Return By',
                'class' => 'csreturndate_custby',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),

            'fileby' => array(
                'name' => 'fileby',
                'type' => 'input',
                'label' => 'File By',
                'class' => 'csfileby',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'cperson' => array(
                'name' => 'cperson',
                'type' => 'input',
                'label' => 'Contact Person',
                'class' => 'cscperson',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'shipaddress' => array(
                'name' => 'shipaddress',
                'type' => 'input',
                'label' => 'Shipping Address',
                'class' => 'csshipaddress',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'awb' => array(
                'name' => 'awb',
                'type' => 'input',
                'label' => 'Awb #',
                'class' => 'csawb',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'action' => array(
                'name' => 'action',
                'type' => 'textarea',
                'label' => 'Action Taken',
                'class' => 'csaction',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
            ),
            'dateclose' => array(
                'name' => 'dateclose',
                'type' => 'date',
                'label' => 'Date Close',
                'class' => 'csdateclose',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'istmptenant' => array(
                'name' => 'istmptenant',
                'type' => 'checkbox',
                'label' => 'Lease',
                'class' => 'csistmptenant',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isnonvat' => array(
                'name' => 'isnonvat',
                'type' => 'checkbox',
                'label' => 'Non - Vatable',
                'class' => 'csisnonvat',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'pref' => array(
                'name' => 'pref',
                'type' => 'lookup',
                'label' => 'Prefix',
                'class' => 'csusers sbccsreadonly',
                'lookupclass' => 'lookupprefix',
                'action' => 'lookupprefix',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'escalation' => array(
                'name' => 'escalation',
                'type' => 'input',
                'label' => 'Rental Escalation',
                'class' => 'csescalation',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'contract' => array(
                'name' => 'contract',
                'type' => 'input',
                'label' => 'Contract',
                'class' => 'cscontract',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'isspecialrate' => array(
                'name' => 'isspecialrate',
                'type' => 'checkbox',
                'label' => 'Special Rate',
                'class' => 'csisspecialrate',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'ewcharges' => array(
                'name' => 'ewcharges',
                'type' => 'input',
                'label' => 'Electric & Water Charges',
                'class' => 'csewcharges',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'concharges' => array(
                'name' => 'concharges',
                'type' => 'input',
                'label' => 'Construction Charges',
                'class' => 'csconcharges',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'fencecharge' => array(
                'name' => 'fencecharge',
                'type' => 'input',
                'label' => 'Est. Cost of Plywood Fencing',
                'class' => 'csfencecharge',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'powercharges' => array(
                'name' => 'powercharges',
                'type' => 'input',
                'label' => 'Est. Power Charges',
                'class' => 'cspowercharges',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'watercharges' => array(
                'name' => 'watercharges',
                'type' => 'input',
                'label' => 'Est. Water Charges',
                'class' => 'cswatercharges',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'housekeeping' => array(
                'name' => 'housekeeping',
                'type' => 'input',
                'label' => 'Housekeeping/Debris Hauling',
                'class' => 'cshousekeeping',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'secdep' => array(
                'name' => 'secdep',
                'type' => 'input',
                'label' => 'Security Deposit',
                'class' => 'cssecdep',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'secdepmos' => array(
                'name' => 'secdepmos',
                'type' => 'input',
                'label' => 'Sec. Deposit # of Months',
                'class' => 'cssecdepmos',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'docstamp' => array(
                'name' => 'docstamp',
                'type' => 'input',
                'label' => 'Documentary Stamp Tax',
                'class' => 'csdocstamp',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'consbond' => array(
                'name' => 'consbond',
                'type' => 'input',
                'label' => 'Construction bond',
                'class' => 'csconsbond',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'emeterdep' => array(
                'name' => 'emeterdep',
                'type' => 'input',
                'label' => 'Electric Meter Deposit',
                'class' => 'csemeterdep',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'servicedep' => array(
                'name' => 'servicedep',
                'type' => 'input',
                'label' => 'Service Bill Deposit',
                'class' => 'csservicedep',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'addnew' => array(
                'name' => 'addnew',
                'type' => 'button',
                'label' => 'ADD',
                'class' => 'csaddnew',
                'action' => 'addnew',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'lbltotal' => array(
                'name' => 'lbltotal',
                'type' => 'label',
                'label' => 'Total',
                'class' => '',
                'style' => 'font-weight:500'
            ),
            'lbltaxes' => array(
                'name' => 'lbltaxes',
                'type' => 'label',
                'label' => 'Taxes and Charges',
                'class' => '',
                'style' => 'font-weight:500'
            ),
            'lblgrandtotal' => array(
                'name' => 'lblgrandtotal',
                'type' => 'label',
                'label' => 'Grand Total',
                'class' => '',
                'style' => 'font-weight:500'
            ),
            'accountname' => array(
                'name' => 'accountname',
                'type' => 'input',
                'label' => 'Account Name',
                'class' => 'csaccountname',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'accountnum' => array(
                'name' => 'accountnum',
                'type' => 'input',
                'label' => 'Account Number',
                'class' => 'csaccountnum',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'isdriver' => array(
                'name' => 'isdriver',
                'type' => 'checkbox',
                'label' => 'Driver',
                'class' => 'csisdriver',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'ispassenger' => array(
                'name' => 'ispassenger',
                'type' => 'checkbox',
                'label' => 'Passenger',
                'class' => 'csispassenger',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'ismon' => array(
                'name' => 'ismon',
                'type' => 'checkbox',
                'label' => 'Monday',
                'class' => 'csismon',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'ismon_am' => array(
                'name' => 'ismon_am',
                'type' => 'checkbox',
                'label' => 'Mon - AM',
                'class' => 'csismonam',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'ismon_pm' => array(
                'name' => 'ismon_pm',
                'type' => 'checkbox',
                'label' => 'Mon - PM',
                'class' => 'csismonpm',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'istue' => array(
                'name' => 'istue',
                'type' => 'checkbox',
                'label' => 'Tuesday',
                'class' => 'csistue',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'istue_am' => array(
                'name' => 'istue_am',
                'type' => 'checkbox',
                'label' => 'Tue - AM',
                'class' => 'csismonam',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'istue_pm' => array(
                'name' => 'istue_pm',
                'type' => 'checkbox',
                'label' => 'Tue - PM',
                'class' => 'csismonpm',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'iswed' => array(
                'name' => 'iswed',
                'type' => 'checkbox',
                'label' => 'Wednesday',
                'class' => 'csiswed',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'iswed_am' => array(
                'name' => 'iswed_am',
                'type' => 'checkbox',
                'label' => 'Wed - AM',
                'class' => 'csiswedam',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'iswed_pm' => array(
                'name' => 'iswed_pm',
                'type' => 'checkbox',
                'label' => 'Wed - PM',
                'class' => 'csiswedpm',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isthu' => array(
                'name' => 'isthu',
                'type' => 'checkbox',
                'label' => 'Thursday',
                'class' => 'csisthu',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isthu_am' => array(
                'name' => 'isthu_am',
                'type' => 'checkbox',
                'label' => 'Thu - AM',
                'class' => 'csisthuam',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isthu_pm' => array(
                'name' => 'isthu_pm',
                'type' => 'checkbox',
                'label' => 'Thu - PM',
                'class' => 'csisthupm',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isfri' => array(
                'name' => 'isfri',
                'type' => 'checkbox',
                'label' => 'Friday',
                'class' => 'csisfri',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isfri_am' => array(
                'name' => 'isfri_am',
                'type' => 'checkbox',
                'label' => 'Fri - AM',
                'class' => 'csisfriam',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isfri_pm' => array(
                'name' => 'isfri_pm',
                'type' => 'checkbox',
                'label' => 'Fri - PM',
                'class' => 'csisfripm',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'issat' => array(
                'name' => 'issat',
                'type' => 'checkbox',
                'label' => 'Satruday',
                'class' => 'csissat',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'issat_am' => array(
                'name' => 'issat_am',
                'type' => 'checkbox',
                'label' => 'Sat - AM',
                'class' => 'csissatam',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'issat_pm' => array(
                'name' => 'issat_pm',
                'type' => 'checkbox',
                'label' => 'Sat - PM',
                'class' => 'csissatpm',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'issun' => array(
                'name' => 'issun',
                'type' => 'checkbox',
                'label' => 'Sunday',
                'class' => 'csissun',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'issun_am' => array(
                'name' => 'issun_am',
                'type' => 'checkbox',
                'label' => 'Sun - AM',
                'class' => 'csissunam',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'issun_pm' => array(
                'name' => 'issun_pm',
                'type' => 'checkbox',
                'label' => 'Sun - PM',
                'class' => 'csissunpm',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'vehicle' => array(
                'name' => 'vehicle',
                'type' => 'lookup',
                'label' => 'Vehicle Code',
                'class' => 'csType sbccsreadonly',
                'lookupclass' => 'vehicle',
                'action' => 'lookupclient',
                'readonly' => true,
                'style' => $this->style,
            ),
            'vehiclename' => array(
                'name' => 'vehiclename',
                'type' => 'input',
                'label' => 'Vechicle Name',
                'class' => 'csvehiclename',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'drivername' => array(
                'name' => 'drivername',
                'type' => 'input',
                'label' => 'Driver Name',
                'class' => 'csdrivername',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'schedin' => array(
                'name' => 'schedin',
                'type' => 'date',
                'label' => 'Schedule In',
                'class' => 'csschedin',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'schedout' => array(
                'name' => 'schedout',
                'type' => 'date',
                'label' => 'Schedule Out',
                'class' => 'csschedout',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'schedintime' => array(
                'name' => 'schedintime',
                'type' => 'input',
                'label' => 'Time In',
                'class' => 'csschedintime',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'schedouttime' => array(
                'name' => 'schedouttime',
                'type' => 'input',
                'label' => 'Time Out',
                'class' => 'csschedouttime',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'actualin' => array(
                'name' => 'actualin',
                'type' => 'date',
                'label' => 'Actual In',
                'class' => 'csactualin',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'actualtimein' => array(
                'name' => 'actualtimein',
                'type' => 'input',
                'label' => 'Time',
                'class' => 'csactualtimein',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'actualout' => array(
                'name' => 'actualout',
                'type' => 'date',
                'label' => 'Actual Out',
                'class' => 'csactualout',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'actualtimeout' => array(
                'name' => 'actualtimeout',
                'type' => 'input',
                'label' => 'Time',
                'class' => 'csactualtimeout',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'orgschedin' => array(
                'name' => 'orgschedin',
                'type' => 'date',
                'label' => 'Schedule In',
                'class' => 'csorgschedin',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'orgschedout' => array(
                'name' => 'orgschedout',
                'type' => 'date',
                'label' => 'Schedule Out',
                'class' => 'csorgschedout',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'amcount' => array(
                'name' => 'amcount',
                'type' => 'input',
                'label' => 'AM Count',
                'class' => 'csamcount',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'pmcount' => array(
                'name' => 'pmcount',
                'type' => 'input',
                'label' => 'PM Count',
                'class' => 'cspmcount',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'amused' => array(
                'name' => 'amused',
                'type' => 'input',
                'label' => 'AM Used',
                'class' => 'csamused',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'pmused' => array(
                'name' => 'pmused',
                'type' => 'input',
                'label' => 'PM Used',
                'class' => 'cspmused',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'interestrate' => array(
                'name' => 'interestrate',
                'type' => 'input',
                'label' => 'Interest Rate',
                'class' => 'csinterestrate',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'downpayment' => array(
                'name' => 'downpayment',
                'type' => 'input',
                'label' => 'Down Payment',
                'class' => 'csdownpayment',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'collectorname' => array(
                'name' => 'collectorname',
                'type' => 'lookup',
                'label' => 'Collector',
                'class' => 'cscollectorname sbccsreadonly',
                'lookupclass' => 'collector',
                'action' => 'lookupclient',
                'readonly' => true,
                'style' => $this->style
            ),
            'lblenddate' => array(
                'name' => 'lblenddate',
                'type' => 'label',
                'label' => 'End Date',
                'class' => '',
                'style' => 'font-weight:bold'
            ),
            'salestype' => array(
                'name' => 'salestype',
                'type' => 'lookup',
                'label' => 'Transaction Type',
                'class' => 'cssalestype sbccsreadonly',
                'lookupclass' => 'lookupsalestype',
                'action' => 'lookupsalestype',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),
            'dtstatusid' => array(
                'name' => 'dtstatusid',
                'type' => 'hidden'
            ),
            'dtstatus' => array(
                'name' => 'dtstatus',
                'type' => 'lookup',
                'label' => 'Status',
                'class' => 'dtstatus sbccsreadonly',
                'lookupclass' => 'lookupdtstatuslistrep',
                'action' => 'lookupdtstatuslist',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'dtuserlevel' => array(
                'name' => 'dtuserlevel',
                'type' => 'lookup',
                'label' => 'User Level',
                'class' => 'dtuserlevel sbccsreadonly',
                'lookupclass' => 'lookupdtuserlevel',
                'action' => 'lookupdtuserlevel',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'radiodtfilter' => array(
                'name' => 'radiodtfilter',
                'label' => 'Date Filter',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'By Current Status', 'value' => 0, 'color' => 'purple'],
                    ['label' => 'By Tagged Status', 'value' => 1, 'color' => 'green']
                )
            ),
            'userfilter' => array(
                'name' => 'userfilter',
                'label' => 'User Options',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Per User', 'value' => '0', 'color' => 'teal'],
                    ['label' => 'All', 'value' => '1', 'color' => 'teal']
                )
            ),
            'otherleadtime' => array(
                'name' => 'otherleadtime',
                'type' => 'date',
                'label' => 'Other Lead Time',
                'class' => 'csotherleadtime',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'deadline' => array(
                'name' => 'deadline',
                'type' => 'date',
                'label' => 'Deadline',
                'class' => 'csdeadline',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'pdeadline' => array(
                'name' => 'pdeadline',
                'type' => 'date',
                'label' => 'Payment Deadline',
                'class' => 'cspdeadline',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'sentdate' => array(
                'name' => 'sentdate',
                'type' => 'date',
                'label' => 'Date Sent',
                'class' => 'cssentdate',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'pickupdate' => array(
                'name' => 'pickupdate',
                'type' => 'date',
                'label' => 'Pickup Date',
                'class' => 'cspickupdate',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'sono' => array(
                'name' => 'sono',
                'type' => 'input',
                'label' => 'SO #',
                'class' => 'cssono',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),

            'orno' => array(
                'name' => 'orno',
                'type' => 'input',
                'label' => 'OR No.',
                'class' => 'csorno',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'ordate' => array(
                'name' => 'ordate',
                'type' => 'date',
                'label' => 'OR Date',
                'class' => 'csordate',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'rtno' => array(
                'name' => 'rtno',
                'type' => 'input',
                'label' => 'RT #',
                'class' => 'csrtno',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'sortline' => array(
                'name' => 'sortline',
                'type' => 'input',
                'label' => 'Line',
                'class' => 'cssortline',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'isencashment' => array(
                'name' => 'isencashment',
                'type' => 'checkbox',
                'label' => 'Encashment',
                'class' => 'csisencashment',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isonlineencashment' => array(
                'name' => 'isonlineencashment',
                'type' => 'checkbox',
                'label' => 'Online Encashment',
                'class' => 'csisonlineencashment',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'rfnno' => array(
                'name' => 'rfnno',
                'type' => 'input',
                'label' => 'RFN No.',
                'class' => 'csrfnno',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'journalamt' => array(
                'name' => 'journalamt',
                'type' => 'input',
                'label' => 'Reading Amount',
                'class' => 'cscr',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isvatzerorated' => array(
                'name' => 'isvatzerorated',
                'type' => 'checkbox',
                'label' => 'Vat Zero Rated',
                'class' => 'csisvatzerorated',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isnotarizedcert' => array(
                'name' => 'isnotarizedcert',
                'type' => 'checkbox',
                'label' => 'Notarized Cert',
                'class' => 'csisnotarizedcert',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'cod' => array(
                'name' => 'cod',
                'type' => 'input',
                'label' => '50% COD',
                'class' => 'cscod',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'outstanding' => array(
                'name' => 'outstanding',
                'type' => 'input',
                'label' => 'Outstanding Balance',
                'class' => 'csoutstanding',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'assignedpodocno' => array(
                'name' => 'assignedpodocno',
                'type' => 'lookup',
                'label' => 'Assigned PO Docno',
                'class' => 'csassignedpodocno sbccsreadonly',
                'lookupclass' => 'lookupassignedpodocno',
                'action' => 'lookupassignedpodocno',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'pidocno' => array(
                'name' => 'pidocno',
                'type' => 'lookup',
                'label' => 'Production Instruction #',
                'class' => 'cscourse sbccsreadonly',
                'lookupclass' => 'lookuppidocno',
                'action' => 'lookuppidocno',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'pedocno' => array(
                'name' => 'pedocno',
                'type' => 'lookup',
                'label' => 'Production Request',
                'class' => 'cscourse sbccsreadonly',
                'lookupclass' => 'lookuppedocno',
                'action' => 'lookuppedocno',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'pddocno' => array(
                'name' => 'pddocno',
                'type' => 'lookup',
                'label' => 'Production Order #',
                'class' => 'cspddocno sbccsreadonly',
                'action' => 'lookuppddocno',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'pendingso' => array(
                'name' => 'pendingso',
                'type' => 'lookup',
                'label' => 'Sales Order #',
                'class' => 'cssodocno sbccsreadonly',
                'action' => 'pendingsodetail',
                'lookupclass' => 'pendingsodetail',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'sjdocno' => array(
                'name' => 'sjdocno',
                'type' => 'lookup',
                'label' => 'Transaction List',
                'class' => 'cssjdocno sbccsreadonly',
                'action' => 'pendingsjsummary',
                'lookupclass' => 'sjdocno',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'ledocno' => array(
                'name' => 'ledocno',
                'type' => 'lookup',
                'label' => 'LOAN APPLICATION',
                'class' => 'cscourse sbccsreadonly',
                'lookupclass' => 'lookupledocno',
                'action' => 'lookupledocno',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'sotype' => array(
                'name' => 'sotype',
                'label' => 'Type',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Retail', 'value' => 0, 'color' => 'red'],
                    ['label' => 'Production', 'value' => 1, 'color' => 'red'],
                )
            ),
            'pdprocess' => array(
                'name' => 'pdprocess',
                'type' => 'lookup',
                'label' => 'Process',
                'class' => 'cspdprocess sbccsreadonly',
                'action' => 'lookuppdprocess',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'addedparams' => ['pdtrno']
            ),
            'leadtimesettings' => array(
                'name' => 'leadtimesettings',
                'type' => 'lookup',
                'label' => 'Lead Time Settings',
                'class' => 'csleadtimesettings sbccsreadonly',
                'lookupclass' => 'lookupleadtimesettings',
                'action' => 'lookuprandom',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'billemail' => array(
                'name' => 'billemail',
                'type' => 'input',
                'label' => 'Email',
                'class' => 'csemail',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'lblbillemail' => array(
                'name' => 'lblbillemail',
                'type' => 'label',
                'label' => 'Email',
                'class' => '',
                'style' => 'font-weight:bold'
            ),
            'shipemail' => array(
                'name' => 'shipemail',
                'type' => 'input',
                'label' => 'Email',
                'class' => 'csemail',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'lblshipemail' => array(
                'name' => 'lblshipemail',
                'type' => 'label',
                'label' => 'Email',
                'class' => '',
                'style' => 'font-weight:bold'
            ),
            'shipmobile' => array(
                'name' => 'shipmobile',
                'type' => 'input',
                'label' => 'Mobile#',
                'class' => 'csmobile',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'lblshipmobile' => array(
                'name' => 'lblshipmobile',
                'type' => 'label',
                'label' => 'Mobile#',
                'class' => '',
                'style' => 'font-weight:bold'
            ),
            'billmobile' => array(
                'name' => 'billmobile',
                'type' => 'input',
                'label' => 'Mobile#',
                'class' => 'csmobile',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'lblbillmobile' => array(
                'name' => 'lblbillmobile',
                'type' => 'label',
                'label' => 'Mobile#',
                'class' => '',
                'style' => 'font-weight:bold'
            ),
            'sidate' => array(
                'name' => 'sidate',
                'type' => 'date',
                'label' => 'SI Date',
                'class' => 'cssidate',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'lastdp' => array(
                'name' => 'lastdp',
                'type' => 'checkbox',
                'label' => 'Final DP',
                'class' => 'cslastdp',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'officialemail' => array(
                'name' => 'officialemail',
                'type' => 'cinput',
                'label' => 'Official Email',
                'class' => 'csofficialemail',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'officialwebsite' => array(
                'name' => 'officialwebsite',
                'type' => 'cinput',
                'label' => 'Official Website',
                'class' => 'csofficialwebsite',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'outdimlen' => array(
                'name' => 'outdimlen',
                'type' => 'lookup',
                'lookupclass' => 'outdimlen~Outside Dimension L',
                'action' => 'lookupqtdetails',
                'label' => 'Length',
                'class' => 'csoutdimlen',
                'readonly' => false,
                'style' => $this->style
            ),
            'outdimwd' => array(
                'name' => 'outdimwd',
                'type' => 'lookup',
                'lookupclass' => 'outdimwd~Outside Dimension Width',
                'action' => 'lookupqtdetails',
                'label' => 'Width',
                'class' => 'csoutdimwd',
                'readonly' => false,
                'style' => $this->style
            ),
            'outdimht' => array(
                'name' => 'outdimht',
                'type' => 'lookup',
                'lookupclass' => 'outdimht~Outside Dimension Height',
                'action' => 'lookupqtdetails',
                'label' => 'Height',
                'class' => 'csoutdimht',
                'readonly' => false,
                'style' => $this->style
            ),
            'indimlen' => array(
                'name' => 'indimlen',
                'type' => 'lookup',
                'lookupclass' => 'indimlen~Inside Dimension L',
                'action' => 'lookupqtdetails',
                'label' => 'Length',
                'class' => 'csindimlen',
                'readonly' => false,
                'style' => $this->style
            ),
            'indimwd' => array(
                'name' => 'indimwd',
                'type' => 'lookup',
                'lookupclass' => 'indimwd~Inside Dimension Width',
                'action' => 'lookupqtdetails',
                'label' => 'Width',
                'class' => 'csindimwd',
                'readonly' => false,
                'style' => $this->style
            ),
            'indimht' => array(
                'name' => 'indimht',
                'type' => 'lookup',
                'lookupclass' => 'indimht~Inside Dimension Height',
                'action' => 'lookupqtdetails',
                'label' => 'Height',
                'class' => 'csindimht',
                'readonly' => false,
                'style' => $this->style
            ),
            'chassiswd' => array(
                'name' => 'chassiswd',
                'type' => 'lookup',
                'lookupclass' => 'chassiswd~Chassis Width',
                'action' => 'lookupqtdetails',
                'label' => 'Chassis Width',
                'class' => 'cschassiswd',
                'readonly' => false,
                'style' => $this->style
            ),
            'underchassis' => array(
                'name' => 'underchassis',
                'type' => 'lookup',
                'lookupclass' => 'underchassis~Under Chassis Runner',
                'action' => 'lookupqtdetails',
                'label' => 'Under Chassis Runner',
                'class' => 'csunderchassis',
                'readonly' => false,
                'style' => $this->style
            ),
            'secchassisqty' => array(
                'name' => 'secchassisqty',
                'type' => 'lookup',
                'lookupclass' => 'secchassisqty~Secondary Chassis Qty',
                'action' => 'lookupqtdetails',
                'label' => 'Secondary Chassis Qty',
                'class' => 'cssecchassisqty',
                'readonly' => false,
                'style' => $this->style
            ),
            'secchassissz' => array(
                'name' => 'secchassissz',
                'type' => 'lookup',
                'lookupclass' => 'secchassissz~Secondary Chassis Size',
                'action' => 'lookupqtdetails',
                'label' => 'Size',
                'class' => 'cssecchassissz',
                'readonly' => false,
                'style' => $this->style
            ),
            'secchassistk' => array(
                'name' => 'secchassistk',
                'type' => 'lookup',
                'lookupclass' => 'secchassistk~Secondary Chassis Thickness',
                'action' => 'lookupqtdetails',
                'label' => 'Thickness',
                'class' => 'cssecchassistk',
                'readonly' => false,
                'style' => $this->style
            ),
            'secchassismat' => array(
                'name' => 'secchassismat',
                'type' => 'lookup',
                'lookupclass' => 'secchassismat~Secondary Chassis Material',
                'action' => 'lookupqtdetails',
                'label' => 'Material',
                'class' => 'cssecchassismat',
                'readonly' => false,
                'style' => $this->style
            ),
            'flrjoistqty' => array(
                'name' => 'flrjoistqty',
                'type' => 'lookup',
                'lookupclass' => 'flrjoistqty~Floor Joist Qty',
                'action' => 'lookupqtdetails',
                'label' => 'Floor Joist Size Qty',
                'class' => 'flrjoistqty',
                'readonly' => false,
                'style' => $this->style
            ),
            'flrjoistqtysz' => array(
                'name' => 'flrjoistqtysz',
                'type' => 'lookup',
                'lookupclass' => 'flrjoistqtysz~Floor Joist Size',
                'action' => 'lookupqtdetails',
                'label' => 'Size',
                'class' => 'csflrjoistqtysz',
                'readonly' => false,
                'style' => $this->style
            ),
            'flrjoistqtytk' => array(
                'name' => 'flrjoistqtytk',
                'type' => 'lookup',
                'lookupclass' => 'flrjoistqtytk~Floor Joist Thickness',
                'action' => 'lookupqtdetails',
                'label' => 'Thickness',
                'class' => 'csflrjoistqtytk',
                'readonly' => false,
                'style' => $this->style
            ),
            'flrjoistqtymat' => array(
                'name' => 'flrjoistqtymat',
                'type' => 'lookup',
                'lookupclass' => 'flrjoistqtymat~Floor Joist Material',
                'action' => 'lookupqtdetails',
                'label' => 'Material',
                'class' => 'csflrjoistqtymat',
                'readonly' => false,
                'style' => $this->style
            ),
            'flrtypework' => array(
                'name' => 'flrtypework',
                'type' => 'lookup',
                'lookupclass' => 'flrtypework~Flooring Type of Work',
                'action' => 'lookupqtdetails',
                'label' => 'Flooring Type of Work',
                'class' => 'csflrtypework',
                'readonly' => false,
                'style' => $this->style
            ),
            'flrtypeworktk' => array(
                'name' => 'flrtypeworktk',
                'type' => 'lookup',
                'lookupclass' => 'flrtypeworktk~Flooring Type of Work Thickness',
                'action' => 'lookupqtdetails',
                'label' => 'Thickness',
                'class' => 'csflrtypeworktk',
                'readonly' => false,
                'style' => $this->style
            ),
            'flrtypeworkty' => array(
                'name' => 'flrtypeworkty',
                'type' => 'lookup',
                'lookupclass' => 'flrtypeworkty~Flooring Type of Work Type',
                'action' => 'lookupqtdetails',
                'label' => 'Type',
                'class' => 'csflrtypeworkty',
                'readonly' => false,
                'style' => $this->style
            ),
            'flrtypeworkmat' => array(
                'name' => 'flrtypeworkmat',
                'type' => 'lookup',
                'lookupclass' => 'flrtypeworkmat~Flooring Type of Work Material',
                'action' => 'lookupqtdetails',
                'label' => 'Material',
                'class' => 'csflrtypeworkmat',
                'readonly' => false,
                'style' => $this->style
            ),
            'exttypework' => array(
                'name' => 'exttypework',
                'type' => 'lookup',
                'lookupclass' => 'exttypework~Exterior Type of Work',
                'action' => 'lookupqtdetails',
                'label' => 'Exterior Type of Work',
                'class' => 'csextypework',
                'readonly' => false,
                'style' => $this->style
            ),
            'exttypeworkqty' => array(
                'name' => 'exttypeworkqty',
                'type' => 'lookup',
                'lookupclass' => 'exttypeworkqty~Exterior Type of Work Qty',
                'action' => 'lookupqtdetails',
                'label' => 'Qty',
                'class' => 'csextypeworkqty',
                'readonly' => false,
                'style' => $this->style
            ),
            'exttypeworkty' => array(
                'name' => 'exttypeworkty',
                'type' => 'lookup',
                'lookupclass' => 'exttypeworkty~Exterior Type of Work Type',
                'action' => 'lookupqtdetails',
                'label' => 'Type',
                'class' => 'csextypeworkty',
                'readonly' => false,
                'style' => $this->style
            ),
            'inwalltypework' => array(
                'name' => 'inwalltypework',
                'type' => 'lookup',
                'lookupclass' => 'inwalltypework~Interior Walls Type of Work',
                'action' => 'lookupqtdetails',
                'label' => 'Interior Walls Type of Work',
                'class' => 'csinwalltypework',
                'readonly' => false,
                'style' => $this->style
            ),
            'inwalltypeworkqty' => array(
                'name' => 'inwalltypeworkqty',
                'type' => 'lookup',
                'lookupclass' => 'inwalltypeworkqty~Interior Walls Type of Work Qty',
                'action' => 'lookupqtdetails',
                'label' => 'Qty',
                'class' => 'csinwalltypeworkqty',
                'readonly' => false,
                'style' => $this->style
            ),
            'inwalltypeworktk' => array(
                'name' => 'inwalltypeworktk',
                'type' => 'lookup',
                'lookupclass' => 'inwalltypeworktk~Interior Walls Type of Work Thickness',
                'action' => 'lookupqtdetails',
                'label' => 'Thickness',
                'class' => 'csinwalltypeworktk',
                'readonly' => false,
                'style' => $this->style
            ),
            'inwalltypeworkty' => array(
                'name' => 'inwalltypeworkty',
                'type' => 'lookup',
                'lookupclass' => 'inwalltypeworkty~Interior Walls Type of Work Type',
                'action' => 'lookupqtdetails',
                'label' => 'Type',
                'class' => 'csinwalltypeworkty',
                'readonly' => false,
                'style' => $this->style
            ),
            'inceiltypework' => array(
                'name' => 'inceiltypework',
                'type' => 'lookup',
                'lookupclass' => 'inceiltypework~Interior Ceiling/Doors Type of Work',
                'action' => 'lookupqtdetails',
                'label' => 'Interior Ceiling/Doors Type of Work',
                'class' => 'csintypework',
                'readonly' => false,
                'style' => $this->style
            ),
            'inceiltypeworkqty' => array(
                'name' => 'inceiltypeworkqty',
                'type' => 'lookup',
                'lookupclass' => 'inceiltypeworkqty~Interior Ceiling/Doors Type of Work Qty',
                'action' => 'lookupqtdetails',
                'label' => 'Qty',
                'class' => 'csinceiltypeworkqty',
                'readonly' => false,
                'style' => $this->style
            ),
            'inceiltypeworktk' => array(
                'name' => 'inceiltypeworktk',
                'type' => 'lookup',
                'lookupclass' => 'inceiltypeworktk~Interior Ceiling/Doors Type of Work Thickness',
                'action' => 'lookupqtdetails',
                'label' => 'Thickness',
                'class' => 'csinceiltypeworktk',
                'readonly' => false,
                'style' => $this->style
            ),
            'inceiltypeworkty' => array(
                'name' => 'inceiltypeworkty',
                'type' => 'lookup',
                'lookupclass' => 'inceiltypeworkty~Interior Ceiling/Doors Type of Work Type',
                'action' => 'lookupqtdetails',
                'label' => 'Type',
                'class' => 'csinceiltypeworkty',
                'readonly' => false,
                'style' => $this->style
            ),
            'insultk' => array(
                'name' => 'insultk',
                'type' => 'lookup',
                'lookupclass' => 'insultk~Insulation Thickness',
                'action' => 'lookupqtdetails',
                'label' => 'Insulation Thickness',
                'class' => 'csinsultk',
                'readonly' => false,
                'style' => $this->style
            ),
            'insulty' => array(
                'name' => 'insulty',
                'type' => 'lookup',
                'lookupclass' => 'insulty~Insulation Type',
                'action' => 'lookupqtdetails',
                'label' => 'Type',
                'class' => 'csinsulty',
                'readonly' => false,
                'style' => $this->style
            ),
            'reardrstype' => array(
                'name' => 'reardrstype',
                'type' => 'lookup',
                'lookupclass' => 'reardrstype~Rear Doors Type',
                'action' => 'lookupqtdetails',
                'label' => 'Rear Doors Type',
                'class' => 'csreardrstype',
                'readonly' => false,
                'style' => $this->style
            ),
            'reardrslock' => array(
                'name' => 'reardrslock',
                'type' => 'lookup',
                'lookupclass' => 'reardrslock~Rear Doors # Locks/Doors',
                'action' => 'lookupqtdetails',
                'label' => '# Locks/Doors',
                'class' => 'csreardrslock',
                'readonly' => false,
                'style' => $this->style
            ),
            'reardrshinger' => array(
                'name' => 'reardrshinger',
                'type' => 'lookup',
                'lookupclass' => 'reardrshinger~Rear Doors # Hinges/Doors',
                'action' => 'lookupqtdetails',
                'label' => '# Hinges/Doors',
                'class' => 'csreardrshinger',
                'readonly' => false,
                'style' => $this->style
            ),
            'reardrsseals' => array(
                'name' => 'reardrsseals',
                'type' => 'lookup',
                'lookupclass' => 'reardrsseals~Rear Doors Seals',
                'action' => 'lookupqtdetails',
                'label' => 'Seals',
                'class' => 'csreardrsseals',
                'readonly' => false,
                'style' => $this->style
            ),
            'reardrsrem' => array(
                'name' => 'reardrsrem',
                'type' => 'lookup',
                'lookupclass' => 'reardrsrem~Rear Doors Notes',
                'action' => 'lookupqtdetails',
                'label' => 'Notes',
                'class' => 'csreardrsrem',
                'readonly' => false,
                'style' => $this->style
            ),
            'sidedrstype' => array(
                'name' => 'sidedrstype',
                'type' => 'lookup',
                'lookupclass' => 'sidedrstype~Side Doors Type',
                'action' => 'lookupqtdetails',
                'label' => 'Side Doors Type',
                'class' => 'cssidedrstype',
                'readonly' => false,
                'style' => $this->style
            ),
            'sidedrslock' => array(
                'name' => 'sidedrslock',
                'type' => 'lookup',
                'lookupclass' => 'sidedrslock~Side Doors # Locks/Doors',
                'action' => 'lookupqtdetails',
                'label' => '# Locks/Doors',
                'class' => 'cssidedrslock',
                'readonly' => false,
                'style' => $this->style
            ),
            'sidedrshinger' => array(
                'name' => 'sidedrshinger',
                'type' => 'lookup',
                'lookupclass' => 'sidedrshinger~Side Doors # Hinges/Doors',
                'action' => 'lookupqtdetails',
                'label' => '# Hinges/Doors',
                'class' => 'cssidedrshinger',
                'readonly' => false,
                'style' => $this->style
            ),
            'sidedrsseals' => array(
                'name' => 'sidedrsseals',
                'type' => 'lookup',
                'lookupclass' => 'sidedrsseals~Side Doors Seals',
                'action' => 'lookupqtdetails',
                'label' => 'Seals',
                'class' => 'cssidedrsseals',
                'readonly' => false,
                'style' => $this->style
            ),
            'sidedrsrem' => array(
                'name' => 'sidedrsrem',
                'type' => 'lookup',
                'lookupclass' => 'sidedrsrem~Side Doors Notes',
                'action' => 'lookupqtdetails',
                'label' => 'Notes',
                'class' => 'cssidedrsrem',
                'readonly' => false,
                'style' => $this->style
            ),
            'normlights' => array(
                'name' => 'normlights',
                'type' => 'lookup',
                'lookupclass' => 'normlights~No of Room Lights',
                'action' => 'lookupqtdetails',
                'label' => 'No of Room Lights',
                'class' => 'csnormlights',
                'readonly' => false,
                'style' => $this->style
            ),
            'lightsrepair' => array(
                'name' => 'lightsrepair',
                'type' => 'lookup',
                'lookupclass' => 'lightsrepair~Lights Repair',
                'action' => 'lookupqtdetails',
                'label' => 'Repair',
                'class' => 'cslightsrepair',
                'readonly' => false,
                'style' => $this->style
            ),
            'upclrlights' => array(
                'name' => 'upclrlights',
                'type' => 'lookup',
                'lookupclass' => 'upclrlights~Upper Clearance Lights',
                'action' => 'lookupqtdetails',
                'label' => 'Upper Clearance Lights',
                'class' => 'csupclrlights',
                'readonly' => false,
                'style' => $this->style
            ),
            'lowclrlights' => array(
                'name' => 'lowclrlights',
                'type' => 'lookup',
                'lookupclass' => 'lowclrlights~Lower Clearance Lights',
                'action' => 'lookupqtdetails',
                'label' => 'Lower Clearance Lights',
                'class' => 'cslowclrlights',
                'readonly' => false,
                'style' => $this->style
            ),
            'clrlightsrepair' => array(
                'name' => 'clrlightsrepair',
                'type' => 'lookup',
                'lookupclass' => 'clrlightsrepair~Clearance Lights Repair',
                'action' => 'lookupqtdetails',
                'label' => 'Repair',
                'class' => 'csclrlightsrepair',
                'readonly' => false,
                'style' => $this->style
            ),
            'paintcover' => array(
                'name' => 'paintcover',
                'type' => 'lookup',
                'lookupclass' => 'paintcover~Painting Coverage',
                'action' => 'lookupqtdetails',
                'label' => 'Painting Coverage',
                'class' => 'cspaintcover',
                'readonly' => false,
                'style' => $this->style
            ),
            'bodycolor' => array(
                'name' => 'bodycolor',
                'type' => 'lookup',
                'lookupclass' => 'bodycolor~Body Color',
                'action' => 'lookupqtdetails',
                'label' => 'Body Color',
                'class' => 'csbodycolor',
                'readonly' => false,
                'style' => $this->style
            ),
            'flrcolor' => array(
                'name' => 'flrcolor',
                'type' => 'lookup',
                'lookupclass' => 'flrcolor~Floor Color',
                'action' => 'lookupqtdetails',
                'label' => 'Floor Color',
                'class' => 'csflrcolor',
                'readonly' => false,
                'style' => $this->style
            ),
            'unchassiscolor' => array(
                'name' => 'unchassiscolor',
                'type' => 'lookup',
                'lookupclass' => 'unchassiscolor~Under Chassis',
                'action' => 'lookupqtdetails',
                'label' => 'Under Chassis',
                'class' => 'csunchassiscolor',
                'readonly' => false,
                'style' => $this->style
            ),
            'paintroof' => array(
                'name' => 'paintroof',
                'type' => 'lookup',
                'lookupclass' => 'paintroof~Painting Roof',
                'action' => 'lookupqtdetails',
                'label' => 'Painting Roof',
                'class' => 'cspaintroof',
                'readonly' => false,
                'style' => $this->style
            ),
            'exterior' => array(
                'name' => 'exterior',
                'type' => 'lookup',
                'lookupclass' => 'exterior~Painting Roof Exterior',
                'action' => 'lookupqtdetails',
                'label' => 'Exterior',
                'class' => 'csexterior',
                'readonly' => false,
                'style' => $this->style
            ),
            'interior' => array(
                'name' => 'interior',
                'type' => 'lookup',
                'lookupclass' => 'interior~Painting Roof Interior',
                'action' => 'lookupqtdetails',
                'label' => 'Interior',
                'class' => 'csinterior',
                'readonly' => false,
                'style' => $this->style
            ),
            'sideguards' => array(
                'name' => 'sideguards',
                'type' => 'lookup',
                'lookupclass' => 'sideguards~Sideguards',
                'action' => 'lookupqtdetails',
                'label' => 'Sideguards',
                'class' => 'cssideguards',
                'readonly' => false,
                'style' => $this->style
            ),
            'reseal' => array(
                'name' => 'reseal',
                'type' => 'lookup',
                'lookupclass' => 'reseal~Reseal',
                'action' => 'lookupqtdetails',
                'label' => 'Reseal',
                'class' => 'csreseal',
                'readonly' => false,
                'style' => $this->style
            ),
            'copyquote' => array(
                'name' => 'backlisting',
                'type' => 'actionbtn',
                'label' => 'COPY QUOTE',
                'class' => 'btncopyquote',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'refresh',
                'access' => 'save',
                'action' => 'copyquote',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false
            ),
            'nonsaleable' => array(
                'name' => 'nonsaleable',
                'type' => 'checkbox',
                'label' => 'Non Saleable',
                'class' => 'csnonsaleable',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'noncomm' => array(
                'name' => 'noncomm',
                'type' => 'checkbox',
                'label' => 'No Commission',
                'class' => 'csnoncomm',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'nocomm' => array(
                'name' => 'nocomm',
                'type' => 'checkbox',
                'label' => 'No Commission',
                'class' => 'csnoncomm',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'mino' => array(
                'name' => 'mino',
                'type' => 'input',
                'label' => 'Material Issuance #',
                'class' => 'csmino',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'readonly' => true
            ),
            'mrno' => array(
                'name' => 'mrno',
                'type' => 'input',
                'label' => 'Material Request #',
                'class' => 'csmrno',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'readonly' => true
            ),
            'outsidedimension' => array(
                'name' => 'outsidedimension',
                'type' => 'label',
                'label' => 'Outside Dimension',
                'class' => 'csoutsidedimension',
                'readonly' => false,
                'style' => 'font-size:20px;font-weight:bold',
                'required' => false
            ),
            'insidedimension' => array(
                'name' => 'insidedimension',
                'type' => 'label',
                'label' => 'Inside Dimension',
                'class' => 'csinsidedimension',
                'readonly' => false,
                'style' => 'font-size:20px;font-weight:bold',
                'required' => false
            ),
            'chassis' => array(
                'name' => 'chassis',
                'type' => 'label',
                'label' => 'Chassis',
                'class' => 'cschassis',
                'readonly' => false,
                'style' => 'font-size:20px;font-weight:bold',
                'required' => false
            ),
            'floorjoist' => array(
                'name' => 'floorjoist',
                'type' => 'label',
                'label' => 'Floor',
                'class' => 'csfloorjoist',
                'readonly' => false,
                'style' => 'font-size:20px;font-weight:bold',
                'required' => false
            ),
            'interiorwalls' => array(
                'name' => 'interiorwalls',
                'type' => 'label',
                'label' => 'Interior walls',
                'class' => 'csinteriorwalls',
                'readonly' => false,
                'style' => 'font-size:20px;font-weight:bold',
                'required' => false
            ),
            'interiorceiling' => array(
                'name' => 'interiorceiling',
                'type' => 'label',
                'label' => 'Interior Ceiling/Doors',
                'class' => 'csinteriorceiling',
                'readonly' => false,
                'style' => 'font-size:20px;font-weight:bold',
                'required' => false
            ),
            'reardoors' => array(
                'name' => 'reardoors',
                'type' => 'label',
                'label' => 'Rear doors',
                'class' => 'csreardoors',
                'readonly' => false,
                'style' => 'font-size:20px;font-weight:bold',
                'required' => false
            ),
            'sidedoors' => array(
                'name' => 'sidedoors',
                'type' => 'label',
                'label' => 'Side doors',
                'class' => 'cssidedoors',
                'readonly' => false,
                'style' => 'font-size:20px;font-weight:bold',
                'required' => false
            ),
            'lights' => array(
                'name' => 'lights',
                'type' => 'label',
                'label' => 'Lights',
                'class' => 'cslights',
                'readonly' => false,
                'style' => 'font-size:20px;font-weight:bold',
                'required' => false
            ),
            'paints' => array(
                'name' => 'paints',
                'type' => 'label',
                'label' => 'Paints',
                'class' => 'cspaints',
                'readonly' => false,
                'style' => 'font-size:20px;font-weight:bold',
                'required' => false
            ),
            'lblpaid' => array(
                'name' => 'lblpaid',
                'type' => 'label',
                'label' => 'PAID!',
                'class' => '',
                'style' => ' font-size: 30px; font-weight:bold; color:darkblue; -ms-transform: rotate(45deg); transform: rotate(-45deg); '
            ),
            'release' => array(
                'name' => 'release',
                'type' => 'button',
                'label' => 'RELEASE ALL',
                'class' => 'csrefresh',
                'action' => 'release',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'invnotrequired' => array(
                'name' => 'invnotrequired',
                'type' => 'checkbox',
                'label' => 'Invoice Not Required',
                'class' => 'csinvnotrequired',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'paymentname' => array(
                'name' => 'paymentname',
                'type' => 'lookup',
                'label' => 'Payment',
                'class' => 'cspaymentname sbccsreadonly',
                'lookupclass' => 'payment',
                'action' => 'lookuppayments',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'repsortby' => array(
                'name' => 'repsortby',
                'type' => 'lookup',
                'label' => 'Sort by',
                'class' => 'csrepsortby sbccsreadonly',
                'lookupclass' => 'repsortby',
                'action' => 'lookuprepsortby',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'apvfrom' => array(
                'name' => 'apvfrom',
                'type' => 'input',
                'label' => 'From APV No',
                'class' => 'csapvfrom',
                'readonly' => false,
                'required' => false,
                'style' => $this->style
            ),
            'apvto' => array(
                'name' => 'apvto',
                'type' => 'input',
                'label' => 'To APV No',
                'class' => 'csapvto',
                'readonly' => false,
                'required' => false,
                'style' => $this->style
            ),
            'radiogatherby' => array(
                'name' => 'gatherby',
                'label' => 'Gather by',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'APV Date', 'value' => '0', 'color' => 'purple'],
                    ['label' => 'APV No', 'value' => '1', 'color' => 'green']
                )
            ),
            'repearnded' => array(
                'name' => 'earnded',
                'label' => 'Earning/Deduction',
                'type' => 'lookup',
                'lookupclass' => 'lookupearnded',
                'action' => 'lookupearnded',
                'class' => 'csearnded',
                'readonly' => true,
                // 'required' => false,
                'style' => $this->style
            ),
            'reqtype' => array(
                'name' => 'reqtype',
                'type' => 'lookup',
                'label' => 'Type',
                'class' => 'csreqtype sbccsreadonly',
                'lookupclass' => 'reqtype',
                'action' => 'lookupreqtype',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'leadtime' => array(
                'name' => 'leadtime',
                'type' => 'input',
                'label' => 'Lead Time',
                'class' => 'csleadtime',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'radioencash' => array(
                'name' => 'radioencash',
                'label' => 'Type',
                'type' => 'radio',
                'readonly' => true,
                'class' => 'csradioencash sbccsreadonly',
                'options' => array(
                    ['label' => '', 'value' => 0, 'color' => 'pink'],
                    ['label' => 'Encashment', 'value' => 1, 'color' => 'pink'],
                    ['label' => 'Online Encashment', 'value' => 2, 'color' => 'pink']
                )
            ),
            'mop1' => array(
                'name' => 'mop1',
                'type' => 'input',
                'label' => 'MOP1',
                'class' => 'csmop1',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'mop2' => array(
                'name' => 'mop2',
                'type' => 'input',
                'label' => 'MOP2',
                'class' => 'csmop2',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'iscis' => array(
                'name' => 'iscis',
                'type' => 'checkbox',
                'label' => 'CIS',
                'class' => 'csiscis',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'operator' => array(
                'name' => 'operator',
                'label' => '',
                'type' => 'qselect',
                'readonly' => false,
                'options' => array(
                    ['label' => 'Equal to', 'value' => '='],
                    ['label' => 'Like', 'value' => 'like']
                )
            ),
            'addr2' => array(
                'name' => 'addr2',
                'type' => 'cinput',
                'label' => 'Address 2',
                'class' => 'csaddr2',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 200
            ),
            'radioarrangeby' => array(
                'name' => 'arrangeby',
                'label' => 'Arrange by',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Item Desc', 'value' => 0, 'color' => 'red'],
                    ['label' => 'Expiry', 'value' => 1, 'color' => 'green']
                )
            ),

            'radioarrangeby2' => array(
                'name' => 'arrangeby2',
                'label' => 'Arrange by',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Date', 'value' => 0, 'color' => 'red'],
                    ['label' => 'Emp name', 'value' => 1, 'color' => 'green']
                )
            ),

            'dlsales' => array(
                'name' => 'dlsales',
                'type' => 'button',
                'label' => 'DOWNLOAD SALES',
                'class' => 'csdlsales',
                'action' => 'dlsales',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'dlsalesret' => array(
                'name' => 'dlsalesret',
                'type' => 'button',
                'label' => 'DOWNLOAD SALES RETURNS',
                'class' => 'csdlsalesret',
                'action' => 'dlsalesret',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'dlpurchase' => array(
                'name' => 'dlpurchase',
                'type' => 'button',
                'label' => 'DOWNLOAD PURCHASES',
                'class' => 'csdlpurchase',
                'action' => 'dlpurchase',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'dlpurchret' => array(
                'name' => 'dlpurchret',
                'type' => 'button',
                'label' => 'DOWNLOAD PURCHASE RETURNS',
                'class' => 'csdlpurchret',
                'action' => 'dlpurchret',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'newpo' => array(
                'name' => 'newpo',
                'type' => 'input',
                'label' => 'New PO #',
                'class' => 'csnewpo',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'dcostcode' => array(
                'name' => 'dcostcode',
                'type' => 'lookup',
                'label' => 'Cost Code',
                'labeldata' => 'costcode~costcodename',
                'class' => 'csdcostcode sbccsreadonly',
                'lookupclass' => 'costcode',
                'action' => 'lookupcostcode',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'cancel' => array(
                'name' => 'cancel',
                'type' => 'actionbtn',
                'label' => 'CANCEL',
                'class' => 'btncancel',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'fa fa-times',
                'access' => 'cancel',
                'action' => 'cancel',
                'readonly' => true,
                'style' => 'width:50%',
                'required' => false,
                'confirm' => true,
                'confirmlabel' => 'Are you sure want to cancel ?'
            ),


            //for financing
            'finterestrate' => array(
                'name' => 'finterestrate',
                'type' => 'input',
                'label' => 'Interest Rate',
                'class' => 'csmonth',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 3
            ),
            'termsmonth' => array(
                'name' => 'termsmonth',
                'type' => 'input',
                'label' => 'Terms Month DP',
                'class' => 'csmonth',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 3
            ),
            'termspercentdp' => array(
                'name' => 'termspercentdp',
                'type' => 'input',
                'label' => 'Terms Percent DP',
                'class' => 'csmonth',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 3
            ),

            'termsyear' => array(
                'name' => 'termsyear',
                'type' => 'input',
                'label' => 'Terms Year',
                'class' => 'csmonth',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 4
            ),
            'termspercent' => array(
                'name' => 'termspercent',
                'type' => 'input',
                'label' => 'Terms Percent',
                'class' => 'csmonth',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 3
            ),
            'reservationdate' => array(
                'name' => 'reservationdate',
                'type' => 'date',
                'label' => 'Reservation Date',
                'class' => 'csdateid',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'dueday' => array(
                'name' => 'dueday',
                'type' => 'input',
                'label' => 'Due Day',
                'class' => 'csmonth',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 3
            ),
            'reservationfee' => array(
                'name' => 'reservationfee',
                'type' => 'input',
                'label' => 'Reservation Fee',
                'class' => 'csmonth',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 15
            ),
            'farea' => array(
                'name' => 'farea',
                'type' => 'input',
                'label' => 'Area',
                'class' => 'csmonth',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 15
            ),
            'fpricesqm' => array(
                'name' => 'fpricesqm',
                'type' => 'input',
                'label' => 'Price per SQM.',
                'class' => 'csmonth',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 15
            ),
            'pricesqm' => array(
                'name' => 'pricesqm',
                'type' => 'input',
                'label' => 'Price per SQM.',
                'class' => 'csmonth',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 15
            ),
            'ftcplot' => array(
                'name' => 'ftcplot',
                'type' => 'input',
                'label' => 'TCP OF LOT',
                'class' => 'csmonth',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 15
            ),
            'ftcphouse' => array(
                'name' => 'ftcphouse',
                'type' => 'input',
                'label' => 'TCP OF HOUSE',
                'class' => 'csmonth',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 15
            ),
            'fsellingpricegross' => array(
                'name' => 'fsellingpricegross',
                'type' => 'input',
                'label' => 'TOTAL SELLING PRICE(GROSS)',
                'class' => 'csmonth',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'fdiscount' => array(
                'name' => 'fdiscount',
                'type' => 'input',
                'label' => 'Discount',
                'class' => 'csmonth',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'fsellingpricenet' => array(
                'name' => 'fsellingpricenet',
                'type' => 'input',
                'label' => 'TOTAL SELLING PRICE(NET)',
                'class' => 'csmonth',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'fcontractprice' => array(
                'name' => 'fcontractprice',
                'type' => 'input',
                'label' => 'TOTAL CONTRACT PRICE',
                'class' => 'csmonth',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'fmiscfee' => array(
                'name' => 'fmiscfee',
                'type' => 'input',
                'label' => 'Misc. Fee',
                'class' => 'csmonth',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'fmonthlydp' => array(
                'name' => 'fmonthlydp',
                'type' => 'input',
                'label' => 'MONTHLY DP',
                'class' => 'csmonth',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'fmonthlyamortization' => array(
                'name' => 'fmonthlyamortization',
                'type' => 'input',
                'label' => 'MONTHLY AMORTIZATION',
                'class' => 'csmonth',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'ffi' => array(
                'name' => 'ffi',
                'type' => 'input',
                'label' => 'FI',
                'class' => 'csmonth',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'fmri' => array(
                'name' => 'fmri',
                'type' => 'input',
                'label' => 'MRI',
                'class' => 'csmonth',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'fma1' => array(
                'name' => 'fma1',
                'type' => 'input',
                'label' => 'MA without added FI & MRI',
                'class' => 'csmonth',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'fma2' => array(
                'name' => 'fma2',
                'type' => 'input',
                'label' => 'FACTOR RATE without added FI & MRI',
                'class' => 'csmonth',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'fma3' => array(
                'name' => 'fma3',
                'type' => 'input',
                'label' => 'MONTHLY AMORTIZATION with FI & MRI',
                'class' => 'csmonth',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),

            'phase' => array(
                'name' => 'phase',
                'type' => 'lookup',
                'label' => 'Phase',
                'class' => 'csphase sbccsreadonly',
                'lookupclass' => 'lookupphase',
                'action' => 'lookupphase',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'housemodel' => array(
                'name' => 'housemodel',
                'type' => 'lookup',
                'label' => 'House Model',
                'class' => 'cshousemodel sbccsreadonly',
                'lookupclass' => 'lookuphousemodel',
                'action' => 'lookuphousemodel',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'amenityname' => array(
                'name' => 'amenityname',
                'type' => 'lookup',
                'label' => 'Amenity',
                'class' => 'csamenityname sbccsreadonly',
                'lookupclass' => 'lookupamenity_head',
                'action' => 'lookupamenity_head',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'subamenityname' => array(
                'name' => 'subamenityname',
                'type' => 'lookup',
                'label' => 'Sub-Amenity',
                'class' => 'cssubamenityname sbccsreadonly',
                'lookupclass' => 'lookupsubamenity_head',
                'action' => 'lookupsubamenity_head',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'housemodel2' => array(
                'name' => 'housemodel2',
                'type' => 'lookup',
                'label' => 'House Model',
                'class' => 'cshousemodel2 sbccsreadonly',
                'lookupclass' => 'lookuphousemodel2',
                'action' => 'lookuphousemodel2',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'blklot' => array(
                'name' => 'blklot',
                'type' => 'lookup',
                'label' => 'BLK',
                'class' => 'csblklot sbccsreadonly',
                'lookupclass' => 'lookupblklot',
                'action' => 'lookupblklot',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'lot' => array(
                'name' => 'lot',
                'type' => 'input',
                'label' => 'Lot',
                'class' => 'cslot',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'cidocno' => array(
                'name' => 'cidocno',
                'type' => 'cinput',
                'label' => 'Construction Instruction',
                'class' => 'cscitrno',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'frefresh' => array(
                'name' => 'frefresh',
                'type' => 'actionbtn',
                'label' => 'Compute',
                'class' => 'btnfrefresh',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'refresh',
                'access' => 'save',
                'action' => 'fcalc',
                'readonly' => true,
                'style' => 'width:50%',
                'required' => false,

            ),
            'loanamt' => array(
                'name' => 'loanamt',
                'type' => 'input',
                'label' => 'Loanable Amount',
                'class' => 'csloanamt sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'cashadv' => array(
                'name' => 'cashadv',
                'type' => 'input',
                'label' => 'Cash Advance',
                'class' => 'cscashadv',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
            ),
            'saldedpurchase' => array(
                'name' => 'saldedpurchase',
                'type' => 'input',
                'label' => 'Salary Deduction Purchase',
                'class' => 'cssaldedpurchase',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
            ),
            'uniforms' => array(
                'name' => 'uniforms',
                'type' => 'input',
                'label' => 'Uniforms',
                'class' => 'csuniforms',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
            ),
            'duelosses' => array(
                'name' => 'duelosses',
                'type' => 'input',
                'label' => 'Charges due to Losses',
                'class' => 'csduelosses',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
            ),

            'loanlimit' => array(
                'name' => 'loanlimit',
                'type' => 'input',
                'label' => 'Loan Limit(% of Salary)',
                'class' => 'csloanlimit sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'checkdate' => array(
                'name' => 'checkdate',
                'type' => 'date',
                'label' => 'Check Date',
                'class' => 'csdateid',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'refdate' => array(
                'name' => 'refdate',
                'type' => 'date',
                'label' => 'Reference Date',
                'class' => 'csdateid',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'checkno' => array(
                'name' => 'checkno',
                'type' => 'input',
                'label' => 'Check #',
                'class' => 'cscheckno',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'areacode' => array(
                'name' => 'areacode',
                'type' => 'input',
                'label' => 'Area Code',
                'class' => 'csareacode',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'hauler' => array(
                'name' => 'hauler',
                'type' => 'input',
                'label' => 'Hauler',
                'class' => 'cshauler',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'batchno' => array(
                'name' => 'batchno',
                'type' => 'input',
                'label' => 'Batch No.',
                'class' => 'csbatchno',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'cwano' => array(
                'name' => 'cwano',
                'type' => 'input',
                'label' => 'CWA No.',
                'class' => 'cscwano',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'cwatime' => array(
                'name' => 'cwatime',
                'type' => 'input',
                'label' => 'CWA Time.',
                'class' => 'cscwatime',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'weightin' => array(
                'name' => 'weightin',
                'type' => 'input',
                'label' => 'Weight In.',
                'class' => 'csweightin',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'weightintime' => array(
                'name' => 'weightintime',
                'type' => 'input',
                'label' => 'Weight In Time',
                'class' => 'csweightintime',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'weightout' => array(
                'name' => 'weightout',
                'type' => 'input',
                'label' => 'Weight Out.',
                'class' => 'csweightout',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'weightouttime' => array(
                'name' => 'weightouttime',
                'type' => 'input',
                'label' => 'Weight Out Time',
                'class' => 'csweightouttime',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'kilo' => array(
                'name' => 'kilo',
                'type' => 'input',
                'label' => 'Kilo',
                'class' => 'cskilo',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'assignedlane' => array(
                'name' => 'assignedlane',
                'type' => 'lookup',
                'label' => 'Assigned Lane No.',
                'class' => 'csassignedlane',
                'lookupclass' => 'assignedlane',
                'action' => 'lookupassignedlane',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'repairtype' => array(
                'name' => 'repairtype',
                'type' => 'lookup',
                'label' => 'Repair Type',
                'class' => 'csrepairtype',
                'lookupclass' => 'lookuprepairtype',
                'action' => 'lookuprepairtype',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'requesttype' => array(
                'name' => 'requesttype',
                'type' => 'lookup',
                'label' => 'Request Type',
                'class' => 'csrequesttype',
                'lookupclass' => 'lookuprequesttype',
                'action' => 'lookuprequesttype',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'isrepair' => array(
                'name' => 'isrepair',
                'type' => 'checkbox',
                'label' => 'For Repair',
                'class' => 'csisrepair',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),

            'isconsumable' => array(
                'name' => 'isconsumable',
                'type' => 'checkbox',
                'label' => 'Consumables',
                'class' => 'csisconsumable',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),

            'haulerrate' => array(
                'name' => 'haulerrate',
                'type' => 'input',
                'label' => 'Hauler Rate',
                'class' => 'cshaulerrate',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'licenseno' => array(
                'name' => 'licenseno',
                'type' => 'input',
                'label' => 'License No',
                'class' => 'cslicenseno',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'licensetype' => array(
                'name' => 'licensetype',
                'type' => 'input',
                'label' => "Type of Driver's License",
                'class' => 'cslicensetype',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'packdate' => array(
                'name' => 'packdate',
                'type' => 'input',
                'label' => 'Pack Date',
                'class' => 'cspackdate sbccsenablealways',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'brgy' => array(
                'name' => 'brgy',
                'type' => 'lookup',
                'label' => 'Brgy',
                'class' => 'csbrgy',
                'lookupclass' => 'brgy',
                'action' => 'lookupbrgy',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'sdate1' => array(
                'name' => 'sdate1',
                'type' => 'date',
                'label' => 'Start Date',
                'class' => 'cssdate1',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'sdate2' => array(
                'name' => 'sdate2',
                'type' => 'date',
                'label' => 'End Date',
                'class' => 'cssdate2',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'strdate1' => array(
                'name' => 'strdate1',
                'type' => 'input',
                'label' => 'Start Date',
                'class' => 'csstrdate1',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'strdate2' => array(
                'name' => 'strdate2',
                'type' => 'input',
                'label' => 'End Date',
                'class' => 'csstrdate2',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'notedby1' => array(
                'name' => 'notedby1',
                'type' => 'input',
                'label' => 'Noted By 1',
                'class' => 'csnotedby1',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'notedby2' => array(
                'name' => 'notedby2',
                'type' => 'input',
                'label' => 'Noted By 2',
                'class' => 'csnotedby2',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'notedby3' => array(
                'name' => 'notedby3',
                'type' => 'input',
                'label' => 'Noted By 3',
                'class' => 'csnotedby3',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'notedby4' => array(
                'name' => 'notedby4',
                'type' => 'input',
                'label' => 'Noted By 4',
                'class' => 'csnotedby4',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'position1' => array(
                'name' => 'position1',
                'type' => 'input',
                'label' => 'Position 1',
                'class' => 'csposition1',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'position2' => array(
                'name' => 'position2',
                'type' => 'input',
                'label' => 'Position 2',
                'class' => 'csposition2',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'position3' => array(
                'name' => 'position3',
                'type' => 'input',
                'label' => 'Position 3',
                'class' => 'csposition3',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'position4' => array(
                'name' => 'position4',
                'type' => 'input',
                'label' => 'Position 4',
                'class' => 'csposition4',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'isplanholder' => array(
                'name' => 'isplanholder',
                'type' => 'checkbox',
                'label' => 'Same with Plan Holder',
                'class' => 'csisplanholder',
                'readonly' => true,
                'style' => 'margin-top:-10px;',
                'required' => false
            ),
            'appref' => array(
                'name' => 'appref',
                'type' => 'input',
                'label' => 'Manual Application Ref #',
                'class' => 'csappref',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'isdp' => array(
                'name' => 'isdp',
                'type' => 'checkbox',
                'label' => 'Has Downpayment',
                'class' => 'csisdp',
                'readonly' => true,
                'style' => 'margin-top:-10px;',
                'required' => false
            ),
            'dp' => array(
                'name' => 'dp',
                'type' => 'input',
                'label' => 'Downpayment',
                'class' => 'csdp',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'ispf' => array(
                'name' => 'ispf',
                'type' => 'checkbox',
                'label' => 'Has Processing Fee',
                'class' => 'csispf',
                'readonly' => true,
                'style' => 'margin-top:-10px;',
                'required' => false
            ),
            'pf' => array(
                'name' => 'pf',
                'type' => 'input',
                'label' => 'Processing Fee',
                'class' => 'cspf',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'isnf' => array(
                'name' => 'isnf',
                'type' => 'checkbox',
                'label' => 'Has Notarial Fee',
                'class' => 'csisnf',
                'readonly' => true,
                'style' => 'margin-top:-10px;',
                'required' => false
            ),
            'nf' => array(
                'name' => 'nf',
                'type' => 'input',
                'label' => 'Notarial Fee',
                'class' => 'csnf',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'plantype' => array(
                'name' => 'plantype',
                'type' => 'lookup',
                'label' => 'Plan Type',
                'class' => 'csplantype sbccsreadonly',
                'lookupclass' => 'plantype',
                'action' => 'lookupplantype',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'powercat' => array(
                'name' => 'powercat',
                'type' => 'lookup',
                'label' => 'Power Consumption Category',
                'class' => 'cspowercat sbccsreadonly',
                'lookupclass' => 'powercat',
                'action' => 'lookuppowercat',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'addressno' => array(
                'name' => 'addressno',
                'type' => 'input',
                'label' => 'Address No',
                'class' => 'csaddressno',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'attorneyinfact' => array(
                'name' => 'attorneyinfact',
                'type' => 'input',
                'label' => 'Attorney-In-Fact',
                'class' => 'csattorneyinfact',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'attorneyaddress' => array(
                'name' => 'attorneyaddress',
                'type' => 'input',
                'label' => 'Attorney Address',
                'class' => 'csattorneyaddress',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'street' => array(
                'name' => 'street',
                'type' => 'input',
                'label' => 'Street',
                'class' => 'csstreet',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'subdistown' => array(
                'name' => 'subdistown',
                'type' => 'input',
                'label' => 'Subdivision/District/Town',
                'class' => 'cssubdistown',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'subdivision' => array(
                'name' => 'subdivision',
                'type' => 'cinput',
                'label' => 'Subdivision',
                'class' => 'cssubdivision',
                'readonly' => true,
                'style' => $this->style,
                'maxlength' => 150,
                'required' => false
            ),
            'otherterms' => array(
                'name' => 'otherterms',
                'type' => 'textarea',
                'label' => 'Other Terms',
                'class' => 'cssubdistown',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'contactno' => array(
                'name' => 'contactno',
                'type' => 'input',
                'label' => 'Primary Contact #',
                'class' => 'cscontactno',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'contactno2' => array(
                'name' => 'contactno2',
                'type' => 'input',
                'label' => 'Alternative Contact #',
                'class' => 'cscontactno2',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'fname2' => array(
                'name' => 'fname2',
                'type' => 'input',
                'label' => 'First Name',
                'class' => 'csfname2',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'mname2' => array(
                'name' => 'mname2',
                'type' => 'input',
                'label' => 'Middle Name',
                'class' => 'csmname2',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'lname2' => array(
                'name' => 'lname2',
                'type' => 'input',
                'label' => 'Last Name',
                'class' => 'cslname2',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'ext2' => array(
                'name' => 'ext2',
                'type' => 'input',
                'label' => 'Ext',
                'class' => 'csext2',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),

            'raddressno' => array(
                'name' => 'raddressno',
                'type' => 'cinput',
                'label' => 'Address No',
                'class' => 'csaddressno',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'rstreet' => array(
                'name' => 'rstreet',
                'type' => 'cinput',
                'label' => 'Street',
                'class' => 'csstreet',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 150
            ),
            'rsubdistown' => array(
                'name' => 'rsubdistown',
                'type' => 'cinput',
                'label' => 'Subdivision/District/Town',
                'class' => 'csrsubdistown',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 150
            ),
            'rbrgy' => array(
                'name' => 'rbrgy',
                'type' => 'lookup',
                'label' => 'Barangay',
                'class' => 'csbrgy',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'action' => 'lookupprovcity'
            ),
            'rcity' => array(
                'name' => 'rcity',
                'type' => 'lookup',
                'label' => 'City',
                'class' => 'cscity',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'action' => 'lookupprovcity'
            ),
            'rprovince' => array(
                'name' => 'rprovince',
                'type' => 'lookup',
                'label' => 'Province',
                'class' => 'rprovince',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'action' => 'lookupprovcity'
            ),
            'rcountry' => array(
                'name' => 'rcountry',
                'type' => 'input',
                'label' => 'Country',
                'class' => 'cscountry',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'rzipcode' => array(
                'name' => 'rzipcode',
                'type' => 'input',
                'label' => 'Zipcode',
                'class' => 'cszipcode',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'paddressno' => array(
                'name' => 'paddressno',
                'type' => 'cinput',
                'label' => 'Address No',
                'class' => 'csaddressno',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'pstreet' => array(
                'name' => 'pstreet',
                'type' => 'cinput',
                'label' => 'Street',
                'class' => 'csstreet',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 150
            ),
            'psubdistown' => array(
                'name' => 'psubdistown',
                'type' => 'cinput',
                'label' => 'Subdivision/District/Town',
                'class' => 'csrsubdistown',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 150
            ),
            'pbrgy' => array(
                'name' => 'pbrgy',
                'type' => 'lookup',
                'label' => 'Barangay',
                'class' => 'csbrgy',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'action' => 'lookupprovcity'
            ),
            'pcity' => array(
                'name' => 'pcity',
                'type' => 'lookup',
                'label' => 'City',
                'class' => 'cscity',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'action' => 'lookupprovcity'
            ),
            'pprovince' => array(
                'name' => 'pprovince',
                'type' => 'lookup',
                'label' => 'Province',
                'class' => 'csprovince',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'action' => 'lookupprovcity'
            ),
            'pcountry' => array(
                'name' => 'pcountry',
                'type' => 'cinput',
                'label' => 'Country',
                'class' => 'cscountry',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),
            'pzipcode' => array(
                'name' => 'pzipcode',
                'type' => 'input',
                'label' => 'Zipcode',
                'class' => 'cszipcode',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'pob' => array(
                'name' => 'pob',
                'type' => 'input',
                'label' => 'Place of Birth',
                'class' => 'cspob',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'ispassport' => array(
                'name' => 'ispassport',
                'type' => 'checkbox',
                'label' => 'Passport',
                'class' => 'csispassport',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isprc' => array(
                'name' => 'isprc',
                'type' => 'checkbox',
                'label' => 'PRC',
                'class' => 'csisprc',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isseniorid' => array(
                'name' => 'isseniorid',
                'type' => 'checkbox',
                'label' => 'Senior ID',
                'class' => 'csisseniorid',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isdriverlisc' => array(
                'name' => 'isdriverlisc',
                'type' => 'checkbox',
                'label' => 'Driver License',
                'class' => 'csisdriverlisc',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isotherid' => array(
                'name' => 'isotherid',
                'type' => 'checkbox',
                'label' => 'Other',
                'class' => 'csisotherid',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'expiration' => array(
                'name' => 'expiration',
                'type' => 'input',
                'label' => 'Expiration',
                'class' => 'csexpiration',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isemployment' => array(
                'name' => 'isemployment',
                'type' => 'checkbox',
                'label' => 'Employment',
                'class' => 'csisemployment',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isleader' => array(
                'name' => 'isleader',
                'type' => 'checkbox',
                'label' => 'License Agent',
                'class' => 'csisleader',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isinvestment' => array(
                'name' => 'isinvestment',
                'type' => 'checkbox',
                'label' => 'Investment/Pension',
                'class' => 'csisinvestment',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isbusiness' => array(
                'name' => 'isbusiness',
                'type' => 'checkbox',
                'label' => 'Business',
                'class' => 'csisbusiness',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isothersource' => array(
                'name' => 'isothersource',
                'type' => 'checkbox',
                'label' => 'Others (specify)',
                'class' => 'csisothersource',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'othersource' => array(
                'name' => 'othersource',
                'type' => 'input',
                'label' => 'Others (specify)',
                'class' => 'csothersource',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'lessten' => array(
                'name' => 'lessten',
                'type' => 'checkbox',
                'label' => 'Less than P10,000',
                'class' => 'cslessten',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'tenthirty' => array(
                'name' => 'tenthirty',
                'type' => 'checkbox',
                'label' => 'P10,001 - P30,000',
                'class' => 'cstenthirty',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'thirtyfifty' => array(
                'name' => 'thirtyfifty',
                'type' => 'checkbox',
                'label' => 'P30,001 - P50,000',
                'class' => 'csthirtyfifty',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'fiftyhundred' => array(
                'name' => 'fiftyhundred',
                'type' => 'checkbox',
                'label' => 'P50,001 - P100,000',
                'class' => 'csfiftyhundred',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'hundredtwofifty' => array(
                'name' => 'hundredtwofifty',
                'type' => 'checkbox',
                'label' => 'P100,001 - P250,000',
                'class' => 'cshundredtwofifty',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'twofiftyfivehundred' => array(
                'name' => 'twofiftyfivehundred',
                'type' => 'checkbox',
                'label' => 'P250,001 - P500,000',
                'class' => 'cstwofiftyfivehundred',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'fivehundredup' => array(
                'name' => 'fivehundredup',
                'type' => 'checkbox',
                'label' => 'More Than P500,001',
                'class' => 'csfivehundredup',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isemployed' => array(
                'name' => 'isemployed',
                'type' => 'checkbox',
                'label' => 'Employed',
                'class' => 'csisemployed',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isselfemployed' => array(
                'name' => 'isselfemployed',
                'type' => 'checkbox',
                'label' => 'Self-Employed',
                'class' => 'csisselfemployed',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isofw' => array(
                'name' => 'isofw',
                'type' => 'checkbox',
                'label' => 'OFW',
                'class' => 'csisofw',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isretired' => array(
                'name' => 'isretired',
                'type' => 'checkbox',
                'label' => 'Retired/Pensioner',
                'class' => 'csisretired',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'iswife' => array(
                'name' => 'iswife',
                'type' => 'checkbox',
                'label' => 'Stay-at-Home/Spouse/Housewife',
                'class' => 'csiswife',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isnotemployed' => array(
                'name' => 'isnotemployed',
                'type' => 'checkbox',
                'label' => 'Not Employed/Student',
                'class' => 'csisnotemployed',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'sssgsis' => array(
                'name' => 'sssgsis',
                'type' => 'input',
                'label' => 'S.S.S/G.S.I.S #',
                'class' => 'cssssgsis',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'employer' => array(
                'name' => 'employer',
                'type' => 'input',
                'label' => 'Employer Name/Business Name',
                'class' => 'csemployer',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'otherplan' => array(
                'name' => 'otherplan',
                'type' => 'ctextarea',
                'label' => 'Does planholder have other Life Or Death Insurance? 
                If yes, Please specify.',
                'class' => 'csotherplan',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 200
            ),
            'afdocno' => array(
                'name' => 'afdocno',
                'type' => 'lookup',
                'label' => 'Application #',
                'class' => 'csafdocno sbccsreadonly',
                'action' => 'lookupafdocno',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'issameadd' => array(
                'name' => 'issameadd',
                'type' => 'checkbox',
                'label' => 'Same With Residence Address?',
                'class' => 'csissameadd',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isbene' => array(
                'name' => 'isbene',
                'type' => 'checkbox',
                'label' => 'No Beneficiary',
                'class' => 'csisbene',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'client2' => array(
                'name' => 'client2',
                'type' => 'fselect',
                'action' => 'clientselect',
                'selectclass' => 'customer',
                'selectaction' => '',
                'plottype' => 'plothead',
                'class' => 'csclient2',
                'plotting' => ['clientid' => 'clientid', 'client2' => 'label', 'client' => 'client', 'clientname' => 'label', 'addr' => 'description2', 'tel' => 'description'],
                'label' => 'Name',
                'style' => $this->style,
                'readonly' => true
            ),
            'conndate' => array(
                'name' => 'conndate',
                'type' => 'date',
                'label' => 'Connection',
                'class' => 'csconndate',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'disconndate' => array(
                'name' => 'disconndate',
                'type' => 'date',
                'label' => 'Disconnected',
                'class' => 'csdisconndate',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'issenior' => array(
                'name' => 'issenior',
                'type' => 'checkbox',
                'label' => 'Senior',
                'class' => 'csissenior',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isfp' => array(
                'name' => 'isfp',
                'type' => 'checkbox',
                'label' => 'Financing Partner',
                'class' => 'csisfp',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'loadmeter' => array(
                'name' => 'backlisting',
                'type' => 'actionbtn',
                'label' => 'Load Meter',
                'class' => 'btnloadmeter',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'refresh',
                'access' => 'save',
                'action' => 'loadmeter',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false
            ),
            'planholder' => array(
                'name' => 'planholder',
                'type' => 'input',
                'label' => 'Plan Holder',
                'class' => 'csplanholder sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'trnxtype' => array(
                'name' => 'trnxtype',
                'type' => 'lookup',
                'label' => 'Trnx Type',
                'class' => 'cstrnxtype sbccsreadonly',
                'action' => 'lookuptrnxtype',
                'lookupclass' => 'lookuptrnxtype',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'leasecontract' => array(
                'name' => 'leasecontract',
                'type' => 'input',
                'label' => 'Lease Contract',
                'class' => 'csleasecontract',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
            ),
            'emailcc' => array(
                'name' => 'emailcc',
                'type' => 'input',
                'label' => 'CC',
                'class' => 'csemailcc',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'emailsubject' => array(
                'name' => 'emailsubject',
                'type' => 'input',
                'label' => 'Subject',
                'class' => 'csemailsubject',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'emailbody' => array(
                'name' => 'emailbody',
                'type' => 'wysiwyg',
                'label' => 'Body',
                'class' => 'csemailbody',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'emailattachment' => array(
                'name' => 'emailattachment',
                'type' => 'file',
                'label' => 'Attachment',
                'class' => 'csemailattachment',
                'style' => $this->style,
                'required' => false
            ),
            'carrier' => array(
                'name' => 'carrier',
                'type' => 'input',
                'label' => 'Carrier',
                'class' => 'cscarrier',
                'style' => $this->style,
                'required' => false,
                'readonly' => false
            ),
            'declaredval' => array(
                'name' => 'declaredval',
                'type' => 'input',
                'label' => 'Declared Value',
                'class' => 'csdeclaredval',
                'style' => $this->style,
                'required' => false,
                'readonly' => false
            ),
            'lblctrlno' => array(
                'name' => 'lblctrlno',
                'type' => 'label',
                'label' => 'Ctrl No.: ',
                'class' => 'cslblctrlno',
                'style' => 'font-weight:bold;font-size:16px'
            ),
            'lblitemname' => array(
                'name' => 'lblitemname',
                'type' => 'label',
                'label' => 'Item Name: ',
                'class' => 'cslblitemname',
                'style' => 'font-weight:bold;font-size:16px'
            ),
            'lblspecs' => array(
                'name' => 'lblspecs',
                'type' => 'label',
                'label' => 'Specifications:',
                'class' => 'cslblspecs',
                'style' => 'font-weight:bold;font-size:16px'
            ),
            'lbluom' => array(
                'name' => 'lbluom',
                'type' => 'label',
                'label' => 'UOM:',
                'class' => 'cslbluom',
                'style' => 'font-weight:bold;font-size:16px'
            ),
            'lblpono' => array(
                'name' => 'lblpono',
                'type' => 'label',
                'label' => 'PO No. ("Actual Paper")',
                'class' => 'cslblpono',
                'style' => 'font-weight:bold;font-size:16px'
            ),
            'lblporem' => array(
                'name' => 'lblporem',
                'type' => 'label',
                'label' => 'Notes',
                'class' => 'cslblporem',
                'style' => 'font-weight:bold;font-size:16px'
            ),
            'lblrrrem' => array(
                'name' => 'lblrrrem',
                'type' => 'label',
                'label' => 'Notes',
                'class' => 'cslblrrrem',
                'style' => 'font-weight:bold;font-size:16px'
            ),
            'lblpodocno' => array(
                'name' => 'lblpodocno',
                'type' => 'label',
                'label' => 'PO Docno:',
                'class' => 'cslblpodocno',
                'style' => 'font-weight:bold;font-size:16px'
            ),
            'lblsupplier' => array(
                'name' => 'lblsupplier',
                'type' => 'label',
                'label' => 'Supplier:',
                'class' => 'cslblsupplier',
                'style' => 'font-weight:bold;font-size:16px'
            ),
            'lblrrno' => array(
                'name' => 'lblrrno',
                'type' => 'label',
                'label' => 'RR No.:',
                'class' => 'cslblrrno',
                'style' => 'font-weight:bold;font-size:16px'
            ),
            'lblrrcost' => array(
                'name' => 'lblrrcost',
                'type' => 'label',
                'label' => 'Price:',
                'class' => 'cslblrrcost',
                'style' => 'font-weight:bold;font-size:16px'
            ),
            'lblext' => array(
                'name' => 'lblext',
                'type' => 'label',
                'label' => 'Ext:',
                'class' => 'cslblext',
                'style' => 'font-weight:bold;font-size:16px'
            ),
            'lbldropwh' => array(
                'name' => 'lbldropwh',
                'type' => 'label',
                'label' => 'Drop Off Warehouse:',
                'class' => 'cslbldropwh',
                'style' => 'font-weight:bold;font-size:16px'
            ),
            'lblmainwh' => array(
                'name' => 'lblmainwh',
                'type' => 'label',
                'label' => 'Main Warehouse:',
                'class' => 'cslblmainwh',
                'style' => 'font-weight:bold;font-size:16px'
            ),
            'lblrequestor' => array(
                'name' => 'lblrequestor',
                'type' => 'label',
                'label' => 'Requestor:',
                'class' => 'cslblrequestor',
                'style' => 'font-weight:bold;font-size:16px'
            ),
            'lbldepartment' => array(
                'name' => 'lbldepartment',
                'type' => 'label',
                'label' => 'Department:',
                'class' => 'cslbldepartment',
                'style' => 'font-weight:bold;font-size:16px'
            ),
            'lblprojname' => array(
                'name' => 'lblprojname',
                'type' => 'label',
                'label' => 'Client/Project Name:',
                'class' => 'cslblprojname',
                'style' => 'font-weight:bold;font-size:16px'
            ),
            'ctrlno' => array(
                'name' => 'ctrlno',
                'type' => 'input',
                'label' => '',
                'class' => 'csctrlno',
                'style' => $this->style,
                'required' => false,
                'readonly' => true
            ),
            'omdocno' => array(
                'name' => 'omdocno',
                'type' => 'input',
                'label' => 'OSI Docno',
                'class' => 'csomdocno',
                'style' => $this->style,
                'required' => false,
                'readonly' => true
            ),
            'podocno' => array(
                'name' => 'podocno',
                'type' => 'input',
                'label' => '',
                'class' => 'cspodocno',
                'style' => $this->style,
                'required' => false,
                'readonly' => true
            ),
            'supplier' => array(
                'name' => 'supplier',
                'type' => 'input',
                'label' => '',
                'class' => 'cssupplier',
                'style' => $this->style,
                'required' => false,
                'readonly' => true
            ),
            'rrno' => array(
                'name' => 'rrno',
                'type' => 'input',
                'label' => '',
                'class' => 'csrrno',
                'style' => $this->style,
                'required' => false,
                'readonly' => true
            ),
            'mainwh' => array(
                'name' => 'mainwh',
                'type' => 'input',
                'label' => '',
                'class' => 'csmainwh',
                'style' => $this->style,
                'required' => false,
                'readonly' => true
            ),
            'lbldpno' => array(
                'name' => 'lbldpno',
                'type' => 'label',
                'label' => 'DP No.: ',
                'class' => 'cslbldpno',
                'style' => 'font-weight:bold;font-size:16px;'
            ),
            'dpno' => array(
                'name' => 'dpno',
                'type' => 'input',
                'label' => '',
                'class' => 'csdpno',
                'style' => $this->style,
                'readonly' => true
            ),
            'lbldpdate' => array(
                'name' => 'lbldpdate',
                'type' => 'label',
                'label' => 'Dispatch Date: ',
                'class' => 'cslbldpdate',
                'style' => 'font-weight:bold;font-size:16px;'
            ),
            'dpdate' => array(
                'name' => 'dpdate',
                'type' => 'input',
                'label' => '',
                'class' => 'csdpdate',
                'style' => $this->style,
                'readonly' => true
            ),
            'lblfreightno' => array(
                'name' => 'lblfreightno',
                'type' => 'label',
                'label' => 'Freight OR No.:',
                'class' => 'cslblfreightno',
                'style' => 'font-weight:bold;font-size:16px;'
            ),
            'freightno' => array(
                'name' => 'freightno',
                'type' => 'input',
                'label' => '',
                'class' => 'csfreightno',
                'style' => $this->style,
                'readonly' => true
            ),
            'lblfreightamt' => array(
                'name' => 'lblfreightamt',
                'type' => 'label',
                'label' => 'Amount:',
                'class' => 'cslblfreightamt',
                'style' => 'font-weight:bold;font-size:16px;'
            ),
            'freightamt' => array(
                'name' => 'freightamt',
                'type' => 'input',
                'label' => '',
                'class' => 'csfreightamt',
                'style' => $this->style,
                'readonly' => true
            ),
            'lblhandno' => array(
                'name' => 'lblhandno',
                'type' => 'label',
                'label' => 'Handling OR No.:',
                'class' => 'cslblhandno',
                'style' => 'font-weight:bold;font-size:16px;'
            ),
            'handno' => array(
                'name' => 'handno',
                'type' => 'input',
                'label' => '',
                'class' => 'cshandno',
                'style' => $this->style,
                'readonly' => true
            ),
            'lblhandnoamt' => array(
                'name' => 'lblhandnoamt',
                'type' => 'label',
                'label' => 'Amount:',
                'class' => 'cslblhandnoamt',
                'style' => 'font-weight:bold;font-size:16px;'
            ),
            'handnoamt' => array(
                'name' => 'handnoamt',
                'type' => 'input',
                'label' => '',
                'class' => 'cshandnoamt',
                'style' => $this->style,
                'readonly' => true
            ),
            'lblwharfno' => array(
                'name' => 'lblwharfno',
                'type' => 'label',
                'label' => 'Wharfage OR No.:',
                'class' => 'cslblwharfno',
                'style' => 'font-weight:bold;font-size:16px;'
            ),
            'wharfno' => array(
                'name' => 'wharfno',
                'type' => 'input',
                'label' => '',
                'class' => 'cswharfno',
                'style' => $this->style,
                'readonly' => true
            ),
            'lblwharfnoamt' => array(
                'name' => 'lblwharfnoamt',
                'type' => 'label',
                'label' => 'Amount:',
                'class' => 'cslblwharfnoamt',
                'style' => 'font-weight:bold;font-size:16px;'
            ),
            'wharfnoamt' => array(
                'name' => 'wharfnoamt',
                'type' => 'input',
                'label' => '',
                'class' => 'cswharfnoamt',
                'style' => $this->style,
                'readonly' => true
            ),
            'lblpermitfee' => array(
                'name' => 'lblpermitfee',
                'type' => 'label',
                'label' => 'Permit Fee:',
                'class' => 'cslblpermitfee',
                'style' => 'font-weight:bold;font-size:16px;'
            ),
            'permitfee' => array(
                'name' => 'permitfee',
                'type' => 'input',
                'label' => '',
                'class' => 'cspermitfee',
                'style' => $this->style,
                'readonly' => true
            ),
            'agentfee' => array(
                'name' => 'agentfee',
                'type' => 'input',
                'label' => "Agent's Fee",
                'class' => 'csagentfee',
                'style' => $this->style,
                'readonly' => false
            ),
            'lblvpassno' => array(
                'name' => 'lblvpassno',
                'type' => 'label',
                'label' => 'Vehicle Pass OR No.:',
                'class' => 'cslblvpassno',
                'style' => 'font-weight:bold;font-size:16px;'
            ),
            'vpassno' => array(
                'name' => 'vpassno',
                'type' => 'input',
                'label' => '',
                'class' => 'csvpassno',
                'style' => $this->style,
                'readonly' => true
            ),
            'lblvpassnoamt' => array(
                'name' => 'lblvpassnoamt',
                'type' => 'label',
                'label' => 'Amount:',
                'class' => 'cslblvpassnoamt',
                'style' => 'font-weight:bold;font-size:16px;'
            ),
            'vpassnoamt' => array(
                'name' => 'vpassnoamt',
                'type' => 'input',
                'label' => '',
                'class' => 'csvpassnoamt',
                'style' => $this->style,
                'readonly' => true
            ),
            'lblmiscno' => array(
                'name' => 'lblmiscno',
                'type' => 'label',
                'label' => 'Misc/Crating OR No.:',
                'class' => 'cslblmiscno',
                'style' => 'font-weight:bold;font-size:16px;'
            ),
            'miscno' => array(
                'name' => 'miscno',
                'type' => 'input',
                'label' => '',
                'class' => 'csmiscno',
                'style' => $this->style,
                'readonly' => true
            ),
            'lblmiscnoamt' => array(
                'name' => 'lblmiscnoamt',
                'type' => 'label',
                'label' => 'Amount:',
                'class' => 'cslblmiscnoamt',
                'style' => 'font-weight:bold;font-size:16px;'
            ),
            'miscnoamt' => array(
                'name' => 'miscnoamt',
                'type' => 'input',
                'label' => '',
                'class' => 'csmiscnoamt',
                'style' => $this->style,
                'readonly' => true
            ),
            'lblvoyno' => array(
                'name' => 'lblvoyno',
                'type' => 'label',
                'label' => 'VOY No.:',
                'class' => 'cslblvoyno',
                'style' => 'font-weight:bold;font-size:16px;'
            ),
            'voyno' => array(
                'name' => 'voyno',
                'type' => 'input',
                'label' => '',
                'class' => 'csvoyno',
                'style' => $this->style,
                'readonly' => true
            ),
            'lblblno' => array(
                'name' => 'lblblno',
                'type' => 'label',
                'label' => 'BL No.:',
                'class' => 'cslblblno',
                'style' => 'font-weight:bold;font-size:16px;'
            ),
            'blno' => array(
                'name' => 'blno',
                'type' => 'input',
                'label' => '',
                'class' => 'csblno',
                'style' => $this->style,
                'readonly' => true
            ),
            'lblshipline' => array(
                'name' => 'lblshipline',
                'type' => 'label',
                'label' => 'Shipping Line:',
                'class' => 'cslblshipline',
                'style' => 'font-weight:bold;font-size:16px;'
            ),
            'shipline' => array(
                'name' => 'shipline',
                'type' => 'input',
                'label' => '',
                'class' => 'csshipline',
                'style' => $this->style,
                'readonly' => true
            ),
            'lblvessel' => array(
                'name' => 'lblvessel',
                'type' => 'label',
                'label' => 'Vessel:',
                'class' => 'cslblvessel',
                'style' => 'font-weight:bold;font-size:16px;'
            ),
            'vessel' => array(
                'name' => 'vessel',
                'type' => 'input',
                'label' => 'Vessel:',
                'class' => 'csvessel',
                'style' => $this->style,
                'readonly' => true
            ),
            'voyageno' => array(
                'name' => 'voyageno',
                'type' => 'input',
                'label' => 'Voyage No.:',
                'class' => 'csvoyageno',
                'style' => $this->style,
                'readonly' => true
            ),
            'sealno' => array(
                'name' => 'sealno',
                'type' => 'input',
                'label' => 'Seal No.:',
                'class' => 'cssealno',
                'style' => $this->style,
                'readonly' => true
            ),
            'unit' => array(
                'name' => 'unit',
                'type' => 'input',
                'label' => 'Unit:',
                'class' => 'csunit',
                'style' => $this->style,
                'readonly' => true
            ),
            'loadedby' => array(
                'name' => 'loadedby',
                'type' => 'input',
                'label' => 'Loaded by:',
                'class' => 'csloadedby',
                'style' => $this->style,
                'readonly' => true
            ),
            'freight' => array(
                'name' => 'freight',
                'type' => 'input',
                'label' => 'Freight',
                'class' => 'csfreight',
                'style' => $this->style,
                'readonly' => false
            ),
            'collector' => array(
                'name' => 'collector',
                'type' => 'input',
                'label' => 'Collector',
                'class' => 'cscollector',
                'style' => $this->style,
                'readonly' => false
            ),
            'isinclude' => array(
                'name' => 'isinclude',
                'type' => 'checkbox',
                'label' => 'Include Unauditted',
                'class' => 'csisinclude',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'istrip' => array(
                'name' => 'istrip',
                'type' => 'checkbox',
                'label' => 'Tripping',
                'class' => 'csistrip',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'amt16' => array(
                'name' => 'amt16',
                'label' => 'Base Price',
                'type' => 'input',
                'readonly' => false
            ),
            'disc16' => array(
                'name' => 'disc16',
                'label' => 'Discount R (R)',
                'type' => 'input',
                'readonly' => false
            ),
            'disc17' => array(
                'name' => 'disc17',
                'label' => 'Discount W (W)',
                'type' => 'input',
                'readonly' => false
            ),
            'disc18' => array(
                'name' => 'disc18',
                'label' => 'Discount A (A)',
                'type' => 'input',
                'readonly' => false
            ),
            'disc19' => array(
                'name' => 'disc19',
                'label' => 'Discount B (B)',
                'type' => 'input',
                'readonly' => false
            ),
            'disc20' => array(
                'name' => 'disc20',
                'label' => 'Discount C (C)',
                'type' => 'input',
                'readonly' => false
            ),
            'disc21' => array(
                'name' => 'disc21',
                'label' => 'Discount D (D)',
                'type' => 'input',
                'readonly' => false
            ),
            'disc22' => array(
                'name' => 'disc22',
                'label' => 'Discount E (E)',
                'type' => 'input',
                'readonly' => false
            ),
            'item_length' => array(
                'name' => 'item_length',
                'label' => 'Lenght',
                'type' => 'input',
                'class' => 'csitem_length',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'item_width' => array(
                'name' => 'item_width',
                'label' => 'Width',
                'type' => 'input',
                'class' => 'csitem_width',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'item_height' => array(
                'name' => 'item_height',
                'label' => 'Height',
                'type' => 'input',
                'class' => 'csitem_height',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'volume' => array(
                'name' => 'volume',
                'label' => 'Volume',
                'type' => 'input',
                'class' => 'csvolume',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'weight' => array(
                'name' => 'weight',
                'label' => 'Weight',
                'type' => 'input',
                'class' => 'csweight',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'chassisno' => array(
                'name' => 'chassisno',
                'label' => 'Chassis No',
                'type' => 'input',
                'class' => 'cschassisno',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'asofdate' => array(
                'name' => 'asofdate',
                'type' => 'date',
                'label' => 'As Of',
                'class' => 'csasofdate',
                'readonly' => true,
                'style' => $this->style
            ),
            'seqstart' => array(
                'name' => 'seqstart',
                'type' => 'input',
                'label' => 'Start of Transaction#',
                'class' => 'csseqstart',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
            ),

            'seqend' => array(
                'name' => 'seqend',
                'type' => 'input',
                'label' => 'End of Transaction#',
                'class' => 'csasofseq',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
            ),
            'stockcardfilter' => array(
                'name' => 'stockcardfilter',
                'type' => 'lookup',
                'label' => 'Filter',
                'class' => 'csstockcardfilter',
                'lookupclass' => 'stockcardfilter',
                'action' => 'lookupstockcardfilter',
                'readonly' => true,
                'style' => $this->style
            ),
            'sex' => array(
                'name' => 'sex',
                'type' => 'lookup',
                'label' => 'Gender',
                'class' => 'csgender sbccsreadonly',
                'lookupclass' => 'sex',
                'action' => 'lookupgender',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'father' => array(
                'name' => 'father',
                'label' => 'Father`s Name',
                'type' => 'input',
                'class' => 'csfather',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'mother' => array(
                'name' => 'mother',
                'label' => 'Mother`s Name',
                'type' => 'input',
                'class' => 'csmother',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'height' => array(
                'name' => 'height',
                'label' => 'Height',
                'type' => 'input',
                'class' => 'csheight',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'accountid' => array(
                'name' => 'accountid',
                'label' => 'LTO Client ID',
                'type' => 'input',
                'class' => 'csaccountid',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'barcodeid' => array(
                'name' => 'barcodeid',
                'type' => 'input',
                'label' => 'Item ID',
                'class' => 'csbarcodeid',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'orderno' => array(
                'name' => 'orderno',
                'type' => 'cinput',
                'label' => 'Billing Period',
                'class' => 'csyourref',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'dfpname' => array(
                'name' => 'dfpname',
                'type' => 'lookup',
                'label' => 'Financing Partner',
                'labeldata' => 'fp~fpname',
                'class' => 'csdfpname sbccsreadonly',
                'lookupclass' => 'fp',
                'action' => 'lookupclient',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'ordertype' => array(
                'name' => 'ordertype',
                'type' => 'lookup',
                'label' => 'Order Type',
                'class' => 'csordertype sbccsreadonly',
                'lookupclass' => 'lookupordertype',
                'action' => 'lookupordertype',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),


            'channel' => array(
                'name' => 'channel',
                'type' => 'lookup',
                'label' => 'Channel',
                'class' => 'cschannel sbccsreadonly',
                'lookupclass' => 'lookupchannel',
                'action' => 'lookupchannel',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),



            'clienttype' => array(
                'name' => 'clienttype',
                'type' => 'lookup',
                'label' => 'Client Type',
                'class' => 'csclienttype sbccsreadonly',
                'lookupclass' => 'lookupclienttype',
                'action' => 'lookupclienttype',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'submit' => array(
                'name' => 'submit',
                'type' => 'actionbtn',
                'label' => 'SUBMIT',
                'class' => 'btnsubmit',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'check',
                'access' => 'save',
                'action' => 'submit',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false
            ),
            'forquotation' => array(
                'name' => 'forquotation',
                'type' => 'actionbtn',
                'label' => 'SUBMIT',
                'class' => 'btnforquotation',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'check',
                'access' => 'save',
                'action' => 'forquotation',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false
            ),

            'open' => array(
                'name' => 'backlisting',
                'type' => 'actionbtn',
                'label' => 'Open',
                'class' => 'btnopen',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'check',
                'access' => 'save',
                'action' => 'open',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false
            ),

            'inprogress' => array(
                'name' => 'backlisting',
                'type' => 'actionbtn',
                'label' => 'In-Progress',
                'class' => 'btninprogress',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'check',
                'access' => 'save',
                'action' => 'inprogress',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false
            ),

            'resolved' => array(
                'name' => 'backlisting',
                'type' => 'actionbtn',
                'label' => 'Resolved',
                'class' => 'btnresolved',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'check',
                'access' => 'save',
                'action' => 'resolved',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false
            ),
            'reopen' => array(
                'name' => 'backlisting',
                'type' => 'actionbtn',
                'label' => 'Re-Open',
                'class' => 'btnreopen',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'check',
                'access' => 'save',
                'action' => 'reopen',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false
            ),

            'assetname' => array(
                'name' => 'assetname',
                'type' => 'lookup',
                'label' => 'Asset/Truck',
                'labeldata' => 'barcode~itemname',
                'class' => 'csassetname sbccsreadonly',
                'lookupclass' => 'lookupasset_head',
                'action' => 'lookupasset_head',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'openstat' => array(
                'name' => 'openstat',
                'type' => 'label',
                'label' => 'OPEN',
                'class' => '',
                'style' => 'font-family: Century Gothic; font-size: 50px; font-weight: bold; color: #f7c200; text-align:center; display:flex;justify-content:center;'
            ),

            'iprogresstat' => array(
                'name' => 'iprogresstat',
                'type' => 'label',
                'label' => 'IN PROGRESS',
                'class' => '',
                'style' =>  'font-family: Century Gothic; font-size: 50px; font-weight: bold; color: #f7c200; text-align:center; display:flex;justify-content:center;'
            ),

            'resolvedstat' => array(
                'name' => 'resolvedstat',
                'type' => 'label',
                'label' => 'RESOLVED',
                'class' => '',
                'style' => 'font-family: Century Gothic; font-size: 50px; font-weight: bold; color: #f7c200; text-align:center; display:flex;justify-content:center;'
            ),


            'plbyitemlookup' => array(
                'name' => 'plbyitemlookup',
                'type' => 'lookup',
                'label' => 'Barcode',
                'labeldata' => 'barcode~itemdesc',
                'class' => 'csplbyitemlookup sbccsreadonly',
                'lookupclass' => 'plbyitemlookup',
                'action' => 'plbyitemlookup',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),


            'radiorepdtagathering' => array(
                'name' => 'dtagathering',
                'label' => 'Data Gathering Format',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Current', 'value' => 'dcurrent', 'color' => 'orange'],
                    ['label' => 'History', 'value' => 'dhistory', 'color' => 'orange']
                )
            ),

            'dcustomer' => array(
                'name' => 'dcustomer',
                'type' => 'lookup',
                'label' => 'Customer ID',
                'labeldata' => 'customercode~customername',
                'class' => 'cscontra sbccsreadonly',
                'lookupclass' => 'customerid',
                'action' => 'lookupclient',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'mmname' => array(
                'name' => 'mmname',
                'type' => 'input',
                'label' => 'Mothers Maiden Name',
                'class' => 'csmmname',
                'readonly' => false,
                'style' => $this->style,
                'required' => true,
                'maxlength' => 100
            ),

            'dependentsno' => array(
                'name' => 'dependentsno',
                'type' => 'input',
                'label' => 'No. Of Dependents',
                'class' => 'csdependentsno',
                'readonly' => false,
                'style' => $this->style,
                'required' => true,
                'maxlength' => 100
            ),

            'sname' => array(
                'name' => 'sname',
                'type' => 'input',
                'label' => 'Name of Spouse',
                'class' => 'cssname',
                'readonly' => false,
                'style' => $this->style,
                'required' => true,
                'maxlength' => 100
            ),

            'credits' => array(
                'name' => 'credits',
                'type' => 'input',
                'label' => 'Credit Committee ',
                'class' => 'cscredits',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),

            'value' => array(
                'name' => 'value',
                'type' => 'cinput',
                'label' => 'Value',
                'class' => 'csvalue',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'cvno' => array(
                'name' => 'cvno',
                'type' => 'input',
                'label' => 'CV#',
                'class' => 'cscvno',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'gjno' => array(
                'name' => 'gjno',
                'type' => 'input',
                'label' => 'Takeout Fee GJ#',
                'class' => 'csgjno sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100,
            ),
            'lblstatus' => array(
                'name' => 'lblstatus',
                'type' => 'input',
                'label' => '',
                'class' => '',
                'style' => 'font-weight:bold'

            ),

            'station_rep' => array(
                'name' => 'station_rep',
                'type' => 'lookup',
                'label' => 'Station',
                'labeldata' => 'stationline~stationname',
                'class' => 'csstation_rep sbccsreadonly',
                'lookupclass' => 'lookupstationreport',
                'action' => 'lookupstationreport',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'radioitemformat' => array(
                'name' => 'radioitemformat',
                'label' => 'Item Format',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'All Items', 'value' => 'All', 'color' => 'green'],
                    ['label' => 'Non-Inventory Items', 'value' => 'NInv', 'color' => 'red']
                )
            ),

            'purposeofpayment' => array(
                'name' => 'purposeofpayment',
                'type' => 'lookup',
                'label' => 'Payment Purpose',
                'class' => 'cspp sbccsreadonly',
                'lookupclass' => 'lookuppurposeofpayment',
                'action' => 'lookuppurposeofpayment',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'bank' => array(
                'name' => 'bank',
                'type' => 'input',
                'label' => 'Bank',
                'class' => 'csbank',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),

            'modeofpayment2' => array(
                'name' => 'modeofpayment2',
                'type' => 'lookup',
                'label' => 'Mode of Payment',
                'class' => 'csmp sbccsreadonly',
                'lookupclass' => 'lookupmodeofpayment2',
                'action' => 'lookupmodeofpayment2',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'trnxtype2' => array(
                'name' => 'trnxtype2',
                'type' => 'lookup',
                'label' => 'Transaction Type',
                'class' => 'cstt sbccsreadonly',
                'lookupclass' => 'lookuptrnxtype2',
                'action' => 'lookuptrnxtype2',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'totalcoll' => array(
                'name' => 'totalcoll',
                'type' => 'input',
                'label' => 'Total Collection',
                'class' => 'cstotalcoll',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'totaldep' => array(
                'name' => 'totaldep',
                'type' => 'input',
                'label' => 'Total Deposit',
                'class' => 'cstotaldep',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'avecost' => array(
                'name' => 'avecost',
                'type' => 'input',
                'label' => 'Avg. Cost',
                'class' => 'csamt',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),

            'runtime' => array(
                'name' => 'runtime',
                'type' => 'input',
                'label' => 'Runtime (mins)',
                'class' => 'csruntime',
                'readonly' => false,
                'style' => $this->style,
                'required' => true
            ),
            'gp' => array(
                'name' => 'gp',
                'type' => 'input',
                'label' => 'Access Time (Hours)',
                'class' => 'csgp',
                'readonly' => false,
                'style' => $this->style,
                'required' => true
            ),
            'startdate' => array(
                'name' => 'startdate',
                'type' => 'date',
                'label' => 'Start Date',
                'class' => 'csstartdate',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),
            'termfrom' => array(
                'name' => 'termfrom',
                'type' => 'date',
                'label' => 'From: ',
                'class' => 'cstermfrom',
                'readonly' => false,
                'style' => $this->style,
                'required' => true
            ),
            'termto' => array(
                'name' => 'termto',
                'type' => 'date',
                'label' => 'To: ',
                'class' => 'cstermto',
                'readonly' => false,
                'style' => $this->style,
                'required' => true
            ),
            'question' => array(
                'name' => 'question',
                'type' => 'ctextarea',
                'label' => 'Question',
                'class' => 'csquestion',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 100
            ),
            'points' => array(
                'name' => 'points',
                'type' => 'input',
                'label' => 'Point(s)',
                'class' => 'cspoints',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'answord' => array(
                'name' => 'answord',
                'type' => 'input',
                'label' => 'Answer',
                'class' => 'csansword',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'lblchoice' => array(
                'name' => 'lblchoice',
                'type' => 'label',
                'label' => 'Choices',
                'class' => '',
                'style' => 'font-weight:bold;font-size:12px;'
            ),
            'lblanswer' => array(
                'name' => 'lblanswer',
                'type' => 'label',
                'label' => 'Correct Answer',
                'class' => '',
                'style' => 'font-weight:bold;font-size:12px;'
            ),
            'a' => array(
                'name' => 'a',
                'type' => 'input',
                'label' => 'A',
                'class' => 'csquestion',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'b' => array(
                'name' => 'b',
                'type' => 'input',
                'label' => 'B',
                'class' => 'csquestion',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'c' => array(
                'name' => 'c',
                'type' => 'input',
                'label' => 'C',
                'class' => 'csquestion',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'd' => array(
                'name' => 'd',
                'type' => 'input',
                'label' => 'D',
                'class' => 'csquestion',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'e' => array(
                'name' => 'e',
                'type' => 'input',
                'label' => 'E',
                'class' => 'csquestion',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'isa' => array(
                'name' => 'isa',
                'type' => 'checkbox',
                'label' => '',
                'class' => 'csisa',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'isb' => array(
                'name' => 'isb',
                'type' => 'checkbox',
                'label' => '',
                'class' => 'csisb',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'isc' => array(
                'name' => 'isc',
                'type' => 'checkbox',
                'label' => '',
                'class' => 'csisc',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'isd' => array(
                'name' => 'isd',
                'type' => 'checkbox',
                'label' => '',
                'class' => 'csisa',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'ise' => array(
                'name' => 'ise',
                'type' => 'checkbox',
                'label' => '',
                'class' => 'csise',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),

            'forcancellation' => array(
                'name' => 'backlisting',
                'type' => 'actionbtn',
                'label' => 'For Approval',
                'class' => 'btnforcancellation',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'done',
                'access' => 'save',
                'action' => 'forcancellation',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false
            ),
            'obapp1' => array(
                'name' => 'obapp1',
                'type' => 'input',
                'label' => 'Name',
                'class' => 'csobapp1',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'obapp2' => array(
                'name' => 'obapp2',
                'type' => 'input',
                'label' => 'Name',
                'class' => 'csobapp2',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'dacnoname3' => array(
                'name' => 'dacnoname3',
                'type' => 'lookup',
                'label' => 'Account 3',
                'labeldata' => 'contra3~acnoname3',
                'class' => 'cscontra sbccsreadonly',
                'lookupclass' => 'detail3',
                'action' => 'lookupcoa',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'dacnoname4' => array(
                'name' => 'dacnoname4',
                'type' => 'lookup',
                'label' => 'Account 2',
                'labeldata' => 'contra4~acnoname4',
                'class' => 'cscontra sbccsreadonly',
                'lookupclass' => 'detail4',
                'action' => 'lookupcoa',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'acctadvances' => array(
                'name' => 'acctadvances',
                'type' => 'lookup',
                'label' => 'Account Advances',
                'class' => 'cscontra sbccsreadonly',
                'lookupclass' => 'acctadvances',
                'action' => 'lookupcoa',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'preempexam' => array(
                'name' => 'backlisting',
                'type' => 'actionbtn',
                'label' => 'For Pre-Employment Exam',
                'class' => 'btnpreempexam',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'done',
                'access' => 'save',
                'action' => 'preempexam',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false
            ),
            'backgroundcheck' => array(
                'name' => 'backlisting',
                'type' => 'actionbtn',
                'label' => 'For Background Checking',
                'class' => 'btnbackgroundcheck',
                'lookupclass' => 'backgroundcheck',
                'icon' => 'done',
                'access' => 'save',
                'action' => 'customform',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false
            ),
            'finalinterview' => array(
                'name' => 'backlisting',
                'type' => 'actionbtn',
                'label' => 'For Final Interview',
                'class' => 'btnfinalinterview',
                'lookupclass' => 'finalinterview',
                'icon' => 'done',
                'access' => 'save',
                'action' => 'customform',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false
            ),
            'preempreq' => array(
                'name' => 'backlisting',
                'type' => 'actionbtn',
                'label' => 'Hiring & Pre-Employment Requirements',
                'class' => 'btnpreempreq',
                'lookupclass' => 'preempreq',
                'icon' => 'done',
                'access' => 'save',
                'action' => 'customform',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false
            ),
            'empjoboffer' => array(
                'name' => 'backlisting',
                'type' => 'actionbtn',
                'label' => 'For Job Offer',
                'class' => 'btnempjoboffer',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'done',
                'access' => 'save',
                'action' => 'empjoboffer',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false
            ),
            'lblbank' => array(
                'name' => 'lblbank',
                'type' => 'label',
                'label' => 'Bank Details',
                'class' => '',
                'style' => 'font-weight:bold;font-size:15px;'
            ),
            'lblTaxStatus' => array(
                'name' => 'lblbank',
                'type' => 'label',
                'label' => 'Tax Status',
                'class' => '',
                'style' => 'font-weight:bold;font-size:15px;'
            ),
            'radiobank' => array(
                'name' => 'bank',
                'type' => 'qradio',
                'items' => [
                    'metro' => ['val' => '1', 'label' => 'Metrobank', 'color' => 'blue'],
                    'bpi' => ['val' => '2', 'label' => 'BPI', 'color' => 'red']
                ]
            ),
            'lblresult' => array(
                'name' => 'lblresult',
                'type' => 'label',
                'label' => 'Questionnaire Result',
                'class' => 'cslblacquisition',
                'style' => 'font-weight:bold;font-size:20px'
            ),
            'totalpoints' => array(
                'name' => 'totalpoints',
                'type' => 'input',
                'label' => 'Score',
                'class' => 'cstotalpoints',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'sbu' => array(
                'name' => 'sbu',
                'type' => 'lookup',
                'label' => 'SBU',
                'class' => 'cssbu sbccsreadonly',
                'lookupclass' => 'lookupsbu',
                'action' => 'lookupsbu',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'voidint' => array(
                'name' => 'voidint',
                'type' => 'input',
                'label' => 'Terms Allowance (No. of mos.)',
                'class' => 'cssbu ',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'permanentaddr' => array(
                'name' => 'permanentaddr',
                'type' => 'textarea',
                'label' => 'Permanent Address',
                'class' => 'cspermanentaddr',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 200
            ),

            'rvoter' => array(
                'name' => 'rvoter',
                'type' => 'lookup',
                'lookupclass' => 'lookupyesno',
                'action' => 'lookuprandom',
                'label' => 'Registered Voter',
                'class' => 'csrvoter',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),

            'hhold' => array(
                'name' => 'hhold',
                'type' => 'lookup',
                'lookupclass' => 'lookuphousehold',
                'action' => 'lookuprandom',
                'label' => 'House Hold',
                'class' => 'cshhold',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),
            'settlertype' => array(
                'name' => 'settlertype',
                'type' => 'lookup',
                'lookupclass' => 'lookupsettlertype',
                'action' => 'lookuprandom',
                'label' => 'Settle Type',
                'class' => 'cssettlertype',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),
            'attainment1' => array(
                'name' => 'attainment1',
                'type' => 'input',
                'label' => 'Educational Attainment',
                'class' => 'csattainment1',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 150
            ),

            'attainment2' => array(
                'name' => 'attainment2',
                'type' => 'input',
                'label' => 'Educational Attainment',
                'class' => 'csattainment2',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 150
            ),

            'occupation1' => array(
                'name' => 'occupation1',
                'type' => 'input',
                'label' => 'Occupation',
                'class' => 'csoccupation1',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 80
            ),

            'occupation2' => array(
                'name' => 'occupation2',
                'type' => 'input',
                'label' => 'Occupation',
                'class' => 'csoccupation2',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 80
            ),
            'skill1' => array(
                'name' => 'skill1',
                'type' => 'input',
                'label' => 'Skill',
                'class' => 'csskill1',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 80
            ),
            'skill2' => array(
                'name' => 'skill2',
                'type' => 'input',
                'label' => 'Skill',
                'class' => 'csskill2',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 80
            ),

            'bday2' => array(
                'name' => 'bday2',
                'type' => 'date',
                'label' => 'Birthday',
                'class' => 'csbday2',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),

            'rvoter' => array(
                'name' => 'rvoter',
                'type' => 'lookup',
                'lookupclass' => 'lookuprvoter',
                'action' => 'lookuprandom',
                'label' => 'Registered Voter',
                'class' => 'csrvoter',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),

            'precintno' => array(
                'name' => 'precintno',
                'type' => 'input',
                'label' => 'Precint No.#',
                'class' => 'csprecintno',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 10
            ),
            'names' => array(
                'name' => 'names',
                'type' => 'input',
                'label' => 'Person to Contact',
                'class' => 'csnames',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 80
            ),
            'acquireddate' => array(
                'name' => 'acquireddate',
                'type' => 'date',
                'label' => 'Register Date',
                'class' => 'csacquireddate',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isallowliquor' => array(
                'name' => 'isallowliquor',
                'type' => 'checkbox',
                'label' => 'Allow Liquor',
                'class' => 'csisallowliquor',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'clientpref' => array(
                'name' => 'clientpref',
                'type' => 'input',
                'label' => 'MP Ref. #',
                'class' => 'csstreet',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'bstype' => array(
                'name' => 'bstype',
                'type' => 'input',
                'label' => 'Business Type',
                'class' => 'csbstype',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'ownername' => array(
                'name' => 'ownername',
                'type' => 'input',
                'label' => 'Owner Name',
                'class' => 'csownername',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'ownertype' => array(
                'name' => 'ownertype',
                'type' => 'input',
                'label' => 'Owner Type',
                'class' => 'csownertype',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'owneraddr' => array(
                'name' => 'owneraddr',
                'type' => 'input',
                'label' => 'Owner Address',
                'class' => 'csowneraddr',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'recommendapp' => array(
                'name' => 'recommendapp',
                'type' => 'lookup',
                'label' => 'Recommending Approval',
                'class' => 'csrecommendapp',
                'lookupclass' => 'recoapproval',
                'action' => 'lookupemployee',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'approvedby' => array(
                'name' => 'approvedby',
                'type' => 'lookup',
                'label' => 'Approved/Disapproved (General Manager)',
                'class' => 'csapprovedby',
                'lookupclass' => 'approvedby',
                'action' => 'lookupemployee',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'hqapprovedby' => array(
                'name' => 'backlisting',
                'type' => 'actionbtn',
                'label' => 'For Approval',
                'class' => 'btnhqapprovedby',
                'lookupclass' => 'stockstatusposted',
                'icon' => 'check',
                'access' => 'save',
                'action' => 'hqapprovedby',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false
            ),
            'iswireitem' => array(
                'name' => 'iswireitem',
                'type' => 'checkbox',
                'label' => 'Wire Item',
                'class' => 'csiswireitem',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isreversewireitem' => array(
                'name' => 'isreversewireitem',
                'type' => 'checkbox',
                'label' => 'Reverse Wire Item',
                'class' => 'csisreversewireitem',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'startwire' => array(
                'name' => 'startwire',
                'type' => 'input',
                'label' => 'Start Wire',
                'class' => 'csstartwire',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'endwire' => array(
                'name' => 'endwire',
                'type' => 'input',
                'label' => 'End Wire',
                'class' => 'csendwire',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'namt' => array(
                'name' => 'namt',
                'type' => 'input',
                'label' => 'Net R (R)',
                'class' => 'csnamt',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'namt2' => array(
                'name' => 'namt2',
                'type' => 'input',
                'label' => 'Net W (W)',
                'class' => 'csnamt',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'nfamt' => array(
                'name' => 'nfamt',
                'type' => 'input',
                'label' => 'Net A (A)',
                'class' => 'csnamt',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'namt4' => array(
                'name' => 'namt4',
                'type' => 'input',
                'label' => 'Net B (B)',
                'class' => 'csnamt',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'namt5' => array(
                'name' => 'namt5',
                'type' => 'input',
                'label' => 'Net C (C)',
                'class' => 'csnamt',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'namt6' => array(
                'name' => 'namt6',
                'type' => 'input',
                'label' => 'Net D (D)',
                'class' => 'csnamt',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'namt7' => array(
                'name' => 'namt7',
                'type' => 'input',
                'label' => 'Net E (E)',
                'class' => 'csnamt',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'fsalarytype' => array(
                'name' => 'fsalarytype',
                'type' => 'input',
                'label' => 'From Salary Type',
                'class' => 'csfsalarytype sbccsreadonly',
                'action' => 'lookupsalarytype',
                'readonly' => true,
                'style' => $this->style
            ),
            'salarytype' => array(
                'name' => 'salarytype',
                'type' => 'lookup',
                'label' => 'Salary Type',
                'class' => 'cssalarytype sbccsreadonly',
                'lookupclass' => 'salarytype',
                'action' => 'lookupsalarytype',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),
            'chkcopy' => array(
                'name' => 'chkcopy',
                'type' => 'checkbox',
                'label' => '',
                'class' => 'cschkcopy',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'fhsperiod' => array(
                'name' => 'fhsperiod',
                'type' => 'input',
                'label' => 'Period',
                'class' => 'csperiod sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'hsperiod' => array(
                'name' => 'hsperiod',
                'type' => 'lookup',
                'label' => 'Period',
                'class' => 'csperiod sbccsreadonly',
                'lookupclass' => 'hsperiod',
                'action' => 'lookuphsperiod',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),
            'fmons' => array(
                'name' => 'fmons',
                'type' => 'input',
                'label' => 'MA Factor1',
                'class' => 'csmonth',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),

            'rrfactor' => array(
                'name' => 'rrfactor',
                'type' => 'input',
                'label' => 'Factor',
                'class' => 'csrrfactor',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'fannum' => array(
                'name' => 'fannum',
                'type' => 'input',
                'label' => 'MA Factor2',
                'class' => 'csmonth',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'frate' => array(
                'name' => 'frate',
                'type' => 'input',
                'label' => 'MA Factor3',
                'class' => 'csmonth',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'radiopaytype' => array(
                'name' => 'radiopaytype',
                'label' => 'Apply payment as',
                'type' => 'radio',
                'readonly' => true,
                'class' => 'csradiopaytype sbccsreadonly',
                'options' => array(
                    ['label' => 'MA', 'value' => 0, 'color' => 'pink'],
                    ['label' => 'Advance Payment', 'value' => 1, 'color' => 'pink'],
                    ['label' => 'Balloon Payment', 'value' => 2, 'color' => 'pink'],
                    ['label' => 'Others', 'value' => 3, 'color' => 'pink']
                )
            ),
            'locname' => array(
                'name' => 'locname',
                'type' => 'lookup',
                'label' => 'Location',
                'class' => 'cslocname sbccsreadonly',
                'lookupclass' => 'lookuplocname',
                'action' => 'lookuplocname',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'totalterms' => array(
                'name' => 'totalterms',
                'type' => 'lookup',
                'label' => 'Total Terms',
                'class' => 'cstotalterms sbccsreadonly',
                'lookupclass' => 'lookuptotalterms',
                'action' => 'lookuptotalterms',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'disapprovedby2' => array(
                'name' => 'disapprovedby2',
                'type' => 'input',
                'label' => 'Disapproved by:',
                'class' => 'csdisapprovedby2',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'disapprovedby1' => array(
                'name' => 'disapprovedby1',
                'type' => 'input',
                'label' => 'Disapproved by:',
                'class' => 'csdisapprovedby1',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'approvedby1' => array(
                'name' => 'approvedby1',
                'type' => 'input',
                'label' => 'Approved by:',
                'class' => 'csapprovedby1',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'approvedby2' => array(
                'name' => 'approvedby2',
                'type' => 'input',
                'label' => 'Approved by:',
                'class' => 'csapprovedby2',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'tct' => array(
                'name' => 'tct',
                'type' => 'input',
                'label' => 'TCT#',
                'class' => 'cstct',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),

            'entryfee' => array(
                'name' => 'entryfee',
                'type' => 'input',
                'label' => 'Entry Fee',
                'class' => 'csentryfee',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'lrf' => array(
                'name' => 'lrf',
                'type' => 'input',
                'label' => 'Legal Research Fee',
                'class' => 'cslrf',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'itfee' => array(
                'name' => 'itfee',
                'type' => 'input',
                'label' => 'IT Fee/ Computer Fee',
                'class' => 'csitfee',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'regfee' => array(
                'name' => 'regfee',
                'type' => 'input',
                'label' => 'Registration Fee',
                'class' => 'csregfee',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'nf2' => array(
                'name' => 'nf2',
                'type' => 'input',
                'label' => 'Notarial Fee: Deed of Undertaking',
                'class' => 'csnf2',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'nf3' => array(
                'name' => 'nf3',
                'type' => 'input',
                'label' => 'Notarial Fee: Deed of Assignment',
                'class' => 'csnf3',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'ofee' => array(
                'name' => 'ofee',
                'type' => 'input',
                'label' => 'Other Fees',
                'class' => 'csofee',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'projectid' => array(
                'name' => 'projectid',
                'type' => 'input',
                'label' => 'Project ID',
                'class' => 'csprojectid',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'pcfno' => array(
                'name' => 'pcfno',
                'type' => 'input',
                'label' => 'PCF No.',
                'class' => 'cspcfno',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'dtcno' => array(
                'name' => 'dtcno',
                'type' => 'input',
                'label' => 'DTC No.',
                'class' => 'csdtcno',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'aftistock' => array(
                'name' => 'aftistock',
                'type' => 'checkbox',
                'label' => 'AFTI Stock',
                'class' => 'csaftistock',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'fullcomm' => array(
                'name' => 'fullcomm',
                'type' => 'lookup',
                'label' => 'Commission Option',
                'class' => 'csfullcomm sbccsreadonly',
                'lookupclass' => 'lookupfullcomm',
                'action' => 'lookupfullcomm',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'oandaphpusd' => array(
                'name' => 'oandaphpusd',
                'type' => 'input',
                'label' => 'OANDA PHP-USD',
                'class' => 'csoandaphpusd sbccsreadonly',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'oandausdphp' => array(
                'name' => 'oandausdphp',
                'type' => 'input',
                'label' => 'OANDA USD-PHP',
                'class' => 'csoandausdphp sbccsreadonly',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'osphpusd' => array(
                'name' => 'osphpusd',
                'type' => 'input',
                'label' => 'OS PHP-USD',
                'class' => 'csosphpusd sbccsreadonly',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'hmoname' => array(
                'name' => 'hmoname',
                'type' => 'input',
                'label' => 'HMO Name',
                'class' => 'cshmoname',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'hmoaccno' => array(
                'name' => 'hmoaccno',
                'type' => 'input',
                'label' => 'HMO Account #',
                'class' => 'cshmoaccno',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),

            'schoollevel' => array(
                'name' => 'schoollevel',
                'type' => 'input',
                'label' => 'School Level',
                'class' => 'csschoollevel',
                'readonly' => true,
                'style' => $this->style,
                'required' => true,
                'maxlength' => 100
            ),

            'rolename2' => array(
                'name' => 'rolename2',
                'type' => 'input',
                'label' => 'Role',
                'class' => 'csrolename2 sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'divname2' => array(
                'name' => 'divname2',
                'type' => 'input',
                'label' => 'Company',
                'class' => 'csdivname2 sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'deptname2' => array(
                'name' => 'deptname2',
                'type' => 'input',
                'label' => 'Department',
                'class' => 'csdeptname2 sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'sectionname2' => array(
                'name' => 'sectionname2',
                'type' => 'input',
                'label' => 'Section',
                'class' => 'cssectionname2 sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'basicrate'  => array(
                'name'        => 'basicrate',
                'type'        => 'input',
                'label'       => 'Basic Salary',
                'class'       => 'csbasicrate sbccsreadonly',
                'readonly'    => true,
                'style'       => $this->style,
                'required'    => false
            ),

            'salarytpe'  => array(
                'name'        => 'salarytpe',
                'type'        => 'input',
                'label'       => 'Salary Type',
                'class'       => 'cssalarytpe sbccsreadonly',
                'readonly'    => false,
                'style'       => $this->style,
                'required'    => false
            ),
            'trackingtype' => array(
                'name' => 'trackingtype',
                'type' => 'lookup',
                'label' => 'Tracking For:',
                'class' => 'cstrackingtype sbccsreadonly',
                'lookupclass' => 'lookuptrackingtype',
                'action' => 'lookuptrackingtype',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'acnoname5' => array(
                'name' => 'acnoname5',
                'type' => 'lookup',
                'label' => 'Bank Account',
                'class' => 'cscontra sbccsreadonly',
                'lookupclass' => 'banklookup',
                'action' => 'lookupcoa',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'annotationfee' => array(
                'name' => 'annotationfee',
                'type' => 'input',
                'label' => 'Annotation of Special Power of Attorney',
                'class' => 'csannotationfee',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'docstamp1' => array(
                'name' => 'docstamp1',
                'type' => 'input',
                'label' => 'Documentary Stamps',
                'class' => 'csdocstamp1',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'articles' => array(
                'name' => 'articles',
                'type' => 'input',
                'label' => 'Articles of Inc. & By Laws',
                'class' => 'csarticles',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'annotationexp' => array(
                'name' => 'annotationexp',
                'type' => 'input',
                'label' => 'Annotation expenses',
                'class' => 'csannotationexp',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'otransfer' => array(
                'name' => 'otransfer',
                'type' => 'input',
                'label' => 'Transfer of ownership',
                'class' => 'csotransfer',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'rpt' => array(
                'name' => 'rpt',
                'type' => 'input',
                'label' => 'Real Property Tax',
                'class' => 'csrpt',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'handling' => array(
                'name' => 'handling',
                'type' => 'input',
                'label' => 'Handling Fee',
                'class' => 'cshandling',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'appraisal' => array(
                'name' => 'appraisal',
                'type' => 'input',
                'label' => 'Appraisal Fee',
                'class' => 'csappraisal',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'filing' => array(
                'name' => 'filing',
                'type' => 'input',
                'label' => 'Processing Fee/Filing Fee',
                'class' => 'csfiling',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'referral' => array(
                'name' => 'referral',
                'type' => 'input',
                'label' => 'Referral Fee',
                'class' => 'csreferral',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'cancellation4' => array(
                'name' => 'cancellation4',
                'type' => 'input',
                'label' => 'Cancellation : Sec 4 Rule 74',
                'class' => 'cscancellation4',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'cancellation7' => array(
                'name' => 'cancellation7',
                'type' => 'input',
                'label' => 'Cancellation : Sec 7 RA 26',
                'class' => 'cscancellation7',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'annotationoc1' => array(
                'name' => 'annotationoc1',
                'type' => 'input',
                'label' => 'Annotation of correct tech description',
                'class' => 'csannotationoc1',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'annotationoc2' => array(
                'name' => 'annotationoc2',
                'type' => 'input',
                'label' => 'Annotation of Aff of one and the same person',
                'class' => 'csannotationoc2',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'cancellationu' => array(
                'name' => 'cancellationu',
                'type' => 'input',
                'label' => 'Cancellation: ULAMA',
                'class' => 'cscancellationu',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'aveleadtime' => array(
                'name' => 'aveleadtime',
                'type' => 'input',
                'label' => 'Average Lead Time',
                'class' => 'csaveleadtime',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'maxleadtime' => array(
                'name' => 'maxleadtime',
                'type' => 'input',
                'label' => 'Maximum Lead Time',
                'class' => 'csmaxleadtime',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'mealamt' => array(
                'name' => 'mealamt',
                'type' => 'input',
                'label' => '',
                'class' => 'csmealamt',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),

            'mealnum' => array(
                'name' => 'mealnum',
                'type' => 'input',
                'label' => '',
                'class' => 'csmealnum',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'texpense' => array(
                'name' => 'texpense',
                'type' => 'input',
                'label' => 'PHP',
                'class' => 'cstexpense sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'expensetype' => array(
                'name' => 'expensetype',
                'type' => 'lookup',
                'label' => 'Select Expenses',
                'class' => 'csexpensetype sbccsreadonly',
                'lookupclass' => 'lookupexpensetype',
                'action' => 'lookupexpensetype',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),
            'lodgeexp' => array(
                'name' => 'lodgeexp',
                'type' => 'input',
                'label' => 'PHP',
                'class' => 'cslodgeexp',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'lengthstay' => array(
                'name' => 'lengthstay',
                'type' => 'input',
                'label' => 'Night Length of Stay',
                'class' => 'cslengthstay',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'misc' => array(
                'name' => 'misc',
                'type' => 'input',
                'label' => 'PHP',
                'class' => 'csmisc',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'gas' => array(
                'name' => 'gas',
                'type' => 'input',
                'label' => 'Liters',
                'class' => 'csgas sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'resignedtype' => array(
                'name' => 'resignedtype',
                'type' => 'lookup',
                'label' => 'Resigned Status',
                'class' => 'csresignedtype sbccsreadonly',
                'lookupclass' => 'lookupresignedEP',
                'action' => 'lookupresigned',
                'readonly' => true,
                'style' => $this->style,
                'required' => true
            ),
            'isreported' => array(
                'name' => 'isreported',
                'type' => 'checkbox',
                'label' => 'Reported',
                'class' => 'csisreported',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'ied' => array(
                'name' => 'ied',
                'type' => 'input',
                'label' => 'IED',
                'class' => 'csied',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'bankcharges' => array(
                'name' => 'bankcharges',
                'type' => 'input',
                'label' => 'Bank Charges',
                'class' => 'csbankcharges',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'brokerfee' => array(
                'name' => 'brokerfee',
                'type' => 'input',
                'label' => 'Broker Fee',
                'class' => 'csbrokerfee',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'arrastre' => array(
                'name' => 'arrastre',
                'type' => 'input',
                'label' => 'Arrastre',
                'class' => 'csarrastre',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'lasttrans' => array(
                'name' => 'lasttrans',
                'type' => 'input',
                'label' => 'Last Purchase Date',
                'class' => 'cslasttrans sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'updatenotes' => array(
                'name' => 'updatenotes',
                'access' => 'view',
                'type' => 'actionbtn',
                'label' => 'Update Notes',
                'class' => 'csupdatenotes',
                'action' => 'customform',
                'lookupclass' => 'updatenotes',
                'readonly' => false,
                'style' => 'height:100%',
                'required' => false
            ),
            'bpo' => array(
                'name' => 'bpo',
                'type' => 'input',
                'label' => 'BPO',
                'class' => 'csbpo',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'ctnsno' => array(
                'name' => 'ctnsno',
                'type' => 'input',
                'label' => 'No. of CTNS',
                'class' => 'csctnsno',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'dpricegroup' => array(
                'name' => 'dpricegroup',
                'type' => 'lookup',
                'label' => 'Price Group',
                'class' => 'cscontra sbccsreadonly',
                'lookupclass' => 'lookuppricegroup',
                'action' => 'lookuppricegroup',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'radiotypeofreportformat' => array(
                'name' => 'typeofformat',
                'label' => 'Formats',
                'type' => 'radio',
                'options' => array(
                    ['label' => 'Sales Invoice Summary', 'value' => 'sis', 'color' => 'orange'],
                    ['label' => 'Government for Tin', 'value' => 'gft', 'color' => 'orange']
                )
            ),
            'loaddate' => array(
                'name' => 'loaddate',
                'type' => 'date',
                'label' => 'Load Date',
                'class' => 'csloaddate',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'isnoentry' => array(
                'name' => 'isnoentry',
                'type' => 'checkbox',
                'label' => 'Without Accounting Entry',
                'class' => 'csisnoentry',
                'readonly' => false,
                'style' => $this->style,
                'required' => true
            ),
            'requestby' => array(
                'name' => 'requestby',
                'type' => 'lookup',
                'label' => 'Request By',
                'class' => 'csuserid sbccsreadonly',
                'lookupclass' => 'lookupusers',
                'action' => 'lookupusers',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'task' => array(
                'name' => 'task',
                'type' => 'wysiwyg',
                'label' => 'Task Info',
                'class' => 'cstask',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'is13th' => array(
                'name' => 'is13th',
                'type' => 'checkbox',
                'label' => '13 Month',
                'class' => 'csistax',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            //added nov 27
            'cashier' => array(
                'name' => 'cashier',
                'type' => 'lookup',
                'label' => 'Cashier',
                // 'labeldata' => 'cashiername',
                'class' => 'cscashier sbccsreadonly',
                'lookupclass' => 'lookupcashier',
                'action' => 'lookupcashier',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            //added nov 27
            'lookup_cashier' => array(
                'name' => 'lookup_cashier',
                'type' => 'lookup',
                'label' => 'Cashier',
                'class' => 'cswh sbccsreadonly',
                'lookupclass' => 'lookupcashier',
                'action' => 'lookupcashier',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            // added nov 28
            'customer_name' => array(
                'name' => 'customer_name',
                'type' => 'lookup',
                'label' => 'Customer',
                'labeldata' => 'customer~clientname',
                'class' => 'csclient sbccsreadonly',
                'lookupclass' => 'lookupcustomer',
                'action' => 'lookupcustomer',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'tpayment' => array(
                'name' => 'tpayment',
                'type' => 'lookup',
                'label' => 'Payment',
                'class' => 'csclient sbccsreadonly',
                'lookupclass' => 'lookup_pos_paymentmode',
                'action' => 'lookup_pos_paymentmode',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'witnessname' => array(
                'name' => 'witnessname',
                'type' => 'lookup',
                'label' => 'Witness Name',
                'class' => 'cswitnessname sbccsreadonly',
                'lookupclass' => 'witnessnamelookup',
                'action' => 'lookupemployee',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'witnessname2' => array(
                'name' => 'witnessname2',
                'type' => 'lookup',
                'label' => 'Witness Name',
                'class' => 'cswitnessname2 sbccsreadonly',
                'lookupclass' => 'witnessnamelookup2',
                'action' => 'lookupemployee',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            // nov 28 end

            'islatefilling' => array(
                'name' => 'islatefilling',
                'type' => 'checkbox',
                'label' => 'Late Filling',
                'class' => 'csislatefilling',
                'readonly' => false,
                'style' => $this->style,
                'required' => true
            ),
            // pos 
            'pospayment' => array(
                'name' => 'pospayment',
                'type' => 'lookup',
                'label' => 'Payment',
                'class' => 'csclient sbccsreadonly',
                'lookupclass' => 'pos_paymentmethod_lookup',
                'action' => 'pos_paymentmethod_lookup',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'isfg' => array(
                'name' => 'isfg',
                'type' => 'checkbox',
                'label' => 'Finish Good ',
                'class' => 'csisfg',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'hqdocno' => array(
                'name' => 'hqdocno',
                'type' => 'lookup',
                'label' => 'Docno',
                'class' => 'cshqdocno sbccsreadonly',
                'lookupclass' => 'lookuphqdocno',
                'action' => 'lookuphqdocno',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'addedparams' => ['jobcode']
            ),

            'pos_station' => array(
                'name' => 'pos_station',
                'type' => 'lookup',
                'label' => 'Station',
                'class' => 'csstation_rep sbccsreadonly',
                'lookupclass' => 'pos_station_lookup',
                'action' => 'pos_station_lookup',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'qtno' => array(
                'name' => 'qtno',
                'type' => 'lookup',
                'label' => 'Qoutation#',
                'class' => 'cslookupqt sbccsreadonly',
                'lookupclass' => 'lookupqt',
                'action' => 'lookupqt',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            'jono' => array(
                'name' => 'jono',
                'type' => 'cinput',
                'label' => 'JO#',
                'class' => 'csjono',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 30
            ),
            // added 2026-02-23
            'usernamee' => array(
                'name' => 'usernamee',
                'type' => 'lookup',
                'label' => 'User',
                'lookupclass' => 'lookupuserss',
                'action' => 'lookupuserss',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

            //added 2026-03-02
            'dystatus' => array(
                'name' => 'dystatus',
                'type' => 'lookup',
                'label' => 'Status',
                'lookupclass' => 'lookupstatus',
                'action' => 'lookupstatus',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'approvedinfodetails' => array(
                'name' => 'backlisting',
                'type' => 'actionbtn',
                'label' => 'Update Approved Info Details',
                'class' => 'btnapprovedinfodetails',
                'lookupclass' => 'approvedinfodetails',
                'icon' => 'edit',
                'access' => 'save',
                'action' => 'customformdialog',
                'readonly' => true,
                'style' => 'width:100%',
                'required' => false
            ),

            'projid' => array(
                'name' => 'projid',
                'type' => 'input',
                'label' => 'Project ID',
                'class' => 'csprojid',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 50
            ),


            'complexity' => array(
                'name' => 'complexity',
                'type' => 'input',
                'label' => 'Task Complexity',
                'field' => 'complexity',
                'align' => 'text-left',
                'style' => $this->style,
                'readonly' => true
            ),
            'isfee' => array(
                'name' => 'isfee',
                'type' => 'checkbox',
                'label' => 'With Fee',
                'class' => 'csisfee',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'infracode' => array(
                'name' => 'infracode',
                'type' => 'lookup',
                'label' => 'Infra - Code',
                'lookupclass' => 'lookupinfracode',
                'action' => 'lookupinfracode',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'infratype' => array(
                'name' => 'infratype',
                'type' => 'lookup',
                'label' => 'Infra Type',
                'lookupclass' => 'lookupinfratype',
                'action' => 'lookupinfratype',
                'readonly' => false,
                'style' => $this->style,
                'required' => false
            ),
            'regdate' => array(
                'name' => 'regdate',
                'type' => 'date',
                'label' => 'Register Date',
                'class' => 'csregdate',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),


            'totalreturn' => array(
                'name' => 'totalreturn',
                'type' => 'cinput',
                'label' => 'Total Return',
                'class' => 'cstotalreturn sbccsreadonly',
                'readonly' => true,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            // BMS
            'trutype' => array(
                'name' => 'trutype',
                'type' => 'lookup',
                'label' => 'Tru Type',
                'class' => 'cstrutype sbccsreadonly',
                'lookupclass' => 'lookuptrutype',
                'action' => 'lookuptrutype',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'bonafide' => array(
                'name' => 'bonafide',
                'type' => 'lookup',
                'label' => 'Bonafide',
                'class' => 'csbonafide sbccsreadonly',
                'lookupclass' => 'lookupbonafide',
                'action' => 'lookupbonafide',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'sidecarno' => array(
                'name' => 'sidecarno',
                'type' => 'input',
                'label' => 'Side Car No.',
                'class' => 'cssidecarno',
                'align' => 'text-left',
                'style' => $this->style,
                'readonly' => true,
                'required' => false
            ),
            'make' => array(
                'name' => 'make',
                'type' => 'input',
                'label' => 'Make',
                'align' => 'text-left',
                'class' => 'csmake',
                'style' => $this->style,
                'readonly' => true,
                'required' => false
            ),
            'motorno' => array(
                'name' => 'motorno',
                'type' => 'input',
                'label' => 'Motor No.',
                'align' => 'text-left',
                'class' => 'csmotorno',
                'style' => $this->style,
                'readonly' => true,
                'required' => false
            ),
            'color' => array(
                'name' => 'color',
                'type' => 'input',
                'label' => 'Color Code',
                'align' => 'text-left',
                'class' => 'cscolor',
                'style' => $this->style,
                'readonly' => true,
                'required' => false
            ),
            'counter' => array(
                'name' => 'counter',
                'type' => 'input',
                'label' => 'Counter',
                'class' => 'counter',
                'readonly' => false,
                'style' => $this->style,
                'required' => false,
                'maxlength' => 20
            ),
            'sentence1' => array(
                'name' => 'sentence1',
                'type' => 'ctextarea',
                'label' => 'SENTENCE 1',
                'class' => 'cssentence1',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'sentence2' => array(
                'name' => 'sentence2',
                'type' => 'ctextarea',
                'label' => 'SENTENCE 2',
                'class' => 'cssentence2',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'sentence3' => array(
                'name' => 'sentence3',
                'type' => 'ctextarea',
                'label' => 'SENTENCE 3',
                'class' => 'cssentence3',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'bullet1' => array(
                'name' => 'bullet1',
                'type' => 'ctextarea',
                'label' => 'BULLET 1',
                'class' => 'csbullet1',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'bullet2' => array(
                'name' => 'bullet2',
                'type' => 'ctextarea',
                'label' => 'BULLET 2',
                'class' => 'csbullet2',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'bullet3' => array(
                'name' => 'bullet3',
                'type' => 'ctextarea',
                'label' => 'BULLET 3',
                'class' => 'csbullet3',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'bullet4' => array(
                'name' => 'bullet4',
                'type' => 'ctextarea',
                'label' => 'BULLET 4',
                'class' => 'csbullet4',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'bullet5' => array(
                'name' => 'bullet5',
                'type' => 'ctextarea',
                'label' => 'BULLET 5',
                'class' => 'csbullet5',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'bullet6' => array(
                'name' => 'bullet6',
                'type' => 'ctextarea',
                'label' => 'BULLET 6',
                'class' => 'csbullet6',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),
            'bullet7' => array(
                'name' => 'bullet7',
                'type' => 'ctextarea',
                'label' => 'BULLET 7',
                'class' => 'csbullet7',
                'readonly' => true,
                'style' => $this->style,
                'required' => false
            ),

        );
    }




    public function getFields($fieldnames)
    {
        $txtfield = $this->othersClass->array_only($this->fields, $fieldnames);
        return $txtfield;
    }

    public function create($fieldnames)
    {
        $this->txtarray();
        $txtplot = [];
        $txtfield = [];
        $labeldata = [];
        $i = 0;
        $b = 0;
        $c = 0;
        $field = [];
        $final = [];
        $this->coreFunctions = new coreFunctions;
        foreach ($fieldnames as $key => $value) {
            if (is_array($value)) {
                $tmp = [];
                $c = 0;
                foreach ($value as $key2 => $value2) {
                    $field = $this->getFields($value2);
                    if (!empty($field)) {
                        if (isset($field[$value2]['required'])) $field[$value2]['error'] = false;
                        $field[$value2]['error'] = false;
                        $final[$value2] = $field[$value2];
                        $tmp[$c] = $value2;
                        $txtfield[$i] = $value2;
                        $i++;
                        $c++;
                    }
                }
                if ($i != $b) {
                    $txtplot[$b] = $tmp;
                    $b++;
                }
            } else {
                $field = $this->getFields($value);
                if (!empty($field)) {
                    if (isset($field[$value]['required'])) $field[$value]['error'] = false;
                    $final[$value] = $field[$value];
                    $txtplot[$b] = $value;
                    $b++;
                    $txtfield[$i] = $value;
                    $i++;
                }
            }
        }
        // $final2 = $this->getFields($txtfield);
        foreach ($final as $key => $value) {
            if (isset($final[$key]['labeldata'])) {
                $labeldata[$key] = $final[$key]['labeldata'];
            }
            if ($final[$key]['type'] == 'mqselect') {
                $data = $this->coreFunctions->opentable($final[$key]['sql']);
                $new = $this->objtoarray($data);
                $final[$key]['options'] = $new;
            }
        }
        $final['plot'] = $txtplot;
        if (!empty($labeldata)) {
            $final['labeldata'] = $labeldata;
        }
        return $final;
    } // end function create

    private function objtoarray($data)
    {
        $new = [];
        foreach ($data as $key => $value) {
            array_push($new, $value->display);
        }
        return $new;
    }
}//end class
