<?php

namespace App\Http\Classes\builder;

use DB;
use Exception;
use Throwable;

class gridcolumnClass
{

       private $columns = [];

       public function getcolumn()
       {

              // setup of grid column
              $this->columns = array(
                     'action' => array(
                            'name' => 'action',
                            'type' => 'action',
                            'label' => 'Action',
                            'align' => 'center',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => true,
                            'btns' => ''
                     ),
                     'serial' => array(
                            'name' => 'serial',
                            'type' => 'input',
                            'label' => 'Serial',
                            'field' => 'serial',
                            'align' => 'text-left',
                            'style' => 'min-width:80px;',
                            'readonly' => false
                     ),
                     'barcode' => array(
                            'name' => 'barcode',
                            'type' => 'input',
                            'label' => 'Barcode',
                            'field' => 'barcode',
                            'align' => 'text-left',
                            'style' => 'min-width:80px;',
                            'readonly' => false
                     ),
                     'othcode' => array(
                            'name' => 'othcode',
                            'type' => 'input',
                            'label' => 'Barcode Name',
                            'field' => 'othcode',
                            'align' => 'text-left',
                            'style' => 'min-width:80px;',
                            'readonly' => false
                     ),
                     'oraclecode' => array(
                            'name' => 'oraclecode',
                            'type' => 'input',
                            'label' => 'Oracle Code',
                            'field' => 'oraclecode',
                            'align' => 'text-left',
                            'style' => 'min-width:80px;',
                            'readonly' => false
                     ),
                     'itemname' => array(
                            'name' => 'itemname',
                            'type' => 'hidden',
                            'label' => '',
                            'field' => 'itemname',
                            'align' => 'text-left',
                            'style' => 'min-width:1px;',
                            'readonly' => true
                     ),
                     'shortname' => array(
                            'name' => 'shortname',
                            'type' => 'input',
                            'label' => 'Shortname',
                            'field' => 'shortname',
                            'align' => 'text-left',
                            'style' => 'width:50px;whiteSpace: normal;min-width:50px;',
                            'readonly' => false
                     ),
                     'accountno' => array(
                            'name' => 'accountno',
                            'type' => 'input',
                            'label' => 'Account No.',
                            'field' => 'accountno',
                            'align' => 'text-left',
                            'style' => 'width:50px;whiteSpace: normal;min-width:50px;',
                            'readonly' => false
                     ),
                     'billingclerk' => array(
                            'name' => 'billingclerk',
                            'type' => 'input',
                            'label' => 'Billing Clerk',
                            'field' => 'billingclerk',
                            'align' => 'text-left',
                            'style' => 'width:50px;whiteSpace: normal;min-width:50px;',
                            'readonly' => false
                     ),
                     'item' => array(
                            'name' => 'item',
                            'type' => 'input',
                            'label' => 'Item',
                            'field' => 'item',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => true
                     ),
                     'itemdesc' => array(
                            'name' => 'itemdesc',
                            'type' => 'label',
                            'label' => 'Item Name',
                            'field' => 'itemdesc',
                            'align' => 'text-left',
                            'style' => 'width:200px;whiteSpace: normal;min-width:200px;',
                            'readonly' => true
                     ),
                     'itemdesc2' => array(
                            'name' => 'itemdesc2',
                            'type' => 'label',
                            'label' => 'Item Name (PR)',
                            'field' => 'itemdesc2',
                            'align' => 'text-left',
                            'style' => 'width:200px;whiteSpace: normal;min-width:200px;',
                            'readonly' => true
                     ),
                     'specs' => array(
                            'name' => 'specs',
                            'type' => 'label',
                            'label' => 'Specifications',
                            'field' => 'specs',
                            'align' => 'text-left',
                            'style' => 'width:200px;whiteSpace: normal;min-width:200px;',
                            'readonly' => true
                     ),
                     'specs2' => array(
                            'name' => 'specs2',
                            'type' => 'label',
                            'label' => 'Specifications (PR)',
                            'field' => 'specs2',
                            'align' => 'text-left',
                            'style' => 'width:200px;whiteSpace: normal;min-width:200px;',
                            'readonly' => true
                     ),
                     'requestorname' => array(
                            'name' => 'requestorname',
                            'type' => 'label',
                            'label' => 'Requestor',
                            'field' => 'requestorname',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => true
                     ),
                     'amt' => array(
                            'name' => 'amt',
                            'type' => 'input',
                            'label' => 'Amount',
                            'field' => 'amt',
                            'align' => 'text-right',
                            'style' => 'text-align:right;width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'baseamt' => array(
                            'name' => 'baseamt',
                            'type' => 'input',
                            'label' => 'Base Amount',
                            'field' => 'baseamt',
                            'align' => 'text-right',
                            'style' => 'text-align:right;width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'apamt' => array(
                            'name' => 'apamt',
                            'type' => 'input',
                            'label' => 'Approved Amount',
                            'field' => 'apamt',
                            'align' => 'text-right',
                            'style' => 'text-align:right;width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'srp' => array(
                            'name' => 'srp',
                            'type' => 'input',
                            'label' => 'Selling',
                            'field' => 'srp',
                            'align' => 'text-right',
                            'style' => 'text-align:right;width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'totalsrp' => array(
                            'name' => 'totalsrp',
                            'type' => 'input',
                            'label' => 'Total Selling',
                            'field' => 'totalsrp',
                            'align' => 'text-right',
                            'style' => 'text-align:right;width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => true
                     ),
                     'tp' => array(
                            'name' => 'tp',
                            'type' => 'input',
                            'label' => 'TP',
                            'field' => 'tp',
                            'align' => 'text-right',
                            'style' => 'text-align:right;width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'totaltp' => array(
                            'name' => 'totaltp',
                            'type' => 'input',
                            'label' => 'Total TP',
                            'field' => 'totaltp',
                            'align' => 'text-right',
                            'style' => 'text-align:right;width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => true
                     ),
                     'foramt' => array(
                            'name' => 'foramt',
                            'type' => 'input',
                            'label' => 'Floor Price',
                            'field' => 'foramt',
                            'align' => 'text-right',
                            'style' => 'text-align:right;width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'expensename' => array(
                            'name' => 'expensename',
                            'type' => 'input',
                            'label' => 'Expense',
                            'field' => 'expensename',
                            'align' => 'text-left',
                            'style' => 'text-align:left;width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'budget' => array(
                            'name' => 'budget',
                            'type' => 'input',
                            'label' => 'Budget',
                            'field' => 'budget',
                            'align' => 'text-right',
                            'style' => 'text-align:right;width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'actual' => array(
                            'name' => 'actual',
                            'type' => 'input',
                            'label' => 'Actual',
                            'field' => 'actual',
                            'align' => 'text-right',
                            'style' => 'text-align:right;width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),

                     'sgdrate' => array(
                            'name' => 'sgdrate',
                            'type' => 'input',
                            'label' => 'Amount',
                            'field' => 'sgdrate',
                            'align' => 'text-right',
                            'style' => 'text-align:right;width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'r' => array(
                            'name' => 'r',
                            'type' => 'input',
                            'label' => 'Price R',
                            'field' => 'r',
                            'align' => 'text-right',
                            'style' => 'width:100px;whiteSpace: normal;max-width:100px;',
                            'readonly' => false
                     ),
                     'w' => array(
                            'name' => 'w',
                            'type' => 'input',
                            'label' => 'Price W',
                            'field' => 'w',
                            'align' => 'text-right',
                            'style' => 'width:100px;whiteSpace: normal;max-width:100px;',
                            'readonly' => false
                     ),
                     'a' => array(
                            'name' => 'a',
                            'type' => 'input',
                            'label' => 'Price A',
                            'field' => 'a',
                            'align' => 'text-right',
                            'style' => 'width:100px;whiteSpace: normal;max-width:100px;',
                            'readonly' => false
                     ),
                     'b' => array(
                            'name' => 'b',
                            'type' => 'input',
                            'label' => 'Price B',
                            'field' => 'b',
                            'align' => 'text-right',
                            'style' => 'width:100px;whiteSpace: normal;max-width:100px;',
                            'readonly' => false
                     ),
                     'c' => array(
                            'name' => 'c',
                            'type' => 'input',
                            'label' => 'Price C',
                            'field' => 'c',
                            'align' => 'text-right',
                            'style' => 'width:100px;whiteSpace: normal;max-width:100px;',
                            'readonly' => false
                     ),
                     'd' => array(
                            'name' => 'd',
                            'type' => 'input',
                            'label' => 'Price D',
                            'field' => 'd',
                            'align' => 'text-right',
                            'style' => 'width:100px;whiteSpace: normal;max-width:100px;',
                            'readonly' => false
                     ),
                     'e' => array(
                            'name' => 'e',
                            'type' => 'input',
                            'label' => 'Price E',
                            'field' => 'e',
                            'align' => 'text-right',
                            'style' => 'width:100px;whiteSpace: normal;max-width:100px;',
                            'readonly' => false
                     ),
                     'f' => array(
                            'name' => 'f',
                            'type' => 'input',
                            'label' => 'Price F',
                            'field' => 'f',
                            'align' => 'text-right',
                            'style' => 'width:100px;whiteSpace: normal;max-width:100px;',
                            'readonly' => false
                     ),
                     'g' => array(
                            'name' => 'g',
                            'type' => 'input',
                            'label' => 'Price G',
                            'field' => 'g',
                            'align' => 'text-right',
                            'style' => 'width:100px;whiteSpace: normal;max-width:100px;',
                            'readonly' => false
                     ),
                     'amcount' => array(
                            'name' => 'amcount',
                            'type' => 'input',
                            'label' => 'AM Count',
                            'field' => 'amcount',
                            'align' => 'text-right',
                            'style' => 'width:100px;whiteSpace: normal;max-width:100px;',
                            'readonly' => true
                     ),
                     'pmcount' => array(
                            'name' => 'pmcount',
                            'type' => 'input',
                            'label' => 'PM Count',
                            'field' => 'pmcount',
                            'align' => 'text-right',
                            'style' => 'width:100px;whiteSpace: normal;max-width:100px;',
                            'readonly' => true
                     ),
                     'amused' => array(
                            'name' => 'amused',
                            'type' => 'input',
                            'label' => 'AM Used',
                            'field' => 'amused',
                            'align' => 'text-right',
                            'style' => 'width:100px;whiteSpace: normal;max-width:100px;',
                            'readonly' => true
                     ),
                     'pmused' => array(
                            'name' => 'pmused',
                            'type' => 'input',
                            'label' => 'PM Used',
                            'field' => 'pmused',
                            'align' => 'text-right',
                            'style' => 'width:100px;whiteSpace: normal;max-width:100px;',
                            'readonly' => true
                     ),
                     'oqty' => array(
                            'name' => 'oqty',
                            'type' => 'label',
                            'label' => 'Onhand Qty',
                            'field' => 'oqty',
                            'align' => 'text-right',
                            'style' => 'text-align:right; width:90px;whiteSpace: normal;min-width:90px;',
                            'readonly' => false
                     ),
                     'asofqty' => array(
                            'name' => 'asofqty',
                            'type' => 'label',
                            'label' => 'Current Count',
                            'field' => 'asofqty',
                            'align' => 'text-right',
                            'style' => 'text-align:right; width:90px;whiteSpace: normal;min-width:90px;',
                            'readonly' => false
                     ),
                     'isqty' => array(
                            'name' => 'isqty',
                            'type' => 'input',
                            'label' => 'Quantity',
                            'field' => 'isqty',
                            'align' => 'text-right',
                            'style' => 'width:90px;whiteSpace: normal;min-width:90px;',
                            'readonly' => false
                     ),
                     'weight' => array(
                            'name' => 'weight',
                            'type' => 'input',
                            'label' => 'Actual Weight',
                            'field' => 'weight',
                            'align' => 'text-right',
                            'style' => 'width:90px;whiteSpace: normal;min-width:90px;',
                            'readonly' => true
                     ),
                     'weight2' => array(
                            'name' => 'weight2',
                            'type' => 'input',
                            'label' => 'Actual Weight',
                            'field' => 'weight2',
                            'align' => 'text-right',
                            'style' => 'width:90px;whiteSpace: normal;min-width:90px;',
                            'readonly' => true
                     ),
                     'totalestweight' => array(
                            'name' => 'totalestweight',
                            'type' => 'input',
                            'label' => 'Total Est Weight',
                            'field' => 'totalestweight',
                            'align' => 'text-right',
                            'style' => 'width:90px;whiteSpace: normal;min-width:90px;',
                            'readonly' => true
                     ),
                     'totalactualweight' => array(
                            'name' => 'totalactualweight',
                            'type' => 'input',
                            'label' => 'Total Actual Weight',
                            'field' => 'totalactualweight',
                            'align' => 'text-right',
                            'style' => 'width:90px;whiteSpace: normal;min-width:90px;',
                            'readonly' => true
                     ),
                     'replaceqty' => array(
                            'name' => 'replaceqty',
                            'type' => 'input',
                            'label' => 'For Replacement Qty',
                            'field' => 'replaceqty',
                            'align' => 'text-right',
                            'style' => 'width:90px;whiteSpace: normal;min-width:90px;',
                            'readonly' => false
                     ),
                     'isqty2' => array(
                            'name' => 'isqty2',
                            'type' => 'input',
                            'label' => 'Picker Qty',
                            'field' => 'isqty2',
                            'align' => 'text-right',
                            'style' => 'width:90px;whiteSpace: normal;min-width:90px;',
                            'readonly' => false
                     ),
                     'isqty3' => array(
                            'name' => 'isqty3',
                            'type' => 'input',
                            'label' => 'Qty',
                            'field' => 'isqty3',
                            'align' => 'text-right',
                            'style' => 'width:90px;whiteSpace: normal;min-width:90px;',
                            'readonly' => false
                     ),
                     'original_qty' => array(
                            'name' => 'original_qty',
                            'type' => 'input',
                            'label' => 'POS Qty',
                            'field' => 'original_qty',
                            'align' => 'text-right',
                            'style' => 'width:90px;whiteSpace: normal;min-width:90px;',
                            'readonly' => false
                     ),
                     'isamt' => array(
                            'name' => 'isamt',
                            'type' => 'input',
                            'label' => 'Amount',
                            'field' => 'isamt',
                            'align' => 'text-right',
                            'style' => 'text-align:right;width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'freight' => array(
                            'name' => 'freight',
                            'type' => 'input',
                            'label' => 'Freight',
                            'field' => 'freight',
                            'align' => 'text-right',
                            'style' => 'text-align:right;width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'ordernum' => array(
                            'name' => 'ordernum',
                            'type' => 'input',
                            'label' => 'Order #',
                            'field' => 'ordernum',
                            'align' => 'text-center',
                            'style' => 'text-align:center;width:50px;whiteSpace: normal;min-width:50px;',
                            'readonly' => false
                     ),
                     'orderno' => array(
                            'name' => 'orderno',
                            'type' => 'input',
                            'label' => 'Orderno',
                            'field' => 'orderno',
                            'align' => 'text-center',
                            'style' => 'text-align:center;width:50px;whiteSpace: normal;min-width:50px;',
                            'readonly' => false
                     ),
                     'priolvl' => array(
                            'name' => 'priolvl',
                            'type' => 'input',
                            'label' => 'Priority',
                            'field' => 'priolvl',
                            'align' => 'text-center',
                            'style' => 'text-align:center;width:50px;whiteSpace: normal;min-width:50px;',
                            'readonly' => false
                     ),
                     'svsnum' => array(
                            'name' => 'svsnum',
                            'type' => 'label',
                            'label' => 'SVS #',
                            'field' => 'svsnum',
                            'align' => 'text-left',
                            'style' => 'text-align:center;width:50px;whiteSpace: normal;min-width:50px;',
                            'readonly' => false
                     ),
                     'rrqty' => array(
                            'name' => 'rrqty',
                            'type' => 'input',
                            'label' => 'Qty',
                            'field' => 'rrqty',
                            'align' => 'text-right',
                            'style' => 'text-align:right;width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'rrqty2' => array(
                            'name' => 'rrqty2',
                            'type' => 'input',
                            'label' => 'Qty',
                            'field' => 'rrqty2',
                            'align' => 'text-right',
                            'style' => 'text-align:right;width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'rrqty3' => array(
                            'name' => 'rrqty3',
                            'type' => 'input',
                            'label' => 'Qty',
                            'field' => 'rrqty3',
                            'align' => 'text-right',
                            'style' => 'text-align:right;width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'kgs' => array(
                            'name' => 'kgs',
                            'type' => 'input',
                            'label' => 'Kgs',
                            'field' => 'kgs',
                            'align' => 'text-right',
                            'style' => 'text-align:right;width:170px;whiteSpace: normal;min-width:170px;'
                     ),
                     'reqqty' => array(
                            'name' => 'reqqty',
                            'type' => 'input',
                            'label' => 'Request Qty',
                            'field' => 'reqqty',
                            'align' => 'text-right',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'prqty' => array(
                            'name' => 'prqty',
                            'type' => 'input',
                            'label' => 'Purch. Req. Qty',
                            'field' => 'prqty',
                            'align' => 'text-right',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'rrcost' => array(
                            'name' => 'rrcost',
                            'type' => 'input',
                            'label' => 'Amount',
                            'field' => 'rrcost',
                            'align' => 'text-right',
                            'style' => 'width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),


                     'rrcost2' => array(
                            'name' => 'rrcost2',
                            'type' => 'input',
                            'label' => 'Supp Cost',
                            'field' => 'rrcost2',
                            'align' => 'text-right',
                            'style' => 'width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'rrcost3' => array(
                            'name' => 'rrcost3',
                            'type' => 'input',
                            'label' => 'Supp Cost',
                            'field' => 'rrcost3',
                            'align' => 'text-right',
                            'style' => 'width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'cost' => array(
                            'name' => 'cost',
                            'type' => 'input',
                            'label' => 'Landed Cost',
                            'field' => 'cost',
                            'align' => 'text-right',
                            'style' => 'width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => true
                     ),
                     'lessvat' => array(
                            'name' => 'lessvat',
                            'type' => 'label',
                            'label' => 'Less VAT',
                            'field' => 'lessvat',
                            'align' => 'text-right',
                            'style' => 'text-align:right;width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => true
                     ),
                     'sramt' => array(
                            'name' => 'sramt',
                            'type' => 'label',
                            'label' => 'Senior Disc',
                            'field' => 'sramt',
                            'align' => 'text-right',
                            'style' => 'text-align:right;width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => true
                     ),
                     'pwdamt' => array(
                            'name' => 'pwdamt',
                            'type' => 'label',
                            'label' => 'PWD Disc',
                            'field' => 'pwdamt',
                            'align' => 'text-right',
                            'style' => 'text-align:right;width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => true
                     ),
                     'ext' => array(
                            'name' => 'ext',
                            'type' => 'label',
                            'label' => 'Total',
                            'field' => 'ext',
                            'align' => 'text-right',
                            'style' => 'text-align:right;width:220px;whiteSpace: normal;min-width:220px;',
                            'readonly' => true
                     ),
                     'ext2' => array(
                            'name' => 'ext2',
                            'type' => 'label',
                            'label' => 'Total',
                            'field' => 'ext2',
                            'align' => 'text-right',
                            'style' => 'text-align:right;width:220px;whiteSpace: normal;min-width:220px;',
                            'readonly' => true
                     ),
                     'disc' => array(
                            'name' => 'disc',
                            'type' => 'input',
                            'label' => 'Discount',
                            'field' => 'disc',
                            'align' => 'text-left',
                            'style' => 'width:90px;whiteSpace: normal;min-width:90px;',
                            'readonly' => false
                     ),
                     'uom' => array(
                            'name' => 'uom',
                            'type' => 'lookup',
                            'label' => 'UOM',
                            'field' => 'uom',
                            'align' => 'text-left',
                            'style' => 'text-align:left;width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false,
                            'lookupclass' => 'uomstock',
                            'action' => 'lookupuom'
                     ),
                     'uom2' => array(
                            'name' => 'uom2',
                            'type' => 'input',
                            'label' => 'Wholesales UOM',
                            'field' => 'uom2',
                            'align' => 'text-left',
                            'style' => 'text-align:left;width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'uom3' => array(
                            'name' => 'uom3',
                            'type' => 'input',
                            'label' => 'Others UOM',
                            'field' => 'uom3',
                            'align' => 'text-left',
                            'style' => 'text-align:left;width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'printuom' => array(
                            'name' => 'printuom',
                            'type' => 'input',
                            'label' => 'Printed UOM',
                            'field' => 'printuom',
                            'align' => 'text-left',
                            'style' => 'text-align:left;width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'unit' => array(
                            'name' => 'unit',
                            'type' => 'input',
                            'label' => 'UOM',
                            'field' => 'unit',
                            'align' => 'text-left',
                            'style' => 'text-align:left;width:100px;whiteSpace: normal;min-width:100px;max-width:100px;',
                            'readonly' => true,
                     ),

                     'inputuom' => array(
                            'name' => 'uom',
                            'type' => 'input',
                            'label' => 'UOM',
                            'field' => 'uom',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'wh' => array(
                            'name' => 'wh',
                            'type' => 'lookup',
                            'label' => 'Warehouse',
                            'field' => 'wh',
                            'align' => 'text-left',
                            'style' => 'text-align:left;width:180px;whiteSpace: normal;min-width:180px;',
                            'readonly' => true,
                            'lookupclass' => 'whstock',
                            'action' => 'lookupclient'
                     ),
                     'intransitwh' => array(
                            'name' => 'intransitwh',
                            'type' => 'lookup',
                            'label' => 'In-Transit',
                            'field' => 'intransitwh',
                            'align' => 'text-center',
                            'style' => 'text-align:right;width:180px;whiteSpace: normal;min-width:180px;',
                            'readonly' => true,
                            'lookupclass' => 'stockinfowh',
                            'action' => 'lookupclient'
                     ),
                     'isasset' => array(
                            'name' => 'isasset',
                            'type' => 'lookup',
                            'label' => 'Is Asset?',
                            'field' => 'isasset',
                            'align' => 'text-center',
                            'style' => 'text-align:right;width:60px;whiteSpace: normal;min-width:60px;',
                            'readonly' => true,
                            'lookupclass' => 'lookupisasset',
                            'action' => 'lookuprandom'
                     ),
                     'ref' => array(
                            'name' => 'ref',
                            'type' => 'lookup',
                            'label' => 'Reference',
                            'field' => 'ref',
                            'align' => 'text-left',
                            'style' => 'text-align:left;width:180px;whiteSpace: normal;min-width:180px;',
                            'readonly' => true,
                            'lookupclass' => 'lookupref',
                            'action' => 'lookupref'
                     ),
                     'rcchecks' => array(
                            'name' => 'rcchecks',
                            'type' => 'lookup',
                            'label' => 'Replacement Cheque',
                            'field' => 'rcchecks',
                            'align' => 'text-left',
                            'style' => 'text-align:left;width:180px;whiteSpace: normal;min-width:180px;',
                            'readonly' => true,
                            'lookupclass' => 'lookuprcchecks',
                            'action' => 'lookuprcchecks'
                     ),
                     'checkdate' => array(
                            'name' => 'checkdate',
                            'type' => 'date',
                            'label' => 'Checkdate',
                            'field' => 'checkdate',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'postdate' => array(
                            'name' => 'postdate',
                            'type' => 'date',
                            'label' => 'Date',
                            'field' => 'postdate',
                            'align' => 'text-left',
                            'style' => 'text-align:left;width:90px;whiteSpace: normal;min-width:90px;',
                            'readonly' => false
                     ),
                     'due' => array(
                            'name' => 'due',
                            'type' => 'date',
                            'label' => 'Due Date',
                            'field' => 'due',
                            'align' => 'text-left',
                            'style' => 'text-align:left;width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'clearday' => array(
                            'name' => 'clearday',
                            'type' => 'date',
                            'label' => 'Clear Day',
                            'field' => 'clearday',
                            'align' => 'text-left',
                            'style' => 'text-align:left;width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'dateid' => array(
                            'name' => 'dateid',
                            'type' => 'date',
                            'label' => 'Date',
                            'field' => 'dateid',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'dateid2' => array(
                            'name' => 'dateid2',
                            'type' => 'date',
                            'label' => 'End Date',
                            'field' => 'dateid2',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'shipdate' => array(
                            'name' => 'shipdate',
                            'type' => 'date',
                            'label' => 'Ship Date',
                            'field' => 'shipdate',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'dateneeded' => array(
                            'name' => 'dateneeded',
                            'type' => 'date',
                            'label' => 'Date Needed',
                            'field' => 'dateneeded',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'reqdate' => array(
                            'name' => 'reqdate',
                            'type' => 'date',
                            'label' => 'Date of Request',
                            'field' => 'reqdate',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'releasedate' => array(
                            'name' => 'releasedate',
                            'type' => 'date',
                            'label' => 'Date Release',
                            'field' => 'releasedate',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'ovaliddate' => array(
                            'name' => 'ovaliddate',
                            'type' => 'date',
                            'label' => 'Validdate',
                            'field' => 'ovaliddate',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),
                     'deadline' => array(
                            'name' => 'deadline',
                            'type' => 'date',
                            'label' => 'Deadline',
                            'field' => 'deadline',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),
                     'prref' => array(
                            'name' => 'prref',
                            'type' => 'label',
                            'label' => 'PR Reference',
                            'field' => 'prref',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),
                     'ocrref' => array(
                            'name' => 'ocrref',
                            'type' => 'label',
                            'label' => 'OCR Status',
                            'field' => 'ocrref',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),
                     'rrref' => array(
                            'name' => 'rrref',
                            'type' => 'label',
                            'label' => 'RR Status',
                            'field' => 'rrref',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),
                     'cvref' => array(
                            'name' => 'cvref',
                            'type' => 'label',
                            'label' => 'CV Status',
                            'field' => 'cvref',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),

                     'osiref2' => array(
                            'name' => 'osiref2',
                            'type' => 'label',
                            'label' => 'OSI Status',
                            'field' => 'osiref2',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),
                     'osiref' => array(
                            'name' => 'osiref',
                            'type' => 'label',
                            'label' => 'OSI Status',
                            'field' => 'osiref',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),
                     'deliverydate' => array(
                            'name' => 'deliverydate',
                            'type' => 'date',
                            'label' => 'Delivery Date',
                            'field' => 'deliverydate',
                            'align' => 'text-left',
                            'style' => 'width:200px;whiteSpace: normal;min-width:200px;',
                            'readonly' => false
                     ),
                     'depodate' => array(
                            'name' => 'depodate',
                            'type' => 'date',
                            'label' => 'Payment Date',
                            'field' => 'depodate',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'validate' => array(
                            'name' => 'validate',
                            'type' => 'date',
                            'label' => 'Validated Date',
                            'field' => 'validate',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'returndate' => array(
                            'name' => 'returndate',
                            'type' => 'date',
                            'label' => 'Return Date',
                            'field' => 'returndate',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'released' => array(
                            'name' => 'released',
                            'type' => 'label',
                            'label' => 'Released Date',
                            'field' => 'released',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;max-width:100px;',
                            'readonly' => false
                     ),
                     'releaseby' => array(
                            'name' => 'releaseby',
                            'type' => 'label',
                            'label' => 'Released by.',
                            'field' => 'releaseby',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;max-width:100px;',
                            'readonly' => true
                     ),
                     'loc' => array(
                            'name' => 'loc',
                            'type' => 'lookup',
                            'label' => 'Location',
                            'field' => 'loc',
                            'align' => 'text-left',
                            'style' => 'min-width:200px;',
                            'readonly' => true,
                            'lookupclass' => 'locstock',
                            'action' => 'lookuploc'
                     ),
                     'loc2' => array(
                            'name' => 'loc2',
                            'type' => 'input',
                            'field' => 'loc2',
                            'label' => 'Destination Location',
                            'class' => 'csloc2 sbccsreadonly',
                            'align' => 'text-left',
                            'readonly' => false,
                            'style' => 'width:120px;whiteSpace: normal;min-width:130px;',
                            'required' => false
                     ),
                     'location' => array(
                            'name' => 'location',
                            'type' => 'lookup',
                            'label' => 'Location',
                            'field' => 'location',
                            'align' => 'text-left',
                            'style' => 'min-width:200px;',
                            'readonly' => true,
                            'lookupclass' => 'locstock',
                            'action' => 'lookuplocation',
                            'addedparams' => ['whid', 'whname'],
                     ),
                     'location2' => array(
                            'name' => 'location2',
                            'type' => 'lookup',
                            'label' => 'Destination Location',
                            'field' => 'location2',
                            'align' => 'text-left',
                            'style' => 'min-width:200px;',
                            'readonly' => true,
                            'lookupclass' => 'locstock2',
                            'action' => 'lookuplocation',
                            'addedparams' => ['whid', 'whname'],
                     ),
                     'whrem' => array(
                            'name' => 'whrem',
                            'type' => 'lookup',
                            'label' => 'Remarks',
                            'field' => 'whrem',
                            'align' => 'text-left',
                            'style' => 'min-width:200px;',
                            'readonly' => true,
                            'lookupclass' => 'lookupwhrem',
                            'action' => 'lookupsetup'
                     ),
                     'boxno' => array(
                            'name' => 'boxno',
                            'type' => 'input',
                            'label' => 'BOX NO.',
                            'field' => 'boxno',
                            'align' => 'text-left',
                            'style' => 'min-width:200px;',
                            'readonly' => true
                     ),
                     'expiry' => array(
                            'name' => 'expiry',
                            'type' => 'lookup',
                            'label' => 'Expiry',
                            'align' => 'text-left',
                            'field' => 'expiry',
                            'style' => 'min-width:100px;',
                            'readonly' => true,
                            'lookupclass' => 'expirystock',
                            'action' => 'lookupexpiry'
                     ),
                     'tfaccount' => array(
                            'name' => 'tfaccount',
                            'type' => 'lookup',
                            'label' => 'Account Code',
                            'field' => 'tfaccount',
                            'align' => 'text-left',
                            'style' => 'width:80px;whiteSpace: normal;min-width:80px;',
                            'readonly' => false,
                            'lookupclass' => 'courseaccountlookup',
                            'action' => 'lookupacno'
                     ),
                     'acno' => array(
                            'name' => 'acno',
                            'type' => 'input',
                            'label' => 'Account Code',
                            'field' => 'acno',
                            'align' => 'text-left',
                            'style' => 'width:80px;whiteSpace: normal;min-width:80px;',
                            'readonly' => false,
                            'lookupclass' => 'lookupacno',
                            'action' => 'lookupacno'
                     ),
                     'acnoname' => array(
                            'name' => 'acnoname',
                            'type' => 'hidden',
                            'label' => '',
                            'field' => 'acnoname',
                            'align' => 'text-left',
                            'style' => 'width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'terminal' => array(
                            'name' => 'terminal',
                            'field' => 'terminal',
                            'type' => 'input',
                            'label' => 'Terminal',
                            'align' => 'text-left',
                            'style' => 'width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'terminalid' => array(
                            'name' => 'terminalid',
                            'field' => 'terminalid',
                            'type' => 'input',
                            'label' => 'Terminal ID',
                            'align' => 'text-left',
                            'style' => 'width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'bank' => array(
                            'name' => 'bank',
                            'field' => 'bank',
                            'type' => 'input',
                            'label' => 'Bank',
                            'align' => 'text-left',
                            'style' => 'width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'assetaccount' => array(
                            'name' => 'assetaccount',
                            'type' => 'lookup',
                            'label' => 'Asset',
                            'field' => 'acnoid',
                            'align' => 'text-left',
                            'style' => 'text-align:left;width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false,
                            'lookupclass' => 'assetlookup',
                            'action' => 'lookupasset'
                     ),
                     'revenueaccount' => array(
                            'name' => 'revenueaccount',
                            'type' => 'lookup',
                            'label' => 'Revenue',
                            'field' => 'acnoid',
                            'align' => 'text-left',
                            'style' => 'text-align:left;width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false,
                            'lookupclass' => 'revenuelookup',
                            'action' => 'lookuprevenue'
                     ),
                     'db' => array(
                            'name' => 'db',
                            'type' => 'input',
                            'label' => 'Debit',
                            'field' => 'db',
                            'align' => 'text-right',
                            'style' => 'text-align:right;width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'cr' => array(
                            'name' => 'cr',
                            'type' => 'input',
                            'label' => 'Credit',
                            'field' => 'cr',
                            'align' => 'text-right',
                            'style' => 'text-align:right;width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'fdb' => array(
                            'name' => 'fdb',
                            'type' => 'input',
                            'label' => 'F.Debit',
                            'field' => 'fdb',
                            'align' => 'text-right',
                            'style' => 'text-align:right;width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'fcr' => array(
                            'name' => 'fcr',
                            'type' => 'input',
                            'label' => 'F.Credit',
                            'field' => 'fcr',
                            'align' => 'text-right',
                            'style' => 'text-align:right;width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'appamt' => array(
                            'name' => 'appamt',
                            'type' => 'input',
                            'label' => 'Approved Amount',
                            'field' => 'appamt',
                            'align' => 'text-right',
                            'style' => 'text-align:right;width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'bal' => array(
                            'name' => 'bal',
                            'type' => 'input',
                            'label' => 'Balance',
                            'field' => 'bal',
                            'align' => 'text-right',
                            'style' => 'text-align:right;width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'bal2' => array(
                            'name' => 'bal2',
                            'type' => 'input',
                            'label' => 'Balance',
                            'field' => 'bal',
                            'align' => 'text-right',
                            'style' => 'text-align:right;width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'adjust' => array(
                            'name' => 'adjust',
                            'type' => 'input',
                            'label' => 'Adjust',
                            'field' => 'adjust',
                            'align' => 'text-right',
                            'style' => 'text-align:right;width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'checkno' => array(
                            'name' => 'checkno',
                            'type' => 'input',
                            'label' => 'Check#',
                            'field' => 'checkno',
                            'align' => 'text-left',
                            'style' => 'width:90px;whiteSpace: normal;min-width:90px;',
                            'readonly' => false
                     ),
                     'rem' => array(
                            'name' => 'rem',
                            'type' => 'input',
                            'label' => 'Notes',
                            'field' => 'rem',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),
                     'carem' => array(
                            'name' => 'carem',
                            'type' => 'input',
                            'label' => 'Notes (Canvass Approver)',
                            'field' => 'carem',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),
                     'acrem' => array(
                            'name' => 'acrem',
                            'type' => 'input',
                            'label' => 'Notes (Approved Canvass)',
                            'field' => 'acrem',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),
                     'payrem' => array(
                            'name' => 'payrem',
                            'type' => 'input',
                            'label' => 'Notes',
                            'field' => 'payrem',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),
                     'cancelrem' => array(
                            'name' => 'cancelrem',
                            'type' => 'label',
                            'label' => 'Cancelled Remarks',
                            'field' => 'cancelrem',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),
                     'canceldate' => array(
                            'name' => 'canceldate',
                            'type' => 'label',
                            'label' => 'Cancelled Date',
                            'field' => 'canceldate',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),
                     'fstatus' => array(
                            'name' => 'fstatus',
                            'type' => 'input',
                            'label' => 'SKU',
                            'field' => 'fstatus',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),
                     'itemstatus' => array(
                            'name' => 'itemstatus',
                            'type' => 'input',
                            'label' => 'SKU',
                            'field' => 'itemstatus',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),
                     'rem1' => array(
                            'name' => 'rem1',
                            'type' => 'input',
                            'label' => 'DNotes',
                            'field' => 'rem1',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),
                     'krdoc' => array(
                            'name' => 'krdoc',
                            'type' => 'input',
                            'label' => 'Counter Reciept No',
                            'field' => 'krdoc',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),
                     'kadoc' => array(
                            'name' => 'kadoc',
                            'type' => 'input',
                            'label' => 'AR Audit No',
                            'field' => 'kadoc',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),
                     'status' => array(
                            'name' => 'status',
                            'type' => 'input',
                            'label' => 'Status',
                            'field' => 'status',
                            'align' => 'text-center',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'comm1' => array(
                            'name' => 'comm1',
                            'type' => 'input',
                            'label' => 'Commission 1',
                            'field' => 'comm1',
                            'align' => 'text-center',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'comm2' => array(
                            'name' => 'comm2',
                            'type' => 'input',
                            'label' => 'Commission 2',
                            'field' => 'comm2',
                            'align' => 'text-center',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'comm3' => array(
                            'name' => 'comm3',
                            'type' => 'input',
                            'label' => 'Commission 3',
                            'field' => 'comm3',
                            'align' => 'text-center',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'supervisorstatus' => array(
                            'name' => 'supervisorstatus',
                            'type' => 'input',
                            'label' => 'Status (Suppervisor)',
                            'field' => 'supervisorstatus',
                            'align' => 'text-center',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'inqstatus' => array(
                            'name' => 'inqstatus',
                            'type' => 'input',
                            'label' => 'Inquiry Status',
                            'class' => 'csinqstatus sbccsreadonly',
                            'field' => 'status',
                            'align' => 'text-center',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => true
                     ),
                     'statname' => array(
                            'name' => 'statname',
                            'type' => 'input',
                            'label' => 'Priority Level',
                            'field' => 'statname',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),

                     'amtstatus' => array(
                            'name' => 'amtstatus',
                            'type' => 'label',
                            'label' => 'Amt Status',
                            'field' => 'status',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'confirmstatus' => array(
                            'name' => 'confirmstatus',
                            'type' => 'input',
                            'label' => 'Status',
                            'field' => 'confirmstatus',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'lblstatus' => array(
                            'name' => 'stat',
                            'type' => 'input',
                            'label' => 'Status',
                            'field' => 'stat',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'lblforapp' => array(
                            'name' => 'lblforapp',
                            'type' => 'input',
                            'label' => 'Approver Status',
                            'field' => 'lblforapp',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'client' => array(
                            'name' => 'client',
                            'type' => 'lookup',
                            'label' => 'Vendor',
                            'field' => 'client',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => true,
                            'lookupclass' => 'clientdetail',
                            'action' => 'lookupclient'
                     ),
                     'qtref' => array(
                            'name' => 'qtref',
                            'field' => 'qtref',
                            'type' => 'lookup',
                            'label' => 'QTN#',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => true,
                            'lookupclass' => 'lookupqtn_detail',
                            'action' => 'lookupqtn'
                     ),
                     'qa' => array(
                            'name' => 'qa',
                            'type' => 'chip',
                            'label' => 'Pending',
                            'field' => 'qa',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;max-width:100px;',
                            'readonly' => true,
                            'color' => 'primary',
                            'textcolor' => 'white'
                     ),
                     'pending' => array(
                            'name' => 'pending',
                            'type' => 'label',
                            'label' => 'Pending',
                            'field' => 'pending',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;max-width:100px;',
                            'readonly' => true,
                     ),


                     'roqa' => array(
                            'name' => 'roqa',
                            'type' => 'chip',
                            'label' => 'Pending RO',
                            'field' => 'roqa',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;max-width:100px;',
                            'readonly' => true,
                            'color' => 'primary',
                            'textcolor' => 'white'
                     ),
                     'rqcd' => array(
                            'name' => 'rqcd',
                            'type' => 'label',
                            'label' => 'Request-Canvass Pending',
                            'field' => 'rqcd',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;max-width:100px;',
                            'readonly' => true,
                            'color' => 'primary',
                            'textcolor' => 'white'
                     ),
                     'basepending' => array(
                            'name' => 'basepending',
                            'type' => 'label',
                            'label' => 'Base Pending',
                            'field' => 'basepending',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;max-width:100px;',
                            'readonly' => true,
                            'color' => 'primary',
                            'textcolor' => 'white'
                     ),
                     'served' => array(
                            'name' => 'served',
                            'type' => 'chip',
                            'label' => 'Served',
                            'field' => 'qa',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;max-width:100px;',
                            'readonly' => true,
                            'color' => 'primary',
                            'textcolor' => 'white'
                     ),
                     'availableslot' => array(
                            'name' => 'availableslot',
                            'type' => 'chip',
                            'label' => 'Open Slots',
                            'field' => 'availableslot',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => true,
                            'color' => 'primary',
                            'textcolor' => 'white'
                     ),
                     'notectr' => array(
                            'name' => 'notectr',
                            'type' => 'chip',
                            'label' => 'Comments Ctr',
                            'field' => 'notectr',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;max-width:100px;',
                            'readonly' => true,
                            'color' => 'green',
                            'textcolor' => 'white'
                     ),
                     'docno' => array(
                            'name' => 'docno',
                            'type' => 'input',
                            'label' => 'Document#',
                            'field' => 'docno',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'pydocno' => array(
                            'name' => 'pydocno',
                            'type' => 'input',
                            'label' => 'Payment Listing#',
                            'field' => 'pydocno',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'si1' => array(
                            'name' => 'si1',
                            'type' => 'input',
                            'label' => 'Yourref',
                            'field' => 'si1',
                            'align' => 'text-left',
                            'style' => 'width: 140px;whiteSpace: normal;min-width:140px;max-width:150px;text-align:left;',
                            'readonly' => true
                     ),
                     'si2' => array(
                            'name' => 'si2',
                            'type' => 'input',
                            'label' => 'SI #',
                            'field' => 'si2',
                            'align' => 'text-left',
                            'style' => 'width: 140px;whiteSpace: normal;min-width:140px;max-width:150px;text-align:left;;',
                            'readonly' => false
                     ),
                     'transtype' => array(
                            'name' => 'transtype',
                            'type' => 'input',
                            'label' => 'Transaction Type',
                            'field' => 'transtype',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'qtype' => array(
                            'name' => 'qtype',
                            'type' => 'input',
                            'label' => 'Type',
                            'field' => 'qtype',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'runtime' => array(
                            'name' => 'runtime',
                            'type' => 'input',
                            'label' => 'Runtime',
                            'field' => 'runtime',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'question' => array(
                            'name' => 'question',
                            'type' => 'input',
                            'label' => 'Question',
                            'field' => 'question',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'a' => array(
                            'name' => 'a',
                            'type' => 'input',
                            'label' => 'A',
                            'field' => 'a',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'b' => array(
                            'name' => 'b',
                            'type' => 'input',
                            'label' => 'B',
                            'field' => 'b',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'c' => array(
                            'name' => 'c',
                            'type' => 'input',
                            'label' => 'C',
                            'field' => 'c',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'd' => array(
                            'name' => 'd',
                            'type' => 'input',
                            'label' => 'D',
                            'field' => 'd',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'e' => array(
                            'name' => 'e',
                            'type' => 'input',
                            'label' => 'E',
                            'field' => 'e',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'ans' => array(
                            'name' => 'ans',
                            'type' => 'input',
                            'label' => 'Answer',
                            'field' => 'ans',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'answord' => array(
                            'name' => 'answord',
                            'type' => 'input',
                            'label' => 'Answer In Word',
                            'field' => 'answord',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'yourref' => array(
                            'name' => 'yourref',
                            'type' => 'input',
                            'label' => 'Yourref',
                            'field' => 'yourref',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'lotno' => array(
                            'name' => 'lotno',
                            'type' => 'input',
                            'label' => 'Lot No.',
                            'field' => 'lotno',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'ctrlno' => array(
                            'name' => 'ctrlno',
                            'type' => 'label',
                            'label' => 'Control No.',
                            'field' => 'ctrlno',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'ourref' => array(
                            'name' => 'ourref',
                            'type' => 'input',
                            'label' => 'Ourref',
                            'field' => 'ourref',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'whref' => array(
                            'name' => 'whref',
                            'type' => 'input',
                            'label' => 'Laying House Reference',
                            'field' => 'whref',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'bmonth' => array(
                            'name' => 'bmonth',
                            'type' => 'input',
                            'label' => 'Month',
                            'field' => 'bmonth',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'byear' => array(
                            'name' => 'byear',
                            'type' => 'input',
                            'label' => 'Year',
                            'field' => 'byear',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'sonumber' => array(
                            'name' => 'sonumber',
                            'type' => 'input',
                            'label' => 'SO No.',
                            'field' => 'sonumber',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'factor' => array(
                            'name' => 'factor',
                            'type' => 'input',
                            'label' => 'Factor',
                            'field' => 'factor',
                            'align' => 'text-right',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'terms' => array(
                            'name' => 'terms',
                            'type' => 'input',
                            'label' => 'Terms',
                            'field' => 'terms',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'days' => array(
                            'name' => 'days',
                            'type' => 'input',
                            'label' => 'Days',
                            'field' => 'days',
                            'align' => 'text-right',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'interest' => array(
                            'name' => 'interest',
                            'type' => 'input',
                            'label' => 'Interest Rate',
                            'field' => 'interest',
                            'align' => 'text-right',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'pfnf' => array(
                            'name' => 'pfnf',
                            'type' => 'input',
                            'label' => 'PF & NF',
                            'field' => 'pfnf',
                            'align' => 'text-right',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'dst' => array(
                            'name' => 'dst',
                            'type' => 'input',
                            'label' => 'DST',
                            'field' => 'dst',
                            'align' => 'text-right',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'mri' => array(
                            'name' => 'mri',
                            'type' => 'input',
                            'label' => 'MRI',
                            'field' => 'mri',
                            'align' => 'text-right',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'loantype' => array(
                            'name' => 'loantype',
                            'field' => 'loantype',
                            'type' => 'input',
                            'label' => 'Loan Type',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'readonly' => false,
                     ),
                     'void' => array(
                            'name' => 'void',
                            'type' => 'toggle',
                            'label' => 'Void',
                            'field' => 'void',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false,
                            'checkfield' => ''
                     ),
                     'waivedqty' => array(
                            'name' => 'waivedqty',
                            'type' => 'toggle',
                            'label' => 'Waived Qty',
                            'field' => 'waivedqty',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false,
                            'checkfield' => ''
                     ),
                     'waivedspecs' => array(
                            'name' => 'waivedspecs',
                            'type' => 'toggle',
                            'label' => 'Waived Specs',
                            'field' => 'waivedspecs',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false,
                            'checkfield' => ''
                     ),
                     'iscleared' => array(
                            'name' => 'iscleared',
                            'type' => 'toggle',
                            'label' => 'Cleared',
                            'field' => 'iscleared',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false,
                            'checkfield' => ''
                     ),
                     'isrr' => array(
                            'name' => 'isrr',
                            'type' => 'toggle',
                            'label' => 'Received',
                            'field' => 'isrr',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false,
                            'checkfield' => ''
                     ),
                     'issc' => array(
                            'name' => 'issc',
                            'type' => 'toggle',
                            'label' => 'Surcharge',
                            'field' => 'issc',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false,
                            'checkfield' => ''
                     ),
                     'isreturn' => array(
                            'name' => 'isreturn',
                            'type' => 'toggle',
                            'label' => 'Return',
                            'field' => 'isreturn',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false,
                            'checkfield' => ''
                     ),
                     'isapprover' => array(
                            'name' => 'isapprover',
                            'type' => 'toggle',
                            'label' => 'Approver',
                            'field' => 'isapprover',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => true,
                            'checkfield' => ''
                     ),
                     'issupervisor' => array(
                            'name' => 'issupervisor',
                            'type' => 'toggle',
                            'label' => 'Supervisor',
                            'field' => 'issupervisor',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => true,
                            'checkfield' => ''
                     ),
                     'ischecker' => array(
                            'name' => 'ischecker',
                            'type' => 'toggle',
                            'label' => 'Checker',
                            'field' => 'ischecker',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => true,
                            'checkfield' => ''
                     ),
                     'isloc' => array(
                            'name' => 'isloc',
                            'type' => 'toggle',
                            'label' => 'Required Location',
                            'field' => 'isloc',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false,
                            'checkfield' => ''
                     ),
                     'isnsi' => array(
                            'name' => 'isnsi',
                            'type' => 'toggle',
                            'label' => 'No System Input',
                            'field' => 'isnsi',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false,
                            'checkfield' => ''
                     ),

                     'ists' => array(
                            'name' => 'ists',
                            'type' => 'toggle',
                            'label' => 'For TS',
                            'field' => 'ists',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false,
                            'checkfield' => ''
                     ),
                     'isgeneratefa' => array(
                            'name' => 'isgeneratefa',
                            'type' => 'toggle',
                            'label' => 'Generate Asset',
                            'field' => 'isgeneratefa',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false,
                            'checkfield' => ''
                     ),

                     'iscldetails' => array(
                            'name' => 'iscldetails',
                            'type' => 'toggle',
                            'label' => 'Required Client Details',
                            'field' => 'iscldetails',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false,
                            'checkfield' => ''
                     ),
                     'ispicked' => array(
                            'name' => 'ispicked',
                            'type' => 'toggle',
                            'label' => 'Picked',
                            'field' => 'ispicked',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => true
                     ),
                     'isadv' => array(
                            'name' => 'isadv',
                            'type' => 'toggle',
                            'label' => 'Advance Payment',
                            'field' => 'isadv',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => true
                     ),

                     'isinvoice' => array(
                            'name' => 'isinvoice',
                            'type' => 'toggle',
                            'label' => 'Invoice',
                            'field' => 'isinvoice',
                            'align' => 'text-left',
                            'style' => 'width:80px;whiteSpace: normal;min-width:80px;text-align:center;',
                            'readonly' => true
                     ),

                     'forreturn' => array(
                            'name' => 'forreturn',
                            'type' => 'toggle',
                            'label' => 'For Return/Adjusment',
                            'field' => 'forreturn',
                            'align' => 'left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false,
                            'checkfield' => ''
                     ),
                     'ispermanent' => array(
                            'name' => 'ispermanent',
                            'type' => 'toggle',
                            'label' => 'Permanent',
                            'field' => 'ispermanent',
                            'align' => 'left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false,
                            'checkfield' => ''
                     ),
                     'istax' => array(
                            'name' => 'istax',
                            'type' => 'toggle',
                            'label' => 'Taxable',
                            'field' => 'istax',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'ispayroll' => array(
                            'name' => 'ispayroll',
                            'type' => 'toggle',
                            'label' => 'Payroll',
                            'field' => 'ispayroll',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'noprint' => array(
                            'name' => 'noprint',
                            'type' => 'toggle',
                            'label' => 'Do not Print',
                            'field' => 'noprint',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'ispaid' => array(
                            'name' => 'ispaid',
                            'type' => 'toggle',
                            'label' => 'Paid',
                            'field' => 'ispaid',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'intransit' => array(
                            'name' => 'intransit',
                            'type' => 'toggle',
                            'label' => 'In-Transit',
                            'field' => 'intransit',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'isemail' => array(
                            'name' => 'isemail',
                            'type' => 'toggle',
                            'label' => 'Emailed',
                            'field' => 'isemail',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'isvat' => array(
                            'name' => 'isvat',
                            'type' => 'toggle',
                            'label' => 'With VAT',
                            'field' => 'isvat',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'isewt' => array(
                            'name' => 'isewt',
                            'type' => 'toggle',
                            'label' => 'With EWT',
                            'field' => 'isewt',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'ewt' => array(
                            'name' => 'ewt',
                            'field' => 'ewt',
                            'type' => 'input',
                            'label' => 'EWT',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'isvewt' => array(
                            'name' => 'isvewt',
                            'type' => 'toggle',
                            'label' => 'With VAT/EWT',
                            'field' => 'isvewt',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'isss' => array(
                            'name' => 'isss',
                            'type' => 'toggle',
                            'label' => 'Stock Issuance W/Out SO',
                            'field' => 'isss',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),

                     'acnotitle' => array(
                            'name' => 'acnotitle',
                            'type' => 'lookup',
                            'label' => 'Account Title',
                            'field' => 'acnotitle',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => true,
                            'lookupclass' => 'lookupacnocode',
                            'action' => 'lookupacnocode'
                     ),
                     'ewtcode' => array(
                            'name' => 'ewtcode',
                            'type' => 'lookup',
                            'label' => 'EWT Code',
                            'field' => 'ewtcode',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => true,
                            'lookupclass' => 'ewtcode',
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
                     'project' => array(
                            'name' => 'project',
                            'type' => 'lookup',
                            'label' => 'Project',
                            'field' => 'project',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => true,
                            'lookupclass' => 'dproject',
                            'action' => 'lookupproject'
                     ),
                     'listdocument' => array(
                            'name' => 'docno',
                            'field' => 'docno',
                            'type' => 'input',
                            'label' => 'Document#',
                            'align' => 'text-left',
                            'style' => 'width: 140px;whiteSpace: normal;min-width:140px;max-width:150px;text-align:left;',
                            'readonly' => true
                     ),
                     'listdate' => array(
                            'name' => 'dateid',
                            'field' => 'dateid',
                            'type' => 'date',
                            'label' => 'Transaction Date',
                            'align' => 'text-left',
                            'style' => 'width: 90px;whiteSpace: normal;min-width:90px;max-width:100px;',
                            'readonly' => false
                     ),
                     'listdeldate' => array(
                            'name' => 'deldate',
                            'field' => 'deldate',
                            'type' => 'date',
                            'label' => 'Delivery Date',
                            'align' => 'text-left',
                            'style' => 'width: 100px;whiteSpace: normal;min-width:100px;max-width:120px;',
                            'readonly' => false
                     ),
                     'inspo' => array(
                            'name' => 'inspo',
                            'field' => 'input',
                            'type' => 'date',
                            'label' => 'Inspo',
                            'align' => 'text-left',
                            'style' => 'width: 100px;whiteSpace: normal;min-width:100px;max-width:120px;',
                            'readonly' => false
                     ),
                     'lockdate' => array(
                            'name' => 'lockdate',
                            'field' => 'lockdate',
                            'type' => 'date',
                            'label' => 'Lock Date',
                            'align' => 'text-left',
                            'style' => 'width: 100px;whiteSpace: normal;min-width:100px;max-width:120px;',
                            'readonly' => false
                     ),
                     'checkerdate' => array(
                            'name' => 'checkerdate',
                            'field' => 'checkerdate',
                            'type' => 'date',
                            'label' => 'Checker Date',
                            'align' => 'text-left',
                            'style' => 'width: 100px;whiteSpace: normal;min-width:100px;max-width:120px;',
                            'readonly' => false
                     ),
                     'scheddate' => array(
                            'name' => 'scheddate',
                            'field' => 'scheddate',
                            'type' => 'date',
                            'label' => 'Schedule Date',
                            'align' => 'text-left',
                            'style' => 'width: 100px;whiteSpace: normal;min-width:100px;max-width:120px;',
                            'readonly' => false
                     ),
                     'dispatchdate' => array(
                            'name' => 'dispatchdate',
                            'field' => 'dispatchdate',
                            'type' => 'date',
                            'label' => 'Dispatch Date',
                            'align' => 'text-left',
                            'style' => 'width: 100px;whiteSpace: normal;min-width:100px;max-width:120px;',
                            'readonly' => false
                     ),
                     'dispatchby' => array(
                            'name' => 'dispatchby',
                            'field' => 'dispatchby',
                            'type' => 'date',
                            'label' => 'Dispatch by',
                            'align' => 'text-left',
                            'style' => 'width: 100px;whiteSpace: normal;min-width:100px;max-width:120px;',
                            'readonly' => false
                     ),
                     'pickerstart' => array(
                            'name' => 'pickerstart',
                            'field' => 'pickerstart',
                            'type' => 'date',
                            'label' => 'Picked time',
                            'align' => 'text-left',
                            'style' => 'width: 100px;whiteSpace: normal;min-width:100px;max-width:120px;',
                            'readonly' => false
                     ),
                     'pickerend' => array(
                            'name' => 'pickerend',
                            'field' => 'pickerend',
                            'type' => 'date',
                            'label' => 'Picked time',
                            'align' => 'text-left',
                            'style' => 'width: 100px;whiteSpace: normal;min-width:100px;max-width:120px;',
                            'readonly' => false
                     ),
                     'listapprovedate' => array(
                            'name' => 'approvedate',
                            'field' => 'approvedate',
                            'type' => 'datetime',
                            'label' => 'Approve Date',
                            'align' => 'text-left',
                            'style' => 'width: 100px;whiteSpace: normal;min-width:100px;max-width:120px;',
                            'readonly' => false
                     ),
                     'liststatus' => array(
                            'name' => 'status',
                            'field' => 'status',
                            'type' => 'input',
                            'label' => 'Status',
                            'align' => 'text-center',
                            'style' => 'width: 80px;whiteSpace: normal;min-width:80px;max-width:80px;',
                            'readonly' => false
                     ),
                     'listcodename' => array(
                            'name' => 'codename',
                            'field' => 'codename',
                            'type' => 'input',
                            'label' => 'Account Name',
                            'align' => 'text-left',
                            'style' => 'width: 100px;whiteSpace: normal;min-width:20px;max-width:100px;',
                            'readonly' => false
                     ),
                     'encodedby' => array(
                            'name' => 'encodedby',
                            'field' => 'encodedby',
                            'type' => 'input',
                            'label' => 'Encodedby',
                            'align' => 'text-left',
                            'style' => 'width: 100px;whiteSpace: normal;min-width:100px;max-width:120px;',
                            'readonly' => true
                     ),
                     'encodeddate' => array(
                            'name' => 'encodeddate',
                            'field' => 'encodeddate',
                            'type' => 'date',
                            'label' => 'Encoded Date',
                            'align' => 'text-left',
                            'style' => 'width: 170px;whiteSpace: normal;min-width:170px;max-width:170px;',
                            'readonly' => true
                     ),
                     'listcreateby' => array(
                            'name' => 'createby',
                            'field' => 'createby',
                            'type' => 'input',
                            'label' => 'Createby',
                            'align' => 'text-left',
                            'style' => 'width: 90px;whiteSpace: normal;min-width:90px;max-width:100px;',
                            'readonly' => false
                     ),
                     'createby' => array(
                            'name' => 'createby',
                            'field' => 'createby',
                            'type' => 'input',
                            'label' => 'Create by',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'listviewby' => array(
                            'name' => 'viewby',
                            'field' => 'viewby',
                            'type' => 'input',
                            'label' => 'Viewby',
                            'align' => 'text-left',
                            'style' => 'width: 90px;whiteSpace: normal;min-width:90px;max-width:100px;',
                            'readonly' => false
                     ),
                     'listeditby' => array(
                            'name' => 'editby',
                            'field' => 'editby',
                            'type' => 'input',
                            'label' => 'Editby',
                            'align' => 'text-left',
                            'style' => 'width: 90px;whiteSpace: normal;min-width:90px;max-width:100px;',
                            'readonly' => false
                     ),
                     'editby' => array(
                            'name' => 'editby',
                            'field' => 'editby',
                            'type' => 'label',
                            'label' => 'Edit By',
                            'align' => 'text-left',
                            'style' => 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px;',
                            'readonly' => false
                     ),
                     'listpostedby' => array(
                            'name' => 'postedby',
                            'field' => 'postedby',
                            'type' => 'input',
                            'label' => 'Posted by',
                            'align' => 'text-left',
                            'style' => 'width: 90px;whiteSpace: normal;min-width:90px;max-width:100px;',
                            'readonly' => false
                     ),
                     'lockuser' => array(
                            'name' => 'lockuser',
                            'field' => 'lockuser',
                            'type' => 'input',
                            'label' => 'Locked by',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'listclient' => array(
                            'name' => 'client',
                            'field' => 'client',
                            'type' => 'input',
                            'label' => 'Code',
                            'align' => 'text-center',
                            'style' => 'width: 150px;whiteSpace: normal;min-width:150px;max-width:160px;text-align:left;',
                            'readonly' => false
                     ),
                     'listclientname' => array(
                            'name' => 'clientname',
                            'field' => 'clientname',
                            'type' => 'input',
                            'label' => 'Name',
                            'align' => 'text-left',
                            'style' => 'width: 280px;whiteSpace: normal;min-width:280px;max-width:290px;text-align:left;',
                            'readonly' => false
                     ),
                     'listplanholder' => array(
                            'name' => 'planholder',
                            'field' => 'planholder',
                            'type' => 'input',
                            'label' => 'Plan Holder',
                            'align' => 'text-left',
                            'style' => 'width: 280px;whiteSpace: normal;min-width:280px;max-width:290px;text-align:left;',
                            'readonly' => false
                     ),
                     'listposition' => array(
                            'name' => 'position',
                            'field' => 'position',
                            'type' => 'input',
                            'label' => 'Position',
                            'align' => 'text-left',
                            'style' => 'width: 80px;whiteSpace: normal;min-width:80px;max-width:90px;text-align:left;',
                            'readonly' => false
                     ),
                     'listsource' => array(
                            'name' => 'source',
                            'field' => 'source',
                            'type' => 'input',
                            'label' => 'Source',
                            'align' => 'text-left',
                            'style' => 'width: 280px;whiteSpace: normal;min-width:280px;max-width:290px;text-align:left;',
                            'readonly' => false
                     ),
                     'clientname' => array(
                            'name' => 'clientname',
                            'field' => 'clientname',
                            'type' => 'input',
                            'label' => 'Supplier Name',
                            'align' => 'text-left',
                            'style' => 'width: 100px;whiteSpace: normal;min-width:20px;max-width:100px;',
                            'readonly' => false
                     ),
                     'approver' => array(
                            'name' => 'approver',
                            'field' => 'approver',
                            'type' => 'input',
                            'label' => 'Users',
                            'align' => 'text-left',
                            'style' => 'width: 100px;whiteSpace: normal;min-width:20px;max-width:100px;',
                            'readonly' => false
                     ),
                     'emplast' => array(
                            'name' => 'emplast',
                            'field' => 'emplast',
                            'type' => 'input',
                            'label' => 'Last Name',
                            'align' => 'text-left',
                            'style' => 'width: 100px;whiteSpace: normal;min-width:20px;max-width:100px;',
                            'readonly' => false
                     ),
                     'empfirst' => array(
                            'name' => 'empfirst',
                            'field' => 'empfirst',
                            'type' => 'input',
                            'label' => 'First Name',
                            'align' => 'text-left',
                            'style' => 'width: 100px;whiteSpace: normal;min-width:20px;max-width:100px;',
                            'readonly' => false
                     ),
                     'empmiddle' => array(
                            'name' => 'empmiddle',
                            'field' => 'empmiddle',
                            'type' => 'input',
                            'label' => 'Middle Name',
                            'align' => 'text-left',
                            'style' => 'width: 100px;whiteSpace: normal;min-width:20px;max-width:100px;',
                            'readonly' => false
                     ),
                     'customer' => array(
                            'name' => 'customer',
                            'field' => 'customer',
                            'type' => 'input',
                            'label' => 'Customer',
                            'align' => 'text-left',
                            'style' => 'width: 100px;whiteSpace: normal;min-width:20px;max-width:100px;',
                            'readonly' => false
                     ),
                     'workloc' => array(
                            'name' => 'workloc',
                            'field' => 'workloc',
                            'type' => 'input',
                            'label' => 'Work Location',
                            'align' => 'text-left',
                            'style' => 'width: 200px;whiteSpace: normal;min-width:200px;max-width:290px;text-align:left;',
                            'readonly' => false
                     ),
                     'workdesc' => array(
                            'name' => 'workdesc',
                            'field' => 'workdesc',
                            'type' => 'input',
                            'label' => 'Work Description',
                            'align' => 'text-left',
                            'style' => 'width: 280px;whiteSpace: normal;min-width:280px;max-width:290px;text-align:left;',
                            'readonly' => false
                     ),
                     'comrate' => array(
                            'name' => 'comrate',
                            'field' => 'comrate',
                            'type' => 'input',
                            'label' => 'Rate',
                            'align' => 'text-left',
                            'style' => 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px;',
                            'readonly' => false
                     ),
                     'agentname' => array(
                            'name' => 'agentname',
                            'field' => 'agentname',
                            'type' => 'label',
                            'label' => 'Agent Name',
                            'align' => 'text-left',
                            'style' => 'width: 100px;whiteSpace: normal;min-width:20px;max-width:100px;',
                            'readonly' => false
                     ),
                     'agentquota' => array(
                            'name' => 'agentquota',
                            'field' => 'agentquota',
                            'type' => 'label',
                            'label' => 'Quota',
                            'align' => 'text-right',
                            'style' => 'text-align:right; width: 100px;whiteSpace: normal;min-width:20px;max-width:100px;',
                            'readonly' => false
                     ),
                     'agentcom' => array(
                            'name' => 'agentcom',
                            'field' => 'agentcom',
                            'type' => 'label',
                            'label' => 'Incentive %',
                            'align' => 'text-right',
                            'style' => 'text-align:right; width: 100px;whiteSpace: normal;min-width:20px;max-width:100px;',
                            'readonly' => false
                     ),
                     'agentcomamt' => array(
                            'name' => 'agentcomamt',
                            'field' => 'agentcomamt',
                            'type' => 'label',
                            'label' => 'Incentive Amount',
                            'align' => 'text-right',
                            'style' => 'text-align:right; width: 100px;whiteSpace: normal;min-width:20px;max-width:100px;',
                            'readonly' => false
                     ),
                     'clientquota' => array(
                            'name' => 'clientquota',
                            'field' => 'clientquota',
                            'type' => 'label',
                            'label' => 'Quota',
                            'align' => 'text-right',
                            'style' => 'text-align:right; width: 100px;whiteSpace: normal;min-width:20px;max-width:100px;',
                            'readonly' => false
                     ),
                     'clientcom' => array(
                            'name' => 'clientcom',
                            'field' => 'clientcom',
                            'type' => 'label',
                            'label' => 'Incentive %',
                            'align' => 'text-right',
                            'style' => 'text-align:right; width: 100px;whiteSpace: normal;min-width:20px;max-width:100px;',
                            'readonly' => false
                     ),
                     'clientcomamt' => array(
                            'name' => 'clientcomamt',
                            'field' => 'clientcomamt',
                            'type' => 'label',
                            'label' => 'Incentive Amount',
                            'align' => 'text-right',
                            'style' => 'text-align:right; width: 100px;whiteSpace: normal;min-width:20px;max-width:100px;',
                            'readonly' => false
                     ),
                     'agtype' => array(
                            'name' => 'agtype',
                            'field' => 'agtype',
                            'type' => 'label',
                            'label' => 'Type',
                            'align' => 'text-left',
                            'style' => 'text-align:left; width: 50px;whiteSpace: normal;min-width:50px;max-width:50px;',
                            'readonly' => false
                     ),
                     'partreqtype' => array(
                            'name' => 'partreqtype',
                            'field' => 'partreqtype',
                            'type' => 'label',
                            'label' => 'Request Type',
                            'align' => 'text-left',
                            'style' => 'text-align:left; width: 50px;whiteSpace: normal;min-width:50px;max-width:50px;',
                            'readonly' => false
                     ),
                     'listaddr' => array(
                            'name' => 'addr',
                            'field' => 'addr',
                            'type' => 'input',
                            'label' => 'Address',
                            'align' => 'text-center',
                            'style' => 'width: 300px;whiteSpace: normal;min-width:300px;max-width:310px;text-align:left;',
                            'readonly' => false
                     ),
                     'listareacode' => array(
                            'name' => 'areacode',
                            'field' => 'areacode',
                            'type' => 'input',
                            'label' => 'Area Code',
                            'align' => 'text-center',
                            'style' => 'width: 300px;whiteSpace: normal;min-width:300px;max-width:310px;text-align:left;',
                            'readonly' => false
                     ),
                     'listarea' => array(
                            'name' => 'area',
                            'field' => 'area',
                            'type' => 'input',
                            'label' => 'Area',
                            'align' => 'text-center',
                            'style' => 'width: 300px;whiteSpace: normal;min-width:300px;max-width:310px;text-align:left;',
                            'readonly' => false
                     ),
                     'area' => array(
                            'name' => 'area',
                            'field' => 'area',
                            'type' => 'input',
                            'label' => 'Area',
                            'align' => 'text-left',
                            'style' => 'width: 120px;whiteSpace: normal;min-width:120px;max-width:130px;',
                            'readonly' => false
                     ),
                     'listbrgy' => array(
                            'name' => 'brgy',
                            'field' => 'brgy',
                            'type' => 'input',
                            'label' => 'Brgy',
                            'align' => 'text-center',
                            'style' => 'width: 300px;whiteSpace: normal;min-width:300px;max-width:310px;text-align:left;',
                            'readonly' => false
                     ),
                     'listregion' => array(
                            'name' => 'region',
                            'field' => 'region',
                            'type' => 'input',
                            'label' => 'Region',
                            'align' => 'text-center',
                            'style' => 'width: 300px;whiteSpace: normal;min-width:300px;max-width:310px;text-align:left;',
                            'readonly' => false
                     ),
                     'listprovince' => array(
                            'name' => 'province',
                            'field' => 'province',
                            'type' => 'input',
                            'label' => 'Province',
                            'align' => 'text-center',
                            'style' => 'width: 300px;whiteSpace: normal;min-width:300px;max-width:310px;text-align:left;',
                            'readonly' => false
                     ),
                     'starttime' => array(
                            'name' => 'starttime',
                            'type' => 'datetime',
                            'label' => 'Start Time',
                            'class' => 'csstarttime',
                            'align' => 'text-center',
                            'style' => 'text-align:right; width: 100px;whiteSpace: normal;min-width:20px;max-width:100px;',
                            'readonly' => false
                     ),
                     'endtime' => array(
                            'name' => 'endtime',
                            'type' => 'datetime',
                            'label' => 'End Time',
                            'class' => 'csendtime',
                            'align' => 'text-center',
                            'style' => 'text-align:right; width: 100px;whiteSpace: normal;min-width:20px;max-width:100px;',
                            'readonly' => false
                     ),
                     'odostart' => array(
                            'name' => 'odostart',
                            'type' => 'input',
                            'label' => 'Meter Start',
                            'class' => 'csodostart',
                            'align' => 'text-center',
                            'style' => 'text-align:right; width: 100px;whiteSpace: normal;min-width:20px;max-width:100px;',
                            'readonly' => false
                     ),
                     'odoend' => array(
                            'name' => 'odoend',
                            'type' => 'input',
                            'label' => 'Meter End',
                            'class' => 'csodoend',
                            'align' => 'text-center',
                            'style' => 'text-align:right; width: 100px;whiteSpace: normal;min-width:20px;max-width:100px;',
                            'readonly' => false
                     ),
                     'distance' => array(
                            'name' => 'distance',
                            'type' => 'input',
                            'label' => 'Distance',
                            'class' => 'csdistance',
                            'align' => 'text-center',
                            'style' => 'text-align:right; width: 100px;whiteSpace: normal;min-width:20px;max-width:100px;',
                            'readonly' => false
                     ),
                     'duration' => array(
                            'name' => 'duration',
                            'type' => 'input',
                            'label' => 'Distance',
                            'class' => 'csduration',
                            'align' => 'text-center',
                            'style' => 'text-align:right; width: 100px;whiteSpace: normal;min-width:20px;max-width:100px;',
                            'readonly' => false
                     ),
                     'fuelconsumption' => array(
                            'name' => 'fuelconsumption',
                            'type' => 'input',
                            'label' => 'Fuel Consumption',
                            'class' => 'csfuelconsumption',
                            'align' => 'text-center',
                            'style' => 'text-align:right; width: 100px;whiteSpace: normal;min-width:20px;max-width:100px;',
                            'readonly' => false
                     ),
                     'channel' => array(
                            'name' => 'channel',
                            'type' => 'input',
                            'label' => 'Channel',
                            'field' => 'channel',
                            'align' => 'text-align:left; width: 100px;whiteSpace: normal;min-width:20px;max-width:100px;',
                            'style' => 'min-width:80px;',
                            'readonly' => false
                     ),
                     'banktype' => array(
                            'name' => 'banktype',
                            'type' => 'input',
                            'label' => 'Bank Type',
                            'field' => 'banktype',
                            'align' => 'text-align:left; width: 100px;whiteSpace: normal;min-width:20px;max-width:100px;',
                            'style' => 'min-width:80px;',
                            'readonly' => false
                     ),
                     'bankrate' => array(
                            'name' => 'bankrate',
                            'type' => 'input',
                            'label' => 'Bank Rate',
                            'field' => 'bankrate',
                            'align' => 'text-align:left; width: 100px;whiteSpace: normal;min-width:20px;max-width:100px;',
                            'style' => 'min-width:80px;',
                            'readonly' => false
                     ),
                     'modepayamt' => array(
                            'name' => 'modepayamt',
                            'type' => 'input',
                            'label' => 'Card Debit Amt',
                            'field' => 'modepayamt',
                            'align' => 'text-align:left; width: 100px;whiteSpace: normal;min-width:20px;max-width:100px;',
                            'style' => 'min-width:80px;',
                            'readonly' => false
                     ),
                     'comap' => array(
                            'name' => 'comap',
                            'type' => 'input',
                            'label' => 'Gross A/P',
                            'field' => 'comap',
                            'align' => 'text-align:left; width: 100px;whiteSpace: normal;min-width:20px;max-width:100px;',
                            'style' => 'min-width:80px;',
                            'readonly' => false
                     ),
                     'cardcharge' => array(
                            'name' => 'cardcharge',
                            'type' => 'input',
                            'label' => 'Card Charge',
                            'field' => 'cardcharge',
                            'align' => 'text-align:left; width: 100px;whiteSpace: normal;min-width:20px;max-width:100px;',
                            'style' => 'min-width:80px;',
                            'readonly' => false
                     ),
                     'comap2' => array(
                            'name' => 'comap2',
                            'type' => 'input',
                            'label' => 'Gross A/P 2',
                            'field' => 'comap2',
                            'align' => 'text-align:left; width: 100px;whiteSpace: normal;min-width:20px;max-width:100px;',
                            'style' => 'min-width:80px;',
                            'readonly' => false
                     ),
                     'netap' => array(
                            'name' => 'netap',
                            'type' => 'input',
                            'label' => 'Net A/P',
                            'field' => 'netap',
                            'align' => 'text-align:left; width: 100px;whiteSpace: normal;min-width:20px;max-width:100px;',
                            'style' => 'min-width:80px;',
                            'readonly' => false
                     ),

                     //ENROLLMENT LIST
                     'listcourse' => array(
                            'name' => 'coursecode',
                            'field' => 'coursecode',
                            'type' => 'input',
                            'label' => 'Course Code',
                            'align' => 'text-left',
                            'style' => 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px;',
                            'readonly' => true
                     ),
                     'listcoursename' => array(
                            'name' => 'coursename',
                            'field' => 'coursename',
                            'type' => 'input',
                            'label' => 'Course Name',
                            'align' => 'text-left',
                            'style' => 'width: 120px;whiteSpace: normal;min-width:120px;max-width:140px;',
                            'readonly' => true
                     ),
                     'listperiod' => array(
                            'name' => 'period',
                            'field' => 'period',
                            'type' => 'input',
                            'label' => 'Period',
                            'align' => 'text-left',
                            'style' => 'width: 120px;whiteSpace: normal;min-width:80px;max-width:80px;',
                            'readonly' => true
                     ),
                     'listsy' => array(
                            'name' => 'sy',
                            'field' => 'sy',
                            'type' => 'input',
                            'label' => 'School Year',
                            'align' => 'text-center',
                            'style' => 'width: 120px;whiteSpace: normal;min-width:80px;max-width:80px;',
                            'readonly' => true
                     ),
                     'liststudent' => [
                            'name' => 'clientname',
                            'field' => 'clientname',
                            'type' => 'input',
                            'label' => 'Student Name',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ],
                     'listlevels' => [
                            'name' => 'levels',
                            'field' => 'levels',
                            'type' => 'input',
                            'label' => 'Level',
                            'align' => 'text-left',
                            'style' => 'width: 120px;whiteSpace: normal;min-width:80px;max-width:80px;',
                            'readonly' => true
                     ],
                     'listyr' => array(
                            'name' => 'yr',
                            'field' => 'yr',
                            'type' => 'input',
                            'label' => 'Year/Grade',
                            'align' => 'text-left',
                            'style' => 'width: 120px;whiteSpace: normal;min-width:80px;max-width:80px;',
                            'readonly' => true
                     ),
                     'listsemester' => array(
                            'name' => 'terms',
                            'field' => 'terms',
                            'type' => 'input',
                            'label' => 'Semester',
                            'align' => 'text-left',
                            'style' => 'width: 120px;whiteSpace: normal;min-width:80px;max-width:80px;',
                            'readonly' => true
                     ),
                     'listsection' => array(
                            'name' => 'section',
                            'field' => 'section',
                            'type' => 'input',
                            'label' => 'Section',
                            'align' => 'text-left',
                            'style' => 'width: 120px;whiteSpace: normal;min-width:80px;max-width:80px;',
                            'readonly' => true
                     ),
                     // GLEN 10.02.2020 
                     'part_code' => array(
                            'name' => 'part_code',
                            'field' => 'part_code',
                            'type' => 'input',
                            'label' => 'Code',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'part_name' => array(
                            'name' => 'part_name',
                            'field' => 'part_name',
                            'type' => 'input',
                            'label' => 'Part Name',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'stock_itemgroup' => array(
                            'name' => 'stock_itemgroup',
                            'field' => 'stock_itemgroup',
                            'type' => 'input',
                            'label' => 'Item Group',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'stockgrp_code' => array(
                            'name' => 'stockgrp_code',
                            'field' => 'stockgrp_code',
                            'type' => 'input',
                            'label' => 'Code',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'stockgrp_name' => array(
                            'name' => 'stockgrp_name',
                            'field' => 'stockgrp_name',
                            'type' => 'input',
                            'label' => 'Group Name',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'brand_desc' => array(
                            'name' => 'brand_desc',
                            'field' => 'brand_desc',
                            'type' => 'input',
                            'label' => 'Brand Name',
                            'align' => 'text-left',
                            'style' => 'width: 120px;whiteSpace: normal;min-width:120px;max-width:130px;text-align:left;',
                            'readonly' => false
                     ),
                     'compatible' => array(
                            'name' => 'compatible',
                            'field' => 'compatible',
                            'type' => 'input',
                            'label' => 'Compatible',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'cl_name' => array(
                            'name' => 'cl_name',
                            'field' => 'cl_name',
                            'type' => 'input',
                            'label' => 'Class Name',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'cat_name' => array(
                            'name' => 'cat_name',
                            'field' => 'cat_name',
                            'type' => 'input',
                            'label' => 'Category Name',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'name' => array(
                            'name' => 'name',
                            'field' => 'name',
                            'type' => 'input',
                            'label' => 'Name',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'code' => array(
                            'name' => 'code',
                            'field' => 'code',
                            'type' => 'input',
                            'label' => 'Code',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'rankcriteria' => array(
                            'name' => 'rankcriteria',
                            'field' => 'rankcriteria',
                            'type' => 'input',
                            'label' => 'Rank',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'lowgrade' => array(
                            'name' => 'lowgrade',
                            'field' => 'lowgrade',
                            'type' => 'input',
                            'label' => 'Low',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'highgrade' => array(
                            'name' => 'highgrade',
                            'field' => 'highgrade',
                            'type' => 'input',
                            'label' => 'High',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'center' => array(
                            'name' => 'center',
                            'field' => 'center',
                            'type' => 'input',
                            'label' => 'Branch',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'min' => array(
                            'name' => 'min',
                            'field' => 'min',
                            'type' => 'input',
                            'label' => 'Minimum',
                            'align' => 'text-left',
                            'style' => 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px;',
                            'readonly' => false
                     ),
                     'max' => array(
                            'name' => 'max',
                            'field' => 'max',
                            'type' => 'input',
                            'label' => 'Maximum',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'address' => array(
                            'name' => 'address',
                            'field' => 'address',
                            'type' => 'input',
                            'label' => 'Address',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'tel' => array(
                            'name' => 'tel',
                            'field' => 'tel',
                            'type' => 'input',
                            'label' => 'Telephone',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'tin' => array(
                            'name' => 'tin',
                            'field' => 'tin',
                            'type' => 'input',
                            'label' => 'Tin #',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'zipcode' => array(
                            'name' => 'zipcode',
                            'field' => 'zipcode',
                            'type' => 'input',
                            'label' => 'Zipcode',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'station' => array(
                            'name' => 'station',
                            'field' => 'station',
                            'type' => 'input',
                            'label' => 'Station',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'warehouse' => array(
                            'name' => 'warehouse',
                            'type' => 'lookup',
                            'label' => 'Warehouse',
                            'field' => 'warehouse',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => true,
                            'lookupclass' => 'whstock',
                            'action' => 'lookupclient'
                     ),
                     'ismain' => array(
                            'name' => 'ismain',
                            'type' => 'toggle',
                            'label' => 'Main',
                            'field' => 'ismain',
                            'align' => 'text-left',
                            'style' => 'width:20px;whiteSpace: normal;min-width:30px;',
                            'readonly' => false
                     ),
                     'ismanual' => array(
                            'name' => 'ismanual',
                            'type' => 'toggle',
                            'label' => 'Manual',
                            'field' => 'ismanual',
                            'align' => 'text-left',
                            'style' => 'width:20px;whiteSpace: normal;min-width:30px;',
                            'readonly' => false
                     ),
                     'isprefer' => array(
                            'name' => 'isprefer',
                            'type' => 'toggle',
                            'label' => 'Preferred',
                            'field' => 'isprefer',
                            'align' => 'text-left',
                            'style' => 'width:20px;whiteSpace: normal;min-width:30px;',
                            'readonly' => false
                     ),
                     'isinactive' => array(
                            'name' => 'isinactive',
                            'type' => 'toggle',
                            'label' => 'Inactive',
                            'field' => 'isinactive',
                            'align' => 'text-left',
                            'style' => 'width:20px;whiteSpace: normal;min-width:30px;',
                            'readonly' => false
                     ),
                     'isnopay' => array(
                            'name' => 'isnopay',
                            'type' => 'toggle',
                            'label' => 'Without Pay',
                            'field' => 'isnopay',
                            'align' => 'text-left',
                            'style' => 'width:20px;whiteSpace: normal;min-width:30px;',
                            'readonly' => false
                     ),
                     'isliquidation' => array(
                            'name' => 'isliquidation',
                            'type' => 'toggle',
                            'label' => 'Subject For Liquidation',
                            'field' => 'isliquidation',
                            'align' => 'text-left',
                            'style' => 'width:20px;whiteSpace: normal;min-width:30px;',
                            'readonly' => false
                     ),
                     'activestat' => array(
                            'name' => 'activestat',
                            'type' => 'input',
                            'label' => 'Status',
                            'field' => 'activestat',
                            'align' => 'text-left',
                            'style' => 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px;',
                            'readonly' => false
                     ),
                     'inactive' => array(
                            'name' => 'inactive',
                            'type' => 'toggle',
                            'label' => 'Inactive',
                            'field' => 'inactive',
                            'align' => 'text-left',
                            'style' => 'width:20px;whiteSpace: normal;min-width:30px;',
                            'readonly' => false
                     ),
                     'pvalue' => array(
                            'name' => 'pvalue',
                            'field' => 'pvalue',
                            'type' => 'input',
                            'label' => 'Prefix',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'psection' => array(
                            'name' => 'psection',
                            'field' => 'psection',
                            'type' => 'input',
                            'label' => 'Module',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => true
                     ),
                     'wstart' => array(
                            'name' => 'wstart',
                            'field' => 'wstart',
                            'type' => 'input',
                            'label' => "Previous Reading",
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;max-width:100px;table-layout: fixed;',
                            'readonly' => false
                     ),
                     'wend' => array(
                            'name' => 'wend',
                            'field' => 'wend',
                            'type' => 'input',
                            'label' => "Present Reading",
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;max-width:100px;table-layout: fixed;',
                            'readonly' => false
                     ),
                     'consump' => array(
                            'name' => 'consump',
                            'field' => 'consump',
                            'type' => 'input',
                            'label' => "Consumption",
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;max-width:100px;table-layout: fixed;',
                            'readonly' => false
                     ),
                     'consignee' => array(
                            'name' => 'consignee',
                            'field' => 'consignee',
                            'type' => 'input',
                            'label' => "Consignee",
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;max-width:150px;table-layout: fixed;',
                            'readonly' => false
                     ),
                     'rate' => array(
                            'name' => 'rate',
                            'field' => 'rate',
                            'type' => 'input',
                            'label' => "Rates (%) [Note: Please enter rate in numbers / decimals]",
                            'align' => 'text-left',
                            'style' => 'width:15%;whiteSpace: normal;min-width:20px;max-width:30px;table-layout: fixed;',
                            'readonly' => false
                     ),
                     'description' => array(
                            'name' => 'description',
                            'field' => 'description',
                            'type' => 'input',
                            'label' => 'Description',
                            'align' => 'text-left',
                            'style' => 'width: 200px;whiteSpace: normal;min-width:20px;max-width:200px;',
                            'readonly' => false
                     ),
                     'position' => array(
                            'name' => 'position',
                            'field' => 'position',
                            'type' => 'input',
                            'label' => 'Position',
                            'align' => 'text-left',
                            'style' => 'width: 200px;whiteSpace: normal;min-width:20px;max-width:200px;',
                            'readonly' => false
                     ),
                     'street' => array(
                            'name' => 'street',
                            'field' => 'street',
                            'type' => 'input',
                            'label' => 'Street List',
                            'align' => 'text-left',
                            'style' => 'width: 200px;whiteSpace: normal;min-width:20px;max-width:200px;',
                            'readonly' => false
                     ),
                     'clearance' => array(
                            'name' => 'clearance',
                            'field' => 'clearance',
                            'type' => 'input',
                            'label' => 'Local Clearance Lists',
                            'align' => 'text-left',
                            'style' => 'width: 200px;whiteSpace: normal;min-width:20px;max-width:200px;',
                            'readonly' => false
                     ),
                     'businesstype' => array(
                            'name' => 'businesstype',
                            'field' => 'businesstype',
                            'type' => 'input',
                            'label' => 'Business Type',
                            'align' => 'text-left',
                            'style' => 'width: 200px;whiteSpace: normal;min-width:20px;max-width:200px;',
                            'readonly' => false
                     ),
                     'product' => array(
                            'name' => 'product',
                            'field' => 'product',
                            'type' => 'input',
                            'label' => 'Product',
                            'align' => 'text-left',
                            'style' => 'width: 200px;whiteSpace: normal;min-width:20px;max-width:200px;',
                            'readonly' => false
                     ),
                     'presenter' => array(
                            'name' => 'presenter',
                            'field' => 'presenter',
                            'type' => 'input',
                            'label' => 'Presenter',
                            'align' => 'text-left',
                            'style' => 'width: 200px;whiteSpace: normal;min-width:20px;max-width:200px;',
                            'readonly' => false
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
                     'paymenttype' => array(
                            'name' => 'paymenttype',
                            'field' => 'paymenttype',
                            'type' => 'label',
                            'label' => 'Payment Type',
                            'align' => 'text-left',
                            'style' => 'width: 100px;whiteSpace: normal;min-width:20px;max-width:100px;',
                            'readonly' => false
                     ),
                     'startdate' => array(
                            'name' => 'startdate',
                            'field' => 'startdate',
                            'type' => 'date',
                            'label' => 'From Date',
                            'align' => 'text-left',
                            'style' => 'width: 200px;whiteSpace: normal;min-width:20px;max-width:200px;',
                            'readonly' => false
                     ),
                     'enddate' => array(
                            'name' => 'enddate',
                            'field' => 'enddate',
                            'type' => 'date',
                            'label' => 'End Date',
                            'align' => 'text-left',
                            'style' => 'width: 200px;whiteSpace: normal;min-width:20px;max-width:200px;',
                            'readonly' => false
                     ),
                     'approveqty' => array(
                            'name' => 'approveqty',
                            'type' => 'input',
                            'label' => 'Approved Qty',
                            'field' => 'approveqty',
                            'align' => 'text-right',
                            'style' => 'width:90px;whiteSpace: normal;min-width:90px;',
                            'readonly' => false
                     ),

                     // GLEN 10.02.2020
                     'model_name' => array(
                            'name' => 'model_name',
                            'field' => 'model_name',
                            'type' => 'input',
                            'label' => 'Model Name',
                            'align' => 'text-left',
                            'style' => 'width: 150px;whiteSpace: normal;min-width:150px;max-width:160px;text-align:left;',
                            'readonly' => false
                     ),
                     'model_code' => array(
                            'name' => 'model_code',
                            'field' => 'model_code',
                            'type' => 'input',
                            'label' => 'Code',
                            'align' => 'text-left',
                            'style' => 'width: 90px;whiteSpace: normal;min-width:90px;max-width:90px;',
                            'readonly' => false
                     ),
                     'model' => array(
                            'name' => 'model',
                            'field' => 'model',
                            'type' => 'input',
                            'label' => 'Model',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'part_code' => array(
                            'name' => 'part_code',
                            'field' => 'part_code',
                            'type' => 'input',
                            'label' => 'Code',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'part_name' => array(
                            'name' => 'part_name',
                            'field' => 'part_name',
                            'type' => 'input',
                            'label' => 'Part Name',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'stockgrp_code' => array(
                            'name' => 'stockgrp_code',
                            'field' => 'stockgrp_code',
                            'type' => 'input',
                            'label' => 'Code',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'stockgrp_name' => array(
                            'name' => 'stockgrp_name',
                            'field' => 'stockgrp_name',
                            'type' => 'input',
                            'label' => 'Group Name',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'brand_desc' => array(
                            'name' => 'brand_desc',
                            'field' => 'brand_desc',
                            'type' => 'input',
                            'label' => 'Brand Name',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'brand' => array(
                            'name' => 'brand',
                            'field' => 'brand',
                            'type' => 'input',
                            'label' => 'Brand',
                            'align' => 'text-left',
                            'style' => 'width: 120px;whiteSpace: normal;min-width:120px;max-width:130px;text-align:left;',
                            'readonly' => false
                     ),
                     'cl_name' => array(
                            'name' => 'cl_name',
                            'field' => 'cl_name',
                            'type' => 'input',
                            'label' => 'Class Name',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'cat_name' => array(
                            'name' => 'cat_name',
                            'field' => 'cat_name',
                            'type' => 'input',
                            'label' => 'Category Name',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'subcat_name' => array(
                            'name' => 'subcat_name',
                            'field' => 'subcat_name',
                            'type' => 'input',
                            'label' => 'Sub Category',
                            'align' => 'text-left',
                            'style' => 'width: 200px;whiteSpace: normal;min-width:200px;max-width:200px;',
                            'readonly' => false
                     ),
                     'name' => array(
                            'name' => 'name',
                            'field' => 'name',
                            'type' => 'input',
                            'label' => 'Name',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'code' => array(
                            'name' => 'code',
                            'field' => 'code',
                            'type' => 'input',
                            'label' => 'Code',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'min' => array(
                            'name' => 'min',
                            'field' => 'min',
                            'type' => 'input',
                            'label' => 'Minimum',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'max' => array(
                            'name' => 'max',
                            'field' => 'max',
                            'type' => 'input',
                            'label' => 'Maximum',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'address' => array(
                            'name' => 'address',
                            'field' => 'address',
                            'type' => 'input',
                            'label' => 'Address',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'tel' => array(
                            'name' => 'tel',
                            'field' => 'tel',
                            'type' => 'input',
                            'label' => 'Telephone',
                            'align' => 'text-left',
                            'style' => 'width: 50px;whiteSpace: normal;min-width:50px;max-width:50px;',
                            'readonly' => false
                     ),
                     'tin' => array(
                            'name' => 'tin',
                            'field' => 'tin',
                            'type' => 'input',
                            'label' => 'Tin #',
                            'align' => 'text-left',
                            'style' => 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px;',
                            'readonly' => false
                     ),
                     'zipcode' => array(
                            'name' => 'zipcode',
                            'field' => 'zipcode',
                            'type' => 'input',
                            'label' => 'Zipcode',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'station' => array(
                            'name' => 'station',
                            'field' => 'station',
                            'type' => 'input',
                            'label' => 'Station',
                            'align' => 'text-left',
                            'style' => 'width: 150px;whiteSpace: normal;min-width:150px;max-width:180px;',
                            'readonly' => false
                     ),
                     'warehouse' => array(
                            'name' => 'warehouse',
                            'type' => 'lookup',
                            'label' => 'Warehouse',
                            'field' => 'warehouse',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => true,
                            'lookupclass' => 'whstock',
                            'action' => 'lookupclient'
                     ),
                     'pvalue' => array(
                            'name' => 'pvalue',
                            'field' => 'pvalue',
                            'type' => 'input',
                            'label' => 'Prefix',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'psection' => array(
                            'name' => 'psection',
                            'field' => 'psection',
                            'type' => 'input',
                            'label' => 'Module',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => true
                     ),
                     'rate' => array(
                            'name' => 'rate',
                            'field' => 'rate',
                            'type' => 'input',
                            'label' => "Rates (%) [Note: Please enter rate in numbers / decimals]",
                            'align' => 'text-right',
                            'style' => 'width:15%;whiteSpace: normal;min-width:20px;max-width:30px;table-layout: fixed;',
                            'readonly' => false
                     ),
                     'userid' => array(
                            'name' => 'userid',
                            'field' => 'userid',
                            'type' => 'input',
                            'label' => 'Username',
                            'align' => 'text-left',
                            'style' => 'width: 200px;whiteSpace: normal;min-width:20px;max-width:200px;',
                            'readonly' => false
                     ),
                     'user' => array(
                            'name' => 'user',
                            'field' => 'user',
                            'type' => 'label',
                            'label' => 'Username',
                            'align' => 'text-left',
                            'style' => 'width: 200px;whiteSpace: normal;min-width:20px;max-width:200px;',
                            'readonly' => false
                     ),
                     'task' => array(
                            'name' => 'task',
                            'field' => 'task',
                            'type' => 'input',
                            'label' => 'Task',
                            'align' => 'text-left',
                            'style' => 'width: 200px;whiteSpace: normal;min-width:20px;max-width:200px;',
                            'readonly' => false
                     ),
                     'oldversion' => array(
                            'name' => 'oldversion',
                            'field' => 'oldversion',
                            'type' => 'input',
                            'label' => 'Activity',
                            'align' => 'text-left',
                            'style' => 'width: 200px;whiteSpace: normal;min-width:20px;max-width:200px;',
                            'readonly' => false
                     ),
                     'counts' => array(
                            'name' => 'counts',
                            'field' => 'counts',
                            'type' => 'input',
                            'label' => 'Count',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => true
                     ),
                     'doc' => array(
                            'name' => 'doc',
                            'field' => 'doc',
                            'type' => 'input',
                            'label' => 'Document',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => true
                     ),
                     'minimum' => array(
                            'name' => 'minimum',
                            'field' => 'minimum',
                            'type' => 'input',
                            'label' => 'Minimum',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'maximum' => array(
                            'name' => 'maximum',
                            'field' => 'maximum',
                            'type' => 'input',
                            'label' => 'Maximum',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'brandname' => array(
                            'name' => 'brandname',
                            'type' => 'lookup',
                            'label' => 'Brand',
                            'class' => 'csbrand sbccsreadonly',
                            'lookupclass' => 'lookupbrand',
                            'action' => 'lookupbrand',
                            'readonly' => true,
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'required' => false
                     ),
                     'brandid' => array(
                            'name' => 'brand',
                            'type' => 'input',
                            'class' => 'csbrand sbccsreadonly',
                            'lookupclass' => 'lookupbrand',
                            'action' => 'lookupbrand',
                            'style' => 'display:none'
                     ),
                     'classid' => array(
                            'name' => 'class',
                            'type' => 'hidden',
                            'class' => 'csclass sbccsreadonly',
                            'lookupclass' => 'lookupclass',
                            'action' => 'lookupclass',
                            'style' => 'display:none'
                     ),
                     'classname' => array(
                            'name' => 'classname',
                            'type' => 'lookup',
                            'label' => 'Class',
                            'class' => 'csclass sbccsreadonly',
                            'lookupclass' => 'lookupclass',
                            'action' => 'lookupclass',
                            'readonly' => true,
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'required' => false
                     ),
                     'stock_groupid' => array(
                            'name' => 'groupid',
                            'type' => 'hidden',
                            'class' => 'csdgroupid sbccsreadonly',
                            'lookupclass' => 'lookupgroup',
                            'action' => 'lookupdivision',
                            'style' => 'display:none'
                     ),
                     'stock_groupname' => array(
                            'name' => 'stock_groupname',
                            'type' => 'lookup',
                            'label' => 'Group',
                            'class' => 'csdgroupid sbccsreadonly',
                            'lookupclass' => 'lookupgroup',
                            'action' => 'lookupdivision',
                            'readonly' => true,
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'required' => false
                     ),
                     'groupname' => array(
                            'name' => 'groupname',
                            'type' => 'input',
                            'label' => 'Group Name',
                            'class' => 'csgroupname',
                            'style' => 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px;',
                            'required' => false
                     ),
                     'subgroup' => array(
                            'name' => 'subgroup',
                            'type' => 'input',
                            'label' => 'Sub Group Name',
                            'class' => 'cssubgroup',
                            'style' => 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px;',
                            'required' => false,
                            'align' => 'text-left'
                     ),
                     'leader' => array(
                            'name' => 'leader',
                            'type' => 'input',
                            'label' => 'Leader',
                            'class' => 'csleader',
                            'style' => 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px;',
                            'required' => false
                     ),
                     'leadfrom' => array(
                            'name' => 'leadfrom',
                            'type' => 'input',
                            'label' => 'Lead Time From',
                            'class' => 'csleadfrom',
                            'align' => 'text-left',
                            'style' => 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px;',
                            'required' => false
                     ),
                     'leadto' => array(
                            'name' => 'leadto',
                            'type' => 'input',
                            'label' => 'Lead Time To',
                            'class' => 'csleadto',
                            'align' => 'text-left',
                            'style' => 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px;',
                            'required' => false
                     ),
                     'leaddur' => array(
                            'name' => 'leaddur',
                            'type' => 'input',
                            'label' => 'Lead Time Duration',
                            'class' => 'csleaddur',
                            'style' => 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px;',
                            'required' => false
                     ),
                     'partid' => array(
                            'name' => 'part',
                            'type' => 'hidden',
                            'class' => 'cspart sbccsreadonly',
                            'lookupclass' => 'lookuppart',
                            'action' => 'lookuppart',
                            'style' => 'display:none'
                     ),
                     'partname' => array(
                            'name' => 'partname',
                            'type' => 'lookup',
                            'label' => 'Part',
                            'class' => 'cspart sbccsreadonly',
                            'lookupclass' => 'lookuppart',
                            'action' => 'lookuppart',
                            'readonly' => true,
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'required' => false
                     ),
                     'partno' => array(
                            'name' => 'partno',
                            'field' => 'partno',
                            'type' => 'input',
                            'label' => 'SKU/Part No.',
                            'class' => 'cspartno sbccsreadonly',
                            'readonly' => true,
                            'align' => 'text-left',
                            'style' => 'width: 300px;whiteSpace: normal;min-width:300px;max-width:300px;text-align:left;',
                            'required' => false
                     ),
                     'modelid' => array(
                            'name' => 'model',
                            'type' => 'hidden',
                            'class' => 'csmodel sbccsreadonly',
                            'lookupclass' => 'lookupmodel',
                            'action' => 'lookupmodel',
                            'style' => 'display:none'
                     ),
                     'modelname' => array(
                            'name' => 'modelname',
                            'type' => 'lookup',
                            'label' => 'Model',
                            'class' => 'csmodel sbccsreadonly',
                            'lookupclass' => 'lookupmodel',
                            'action' => 'lookupmodel',
                            'readonly' => true,
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'required' => false
                     ),
                     'sizeid' => array(
                            'name' => 'sizeid',
                            'type' => 'lookup',
                            'label' => 'Size',
                            'class' => 'cssize sbccsenablealways',
                            'lookupclass' => 'lookupsize',
                            'action' => 'lookupsize',
                            'readonly' => true,
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'required' => false
                     ),

                     //HMS
                     'listroomtype' => array(
                            'name' => 'roomtype',
                            'field' => 'roomtype',
                            'type' => 'input',
                            'label' => 'Room Type',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'listcategory' => array(
                            'name' => 'category',
                            'field' => 'category',
                            'type' => 'input',
                            'label' => 'Category',
                            'align' => 'text-left',
                            'style' => 'width: 190px;whiteSpace: normal;min-width:190px;max-width:200px;',
                            'readonly' => false
                     ),
                     'category' => array(
                            'name' => 'category',
                            'field' => 'category',
                            'type' => 'input',
                            'label' => 'Category',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'packname' => array(
                            'name' => 'packname',
                            'field' => 'packname',
                            'type' => 'input',
                            'label' => 'Description',
                            'align' => 'text-left',
                            'style' => 'width: 200px;whiteSpace: normal;min-width:20px;max-width:200px;',
                            'readonly' => false
                     ),
                     'roomno' => array(
                            'name' => 'roomno',
                            'field' => 'roomno',
                            'type' => 'input',
                            'label' => 'Room No.',
                            'align' => 'text-left',
                            'style' => 'width: 200px;whiteSpace: normal;min-width:20px;max-width:200px;',
                            'readonly' => false
                     ),
                     'isdefault' => array(
                            'name' => 'isdefault',
                            'type' => 'toggle',
                            'label' => 'Default',
                            'field' => 'isdefault',
                            'align' => 'text-left',
                            'style' => 'width:40px;whiteSpace: normal;min-width:40px;',
                            'readonly' => false
                     ),
                     'isdefault2' => array(
                            'name' => 'isdefault2',
                            'type' => 'toggle',
                            'label' => 'Default',
                            'field' => 'isdefault2',
                            'align' => 'text-left',
                            'style' => 'width:40px;whiteSpace: normal;min-width:40px;',
                            'readonly' => false
                     ),
                     'issales' => array(
                            'name' => 'issales',
                            'type' => 'toggle',
                            'label' => 'Allow in SO/SJ',
                            'field' => 'issales',
                            'align' => 'text-left',
                            'style' => 'width:40px;whiteSpace: normal;min-width:40px;',
                            'readonly' => false
                     ),
                     'issalesdef' => array(
                            'name' => 'issalesdef',
                            'type' => 'toggle',
                            'label' => 'Default in SO/SJ',
                            'field' => 'issalesdef',
                            'align' => 'text-left',
                            'style' => 'width:40px;whiteSpace: normal;min-width:40px;',
                            'readonly' => false
                     ),
                     // end of HMS


                     'orderlevels' => array(
                            'name' => 'orderlevels',
                            'field' => 'orderlevels',
                            'type' => 'input',
                            'label' => 'OrderLevels',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'term' => array(
                            'name' => 'term',
                            'field' => 'term',
                            'type' => 'input',
                            'label' => 'Term',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'orderterm' => array(
                            'name' => 'orderterm',
                            'field' => 'orderterm',
                            'type' => 'input',
                            'label' => 'Order',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'buildingcode' => array(
                            'name' => 'buildingcode',
                            'field' => 'buildingcode',
                            'type' => 'input',
                            'label' => 'Bldg. Code',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'buildingname' => array(
                            'name' => 'buildingname',
                            'field' => 'buildingname',
                            'type' => 'input',
                            'label' => 'Bldg. Name',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'lbldgcode' => array(
                            'name' => 'lbldgcode',
                            'type' => 'lookup',
                            'label' => 'Bldg Code',
                            'field' => 'bldgcode',
                            'align' => 'text-left',
                            'style' => 'text-align:right;width:180px;whiteSpace: normal;min-width:180px;',
                            'readonly' => true,
                            'lookupclass' => 'lookupbldg',
                            'action' => 'lookupbldg'
                     ),
                     'lroomcode' => array(
                            'name' => 'lroomcode',
                            'type' => 'lookup',
                            'label' => 'Room',
                            'field' => 'roomcode',
                            'align' => 'text-left',
                            'style' => 'text-align:right;width:180px;whiteSpace: normal;min-width:180px;',
                            'readonly' => true,
                            'lookupclass' => 'lookuprooms',
                            'action' => 'lookuprooms'
                     ),
                     'roomcode' => array(
                            'name' => 'roomcode',
                            'field' => 'roomcode',
                            'type' => 'input',
                            'label' => 'Room Code',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'roomname' => array(
                            'name' => 'roomname',
                            'field' => 'roomname',
                            'type' => 'input',
                            'label' => 'Room Name',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'roomname' => array(
                            'name' => 'roomname',
                            'field' => 'roomname',
                            'type' => 'input',
                            'label' => 'Room Name',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'section' => array(
                            'name' => 'section',
                            'field' => 'section',
                            'type' => 'lookup',
                            'label' => 'Section',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false,
                            'lookupclass' => 'lookupdsection',
                            'action' => 'lookupdsection'
                     ),
                     'times' => array(
                            'name' => 'times',
                            'field' => 'times',
                            'type' => 'input',
                            'label' => 'Times',
                            'align' => 'text-left',
                            'style' => 'width:20px;whiteSpace:normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'order' => array(
                            'name' => 'order',
                            'field' => 'order',
                            'type' => 'input',
                            'label' => 'Order',
                            'align' => 'text-left',
                            'style' => 'width:20px;whiteSpace:normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'studenttype' => array(
                            'name' => 'studenttype',
                            'field' => 'studenttype',
                            'type' => 'input',
                            'label' => 'Student Type',
                            'align' => 'text-left',
                            'style' => 'width:20px;whiteSpace: normal;min-width:30px;',
                            'readonly' => false
                     ),
                     'requirements' => array(
                            'name' => 'requirements',
                            'field' => 'requirements',
                            'type' => 'input',
                            'label' => 'Requirement',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'linstructorcode' => array(
                            'name' => 'linstructorcode',
                            'type' => 'lookup',
                            'label' => 'Instructor',
                            'field' => 'instructorcode',
                            'align' => 'text-left',
                            'style' => 'text-align:right;width:180px;whiteSpace: normal;min-width:180px;',
                            'readonly' => true,
                            'lookupclass' => 'lookupinstructor',
                            'action' => 'lookupinstructor'
                     ),
                     'eainstructorcode' => array(
                            'name' => 'eainstructorcode',
                            'type' => 'lookup',
                            'label' => 'Instructor',
                            'field' => 'instructorcode',
                            'align' => 'text-left',
                            'style' => 'text-align:right;width:180px;whiteSpace: normal;min-width:180px;',
                            'readonly' => true,
                            'lookupclass' => 'lookupschedinstructor',
                            'action' => 'lookupinstructor'
                     ),
                     'teachercode' => array(
                            'name' => 'teachercode',
                            'field' => 'teachercode',
                            'type' => 'input',
                            'label' => 'Instructor Code',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'teachername' => array(
                            'name' => 'teachername',
                            'field' => 'teachername',
                            'type' => 'input',
                            'label' => 'Instructor Name',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'deptname' => array(
                            'name' => 'deptname',
                            'field' => 'deptname',
                            'type' => 'input',
                            'label' => 'Department',
                            'align' => 'text-left',
                            'style' => 'width: 150px;whiteSpace: normal;min-width:150px;max-width:160px;',
                            'readonly' => true
                     ),
                     'department' => array(
                            'name' => 'department',
                            'field' => 'department',
                            'type' => 'input',
                            'label' => 'Department',
                            'align' => 'text-left',
                            'style' => 'width: 150px;whiteSpace: normal;min-width:150px;max-width:160px;',
                            'readonly' => true
                     ),
                     'deptcode' => array(
                            'name' => 'deptcode',
                            'field' => 'deptcode',
                            'type' => 'lookup',
                            'label' => 'Department Code',
                            'align' => 'text-left',
                            'style' => 'width: 110px;whiteSpace: normal;min-width:110px;
                                  max-width:110px;',
                            'readonly' => true,
                            'lookupclass' => 'lookupdepartment',
                            'action' => 'lookupdepartment'
                     ),
                     'callname' => array(
                            'name' => 'callname',
                            'field' => 'callname',
                            'type' => 'input',
                            'label' => 'Call Name',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'signatory' => array(
                            'name' => 'signatory',
                            'field' => 'signatory',
                            'type' => 'input',
                            'label' => 'Signatory',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'parentcode' => array(
                            'name' => 'parentcode',
                            'field' => 'parentcode',
                            'type' => 'lookup',
                            'label' => 'Parent Department Code',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'readonly' => true,
                            'lookupclass' => 'lookupparentdepartment',
                            'action' => 'lookupdepartment'
                     ),
                     'parentname' => array(
                            'name' => 'parentname',
                            'field' => 'parentname',
                            'type' => 'input',
                            'label' => 'Parent Department Name',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'readonly' => true,
                     ),
                     'fund' => array(
                            'name' => 'fund',
                            'field' => 'fund',
                            'type' => 'input',
                            'label' => 'Fund',
                            'align' => 'text-right',
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'readonly' => true,
                     ),
                     'orderdept' => array(
                            'name' => 'orderdept',
                            'field' => 'orderdept',
                            'type' => 'input',
                            'label' => 'Order No',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'readonly' => true,
                     ),
                     'lfeescode' => array(
                            'name' => 'lfeescode',
                            'field' => 'feescode',
                            'type'  => 'lookup',
                            'label' => 'Code',
                            'align' => 'text-right',
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'readonly' => true,
                            'lookupclass' => 'lookupfeesgrid',
                            'action' => 'lookupfeesgrid'
                     ),
                     'feescode' => array(
                            'name' => 'feescode',
                            'field' => 'feescode',
                            'type'  => 'input',
                            'label' => 'Code',
                            'align' => 'text-right',
                            'style' => 'width: 80px;whiteSpace: normal;
                                  min-width:80px;max-width:80px;',
                            'readonly' => false
                     ),
                     'feesdesc' => array(
                            'name' => 'feesdesc',
                            'field' => 'feesdesc',
                            'type'  => 'input',
                            'label' => 'Description',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'feestype' => array(
                            'name' => 'feestype',
                            'field' => 'feestype',
                            'type'  => 'input',
                            'label' => 'Type',
                            'align' => 'text-left',
                            'style' => 'width: 80px;whiteSpace: normal;
                                  min-width:80px;max-width:80px;',
                            'readonly' => false,
                     ),
                     'vat' => array(
                            'name' => 'vat',
                            'field' => 'vat',
                            'type'  => 'input',
                            'label' => 'Vat',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'purchase' => array(
                            'name' => 'purchase',
                            'field' => 'purchase',
                            'type'  => 'input',
                            'label' => 'Purchase',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'credentials' => array(
                            'name' => 'credentials',
                            'field' => 'credentials',
                            'type'  => 'input',
                            'label' => 'Credentials',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'credentialcode' => array(
                            'name' => 'credentialcode',
                            'field' => 'credentialcode',
                            'type'  => 'input',
                            'label' => 'Credentials Code',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'camt' => array(
                            'name' => 'camt',
                            'field' => 'camt',
                            'type'  => 'input',
                            'label' => 'Computed Amount',
                            'align' => 'text-right', //alignment of text inside the grid
                            'style' => 'text-align:center;width: 100px;whiteSpace: normal; min-width:100px;max-width:100px;', //alignment of column name
                            'readonly' => false
                     ),
                     'particulars' => array(
                            'name' => 'particulars',
                            'field' => 'particulars',
                            'type'  => 'input',
                            'label' => 'Particulars',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'percentdisc' => array(
                            'name' => 'percentdisc',
                            'field' => 'percentdisc',
                            'type'  => 'input',
                            'label' => 'Percentage',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'amount' => array(
                            'name' => 'amount',
                            'field' => 'amount',
                            'type'  => 'input',
                            'label' => 'Amount',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'deancode' => array(
                            'name' => 'deancode',
                            'field' => 'deancode',
                            'type' => 'lookup',
                            'label' => 'Dean Code',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'readonly' => true,
                            'lookupclass' => 'enlookupdean',
                            'action' => 'enlookupdean'
                     ),
                     'deanname' => array(
                            'name' => 'deanname',
                            'field' => 'deanname',
                            'type' => 'input',
                            'label' => 'Dean Name',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'readonly' => true,
                            'lookupclass' => 'enlookupdean',
                            'action' => 'enlookupdean'
                     ),
                     'rank' => array(
                            'name' => 'rank',
                            'field' => 'rank',
                            'type' => 'input',
                            'label' => 'Rank',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'readonly' => false,
                            'lookupclass' => 'lookuprank',
                            'action' => 'lookuprank'
                     ),
                     'attendancetype' => array(
                            'name' => 'attendancetype',
                            'field' => 'attendancetype',
                            'type' => 'input',
                            'label' => 'Type',
                            'align' => 'text-left',
                            'style' => 'width:110px; whiteSpace:normal; min-width:110px; max-width:140px;',
                            'readonly' => false,
                     ),
                     'attendeecount' => array(
                            'name' => 'attendeecount',
                            'field' => 'attendeecount',
                            'type' => 'input',
                            'label' => 'Attendee Count',
                            'align' => 'text-left',
                            'style' => 'width:110px; whiteSpace:normal; min-width:110px; max-width:140px;',
                            'readonly' => false,
                     ),
                     'attendancecolor' => array(
                            'name' => 'attendancecolor',
                            'field' => 'attendancecolor',
                            'type' => 'input',
                            'label' => 'Color',
                            'align' => 'text-left',
                            'style' => 'width:110px; whiteSpace:normal; min-width:110px; max-width:140px;',
                            'readonly' => false
                     ),
                     'levels' => array(
                            'name' => 'levels',
                            'field' => 'levels',
                            'type' => 'lookup',
                            'label' => 'Level',
                            'align' => 'text-left',
                            'style' => 'width: 110px;whiteSpace: normal;
                                  min-width:110px;max-width:140px;',
                            'readonly' => false,
                            'lookupclass' => 'lookuplevel',
                            'action' => 'lookuplevel'
                     ),
                     'level' => array(
                            'name' => 'level',
                            'field' => 'level',
                            'type' => 'lookup',
                            'label' => 'Level',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'readonly' => false,
                            'lookupclass' => 'lookupdeptlevel',
                            'action' => 'lookuplevel'
                     ),
                     'telno' => array(
                            'name' => 'telno',
                            'field' => 'telno',
                            'type' => 'input',
                            'label' => 'Telephone',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'colorselect' => array(
                            'name' => 'color',
                            'label' => 'Color',
                            'type' => 'qselect',
                            'style' => 'width: 80px;whiteSpace: normal;min-width:80px;max-width:80px;',
                            'options' => array(
                                   'purple',
                                   'green',
                                   'red',
                                   'yellow',
                                   'orange'
                            )
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
                     'isdegree' => array(
                            'name' => 'isdegree',
                            'field' => 'isdegree',
                            'type' => 'input',
                            'label' => 'Degree',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'isundergraduate' => array(
                            'name' => 'isundergraduate',
                            'field' => 'isundergraduate',
                            'type' => 'input',
                            'label' => 'Under Graduate',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'bldgname' => array(
                            'name' => 'bldgname',
                            'type' => 'input',
                            'label' => 'Building Name',
                            'class' => 'csbldgname',
                            'readonly' => true,
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'required' => true,
                            'align' => 'text-left'
                     ),
                     'bldgcode' => array(
                            'name' => 'bldgcode',
                            'type' => 'input',
                            'label' => 'Building Code',
                            'class' => 'csbldgcode',
                            'readonly' => true,
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'required' => true,
                            'align' => 'text-left'
                     ),
                     'coursecode' => array(
                            'name' => 'coursecode',
                            'field' => 'coursecode',
                            'type' => 'lookup',
                            'label' => 'Course Code',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'readonly' => true,
                            'lookupclass' => 'lookupcourse',
                            'action' => 'lookupcourse'
                     ),
                     'coursename' => array(
                            'name' => 'coursename',
                            'field' => 'coursename',
                            'type' => 'input',
                            'label' => 'Course Name',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'readonly' => false,
                     ),
                     'subjectcode' => array(
                            'name' => 'subjectcode',
                            'field' => 'subjectcode',
                            'type'  => 'input',
                            'label' => 'Subject Code',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'readonly' => false,
                            'lookupclass' => 'lookupsubject',
                            'action' => 'lookupsubject'
                     ),
                     'subjectname' => array(
                            'name' => 'subjectname',
                            'field' => 'subjectname',
                            'type' => 'input',
                            'label' => 'Subject Name',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal; min-width:20px;max-width:30px;',
                            'readonly' => false,
                     ),
                     'rctitle' => array(
                            'name' => 'rctitle',
                            'field' => 'rctitle',
                            'type' => 'lookup',
                            'label' => 'Report Card',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace:normal;min-width:120px;',
                            'readonly' => true,
                            'lookupclass' => 'lookupreportcardsetup',
                            'action' => 'lookupsetup'
                     ),
                     'clientstatus' => array(
                            'name' => 'clientstatus',
                            'field' => 'clientstatus',
                            'type' => 'lookup',
                            'label' => 'Client Status',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace:normal;min-width:120px;',
                            'readonly' => true,
                            'lookupclass' => 'lookupclientstatus',
                            'action' => 'lookupsetup'
                     ),
                     'units' => array(
                            'name' => 'units',
                            'field' => 'units',
                            'type' => 'input',
                            'label' => 'Units',
                            'align' => 'text-right',
                            'style' => 'width: 60px;whiteSpace: normal; min-width:60px;max-width:60px;',
                            'readonly' => false,
                     ),
                     'lecture' => array(
                            'name' => 'lecture',
                            'field' => 'lecture',
                            'type' => 'input',
                            'label' => 'Lecture',
                            'align' => 'text-right',
                            'style' => 'width: 60px;whiteSpace: normal; min-width:60px;max-width:60px;',
                            'readonly' => false,
                     ),
                     'grade' => [
                            'name' => 'grade',
                            'field' => 'grade',
                            'type' => 'input',
                            'label' => 'Grade',
                            'align' => 'text-right',
                            'style' => 'width:60px; whiteSpace:normal; min-width:60px; max-width:60px;',
                            'readonly' => false
                     ],
                     'laboratory' => array(
                            'name' => 'laboratory',
                            'field' => 'laboratory',
                            'type' => 'input',
                            'label' => 'Laboratory',
                            'align' => 'text-right',
                            'style' => 'width: 60px;whiteSpace: normal; min-width:60px;max-width:60px;',
                            'readonly' => false,
                     ),
                     'hours' => array(
                            'name' => 'hours',
                            'field' => 'hours',
                            'type' => 'input',
                            'label' => 'Hours',
                            'align' => 'text-right',
                            'style' => 'width: 60px;whiteSpace: normal; min-width:60px;max-width:60px;',
                            'readonly' => false,
                     ),
                     'body' => array(
                            'name' => 'body',
                            'field' => 'body',
                            'type' => 'input',
                            'label' => 'Form',
                            'align' => 'text-left',
                            'style' => 'width: 60px;whiteSpace: normal; min-width:60px;max-width:60px;',
                            'readonly' => false,
                     ),
                     'yearnum' => array(
                            'name' => 'yearnum',
                            'field' => 'yearnum',
                            'type' => 'input',
                            'label' => 'Year/Grade',
                            'align' => 'text-right',
                            'style' => 'width: 60px;whiteSpace: normal; min-width:60px;max-width:60px;',
                            'readonly' => true,
                     ),
                     'coreq' => array(
                            'name' => 'coreq',
                            'type' => 'lookup',
                            'label' => 'Co-Req',
                            'field' => 'coreq',
                            'align' => 'text-left',
                            'style' => 'text-align:right;width:180px;whiteSpace: normal;min-width:180px;',
                            'readonly' => true,
                            'lookupclass' => 'lookupgridcoreq',
                            'action' => 'lookupgridcoreq'
                     ),
                     'acnoexpense' => array(
                            'name' => 'acnoexpense',
                            'type' => 'lookup',
                            'action' => 'lookupsetup',
                            'label' => 'Account Expense',
                            'field' => 'acnoexpense',
                            'align' => 'text-left',
                            'style' => 'text-align:right;width:180px;whiteSpace: normal;min-width:180px;',
                            'readonly' => true,
                            'lookupclass' => 'lookupexpense'
                     ),

                     'pre1' => array(
                            'name' => 'pre1',
                            'field' => 'pre1',
                            'type' => 'lookup',
                            'label' => 'Pre-Req 1',
                            'align' => 'text-left',
                            'style' => 'text-align:right;width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false,
                            'lookupclass' => 'lookupgridpre1',
                            'action' => 'lookupgridpre1'
                     ),
                     'pre2' => array(
                            'name' => 'pre2',
                            'field' => 'pre2',
                            'type' => 'lookup',
                            'label' => 'Pre-Req 2',
                            'align' => 'text-left',
                            'style' => 'text-align:right;width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false,
                            'lookupclass' => 'lookupgridpre2',
                            'action' => 'lookupgridpre2'
                     ),
                     'pre3' => array(
                            'name' => 'pre3',
                            'field' => 'pre3',
                            'type' => 'lookup',
                            'label' => 'Pre-Req 3',
                            'align' => 'text-left',
                            'style' => 'text-align:right;width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false,
                            'lookupclass' => 'lookupgridpre3',
                            'action' => 'lookupgridpre3'
                     ),
                     'pre4' => array(
                            'name' => 'pre4',
                            'field' => 'pre4',
                            'type' => 'lookup',
                            'label' => 'Pre-Req 4',
                            'align' => 'text-left',
                            'style' => 'text-align:right;width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false,
                            'lookupclass' => 'lookupgridpre4',
                            'action' => 'lookupgridpre4'
                     ),
                     'instructorcode' => array(
                            'name' => 'instructorcode',
                            'field' => 'instructorcode',
                            'type' => 'input',
                            'label' => 'Instructor Code',
                            'align' => 'text-right',
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'readonly' => false,
                     ),
                     'instructorname' => array(
                            'name' => 'instructorname',
                            'field' => 'instructorname',
                            'type' => 'input',
                            'label' => 'Instructor Name',
                            'align' => 'text-right',
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'readonly' => false,
                     ),
                     'rooms' => array(
                            'name' => 'rooms',
                            'field' => 'rooms',
                            'type' => 'input',
                            'label' => 'Rooms',
                            'align' => 'text-right',
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'readonly' => false,
                     ),
                     'schedday' => array(
                            'name' => 'schedday',
                            'field' => 'schedday',
                            'type' => 'input',
                            'label' => 'Sched. Day',
                            'align' => 'text-right',
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'readonly' => false,
                     ),
                     'schedtime' => array(
                            'name' => 'schedtime',
                            'field' => 'schedtime',
                            'type' => 'input',
                            'label' => 'Sched. Time',
                            'align' => 'text-right',
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'readonly' => false,
                     ),
                     'schedstarttime' => array(
                            'name' => 'schedstarttime',
                            'field' => 'schedstarttime',
                            'type' => 'datetime',
                            'label' => 'Start Time',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:230px;',
                            'readonly' => false,
                     ),
                     'schedendtime' => array(
                            'name' => 'schedendtime',
                            'field' => 'schedendtime',
                            'type' => 'datetime',
                            'label' => 'End Time',
                            'align' => 'text-right',
                            'style' => 'width:150px;whiteSpace: normal;min-width:230px;',
                            'readonly' => false,
                     ),
                     'timeinout' => array(
                            'name' => 'timeinout',
                            'field' => 'timeinout',
                            'type' => 'input',
                            'label' => 'Time Log',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:230px;',
                            'readonly' => true,
                     ),
                     'dategiven' => array(
                            'name' => 'dategiven',
                            'field' => 'dategiven',
                            'type' => 'date',
                            'label' => 'Given Date',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:230px;',
                            'readonly' => false,
                     ),
                     'minslot' => array(
                            'name' => 'minslot',
                            'field' => 'minslot',
                            'type' => 'input',
                            'label' => 'Min. slot',
                            'align' => 'text-right',
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'readonly' => false,
                     ),
                     'maxslot' => array(
                            'name' => 'maxslot',
                            'field' => 'maxslot',
                            'type' => 'input',
                            'label' => 'Max. Slot',
                            'align' => 'text-right',
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'readonly' => false,
                     ),
                     'deductpercent' => array(
                            'name' => 'deductpercent',
                            'field' => 'deductpercent',
                            'type' => 'input',
                            'label' => 'Interest',
                            'align' => 'text-right',
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'readonly' => false,
                     ),
                     'modeofpayment' => array(
                            'name' => 'modeofpayment',
                            'field' => 'modeofpayment',
                            'type' => 'input',
                            'label' => 'Mode of Payment',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'readonly' => false,
                     ),
                     'months' => array(
                            'name' => 'months',
                            'field' => 'months',
                            'type' => 'input',
                            'label' => '# of Months',
                            'align' => 'text-right',
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'readonly' => false,
                     ),
                     'perc1' => array(
                            'name' => 'perc1',
                            'field' => 'perc1',
                            'type' => 'input',
                            'label' => '1st %',
                            'align' => 'text-right',
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'readonly' => false,
                     ),
                     'date1' => array(
                            'name' => 'date1',
                            'field' => 'date1',
                            'type' => 'date',
                            'label' => 'Date 1',
                            'align' => 'text-left',
                            'style' => 'width:70px;whiteSpace: normal;min-width:90px;',
                            'readonly' => false
                     ),
                     'perc2' => array(
                            'name' => 'perc2',
                            'field' => 'perc2',
                            'type' => 'input',
                            'label' => '2nd %',
                            'align' => 'text-right',
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'readonly' => false,
                     ),
                     'date2' => array(
                            'name' => 'date2',
                            'field' => 'date2',
                            'type' => 'date',
                            'label' => 'Date 2',
                            'align' => 'text-left',
                            'style' => 'width:70px;whiteSpace: normal;min-width:90px;',
                            'readonly' => false
                     ),
                     'perc3' => array(
                            'name' => 'perc3',
                            'field' => 'perc3',
                            'type' => 'input',
                            'label' => '3rd %',
                            'align' => 'text-right',
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'readonly' => false,
                     ),
                     'date3' => array(
                            'name' => 'date3',
                            'field' => 'date3',
                            'type' => 'date',
                            'label' => 'Date 3',
                            'align' => 'text-left',
                            'style' => 'width:70px;whiteSpace: normal;min-width:90px;',
                            'readonly' => false
                     ),
                     'perc4' => array(
                            'name' => 'perc4',
                            'field' => 'perc4',
                            'type' => 'input',
                            'label' => '4th %',
                            'align' => 'text-right',
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'readonly' => false,
                     ),
                     'date4' => array(
                            'name' => 'date4',
                            'field' => 'date4',
                            'type' => 'date',
                            'label' => 'Date 4',
                            'align' => 'text-left',
                            'style' => 'width:70px;whiteSpace: normal;min-width:90px;',
                            'readonly' => false
                     ),
                     'perc5' => array(
                            'name' => 'perc5',
                            'field' => 'perc5',
                            'type' => 'input',
                            'label' => '5th %',
                            'align' => 'text-right',
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'readonly' => false,
                     ),
                     'date5' => array(
                            'name' => 'date5',
                            'field' => 'date5',
                            'type' => 'date',
                            'label' => 'Date 5',
                            'align' => 'text-left',
                            'style' => 'width:70px;whiteSpace: normal;min-width:90px;',
                            'readonly' => false
                     ),
                     'perc6' => array(
                            'name' => 'perc6',
                            'field' => 'perc6',
                            'type' => 'input',
                            'label' => '6th %',
                            'align' => 'text-right',
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'readonly' => false,
                     ),
                     'date6' => array(
                            'name' => 'date6',
                            'field' => 'date6',
                            'type' => 'date',
                            'label' => 'Date 6',
                            'align' => 'text-left',
                            'style' => 'width:70px;whiteSpace: normal;min-width:90px;',
                            'readonly' => false
                     ),
                     'perc7' => array(
                            'name' => 'perc7',
                            'field' => 'perc7',
                            'type' => 'input',
                            'label' => '7th %',
                            'align' => 'text-right',
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'readonly' => false,
                     ),
                     'date7' => array(
                            'name' => 'date7',
                            'field' => 'date7',
                            'type' => 'date',
                            'label' => 'Date 7',
                            'align' => 'text-left',
                            'style' => 'width:70px;whiteSpace: normal;min-width:90px;',
                            'readonly' => false
                     ),
                     'perc8' => array(
                            'name' => 'perc8',
                            'field' => 'perc8',
                            'type' => 'input',
                            'label' => '8th %',
                            'align' => 'text-right',
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'readonly' => false,
                     ),
                     'date8' => array(
                            'name' => 'date8',
                            'field' => 'date8',
                            'type' => 'date',
                            'label' => 'Date 8',
                            'align' => 'text-left',
                            'style' => 'width:70px;whiteSpace: normal;min-width:90px;',
                            'readonly' => false
                     ),
                     'perc9' => array(
                            'name' => 'perc9',
                            'field' => 'perc9',
                            'type' => 'input',
                            'label' => '9th %',
                            'align' => 'text-right',
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'readonly' => false,
                     ),
                     'date9' => array(
                            'name' => 'date9',
                            'field' => 'date9',
                            'type' => 'date',
                            'label' => 'Date 9',
                            'align' => 'text-left',
                            'style' => 'width:70px;whiteSpace: normal;min-width:90px;',
                            'readonly' => false
                     ),
                     'perc10' => array(
                            'name' => 'perc10',
                            'field' => 'perc10',
                            'type' => 'input',
                            'label' => '10th %',
                            'align' => 'text-right',
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'readonly' => false,
                     ),
                     'date10' => array(
                            'name' => 'date10',
                            'field' => 'date10',
                            'type' => 'date',
                            'label' => 'Date 10',
                            'align' => 'text-left',
                            'style' => 'width:70px;whiteSpace: normal;min-width:90px;',
                            'readonly' => false
                     ),


                     // ENROLLMENT SETUP - Jiks
                     'sy' => array(
                            'name' => 'sy',
                            'field' => 'sy',
                            'type' => 'input',
                            'label' => 'School Year',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'sex' => array(
                            'name' => 'sex',
                            'field' => 'sex',
                            'type' => 'input',
                            'label' => 'Sex',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'issy' => array(
                            'name' => 'issy',
                            'field' => 'issy',
                            'type' => 'input',
                            'label' => 'SY',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'scheme' => array(
                            'name' => 'scheme',
                            'field' => 'scheme',
                            'type' => 'input',
                            'label' => 'Scheme',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'orderscheme' => array(
                            'name' => 'orderscheme',
                            'field' => 'orderscheme',
                            'type' => 'input',
                            'label' => 'Order',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'isnew' => array(
                            'name' => 'isnew',
                            'type' => 'toggle',
                            'label' => 'New',
                            'field' => 'isnew',
                            'align' => 'text-right',
                            'style' => 'width:40px;whiteSpace: normal;min-width:40px;',
                            'readonly' => false
                     ),
                     'isold' => array(
                            'name' => 'isold',
                            'type' => 'toggle',
                            'label' => 'Old',
                            'field' => 'isold',
                            'align' => 'text-right',
                            'style' => 'width:40px;whiteSpace: normal;min-width:40px;',
                            'readonly' => false
                     ),
                     'isforeign' => array(
                            'name' => 'isforeign',
                            'type' => 'toggle',
                            'label' => 'Foreign',
                            'field' => 'isforeign',
                            'align' => 'text-right',
                            'style' => 'width:40px;whiteSpace: normal;min-width:40px;',
                            'readonly' => false
                     ),
                     'isadddrop' => array(
                            'name' => 'isadddrop',
                            'type' => 'toggle',
                            'label' => 'Add/Drop',
                            'field' => 'isadddrop',
                            'align' => 'text-right',
                            'style' => 'width:40px;whiteSpace: normal;min-width:40px;',
                            'readonly' => false
                     ),
                     'islateenrollee' => array(
                            'name' => 'islateenrollee',
                            'type' => 'toggle',
                            'label' => 'Late',
                            'field' => 'islateenrollee',
                            'align' => 'text-right',
                            'style' => 'width:40px;whiteSpace: normal;min-width:40px;',
                            'readonly' => false
                     ),
                     'istransferee' => array(
                            'name' => 'istransferee',
                            'type' => 'toggle',
                            'label' => 'Transfer',
                            'field' => 'istransferee',
                            'align' => 'text-right',
                            'style' => 'width:40px;whiteSpace: normal;min-width:40px;',
                            'readonly' => false
                     ),
                     'isdrop' => array(
                            'name' => 'isdrop',
                            'type' => 'toggle',
                            'label' => 'Drop',
                            'field' => 'isdrop',
                            'align' => 'text-right',
                            'style' => 'width:40px;whiteSpace: normal;min-width:40px;',
                            'readonly' => false
                     ),
                     'iscrossenrollee' => array(
                            'name' => 'iscrossenrollee',
                            'type' => 'toggle',
                            'label' => 'Cross',
                            'field' => 'iscrossenrollee',
                            'align' => 'text-right',
                            'style' => 'width:40px;whiteSpace: normal;min-width:40px;',
                            'readonly' => false
                     ),
                     'year' => array(
                            'name' => 'year',
                            'field' => 'year',
                            'type' => 'input',
                            'label' => 'Year/Grade',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'isactive' => array(
                            'name' => 'isactive',
                            'type' => 'toggle',
                            'label' => 'Is active',
                            'field' => 'isactive',
                            'align' => 'text-left',
                            'style' => 'width:20px;whiteSpace: normal;min-width:30px;align:center',
                            'readonly' => false
                     ),
                     'isgradeschool' => array(
                            'name' => 'isgradeschool',
                            'type' => 'toggle',
                            'label' => 'Grades School',
                            'field' => 'isgradeschool',
                            'align' => 'text-left',
                            'style' => 'width:20px;whiteSpace: normal;min-width:30px;align:center',
                            'readonly' => false
                     ),
                     'ishighschool' => array(
                            'name' => 'ishighschool',
                            'type' => 'toggle',
                            'label' => 'High School',
                            'field' => 'ishighschool',
                            'align' => 'text-left',
                            'style' => 'width:20px;whiteSpace: normal;min-width:30px;align:center',
                            'readonly' => false
                     ),
                     'isenconvertgrade' => array(
                            'name' => 'isenconvertgrade',
                            'type' => 'toggle',
                            'label' => 'Convert Grade English',
                            'field' => 'isenconvertgrade',
                            'align' => 'text-left',
                            'style' => 'width:20px;min-width:30px;align:center;whiteSpace:normal;',
                            'readonly' => false
                     ),
                     'ischiconvertgrade' => array(
                            'name' => 'ischiconvertgrade',
                            'type' => 'toggle',
                            'label' => 'Convert Grade Chinese',
                            'field' => 'ischiconvertgrade',
                            'align' => 'text-left',
                            'style' => 'width:20px;min-width:30px;align:center;whiteSpace:normal;',
                            'readonly' => false
                     ),
                     'isdiminishing' => array(
                            'name' => 'isdiminishing',
                            'type' => 'toggle',
                            'label' => 'Diminishing',
                            'field' => 'isdiminishing',
                            'align' => 'text-left',
                            'style' => 'width:20px;whiteSpace: normal;min-width:30px;align:center',
                            'readonly' => false
                     ),

                     'datestart' => array(
                            'name' => 'datestart',
                            'type' => 'date',
                            'label' => 'Start Date',
                            'field' => 'datestart',
                            'align' => 'text-left',
                            'style' => 'width:90px;whiteSpace: normal;min-width:90px;',
                            'readonly' => false
                     ),


                     'sstart' => array(
                            'name' => 'sstart',
                            'type' => 'date',
                            'label' => 'Start Date',
                            'field' => 'sstart',
                            'align' => 'text-left',
                            'style' => 'width:90px;whiteSpace: normal;min-width:90px;',
                            'readonly' => false
                     ),
                     'send' => array(
                            'name' => 'send',
                            'type' => 'date',
                            'label' => 'End Date',
                            'field' => 'send',
                            'align' => 'text-left',
                            'style' => 'width:90px;whiteSpace: normal;min-width:90px;',
                            'readonly' => false
                     ),
                     'sext' => array(
                            'name' => 'sext',
                            'type' => 'date',
                            'label' => 'Ext Date',
                            'field' => 'sext',
                            'align' => 'text-left',
                            'style' => 'width:90px;whiteSpace: normal;min-width:90px;',
                            'readonly' => false
                     ),

                     'estart' => array(
                            'name' => 'estart',
                            'type' => 'date',
                            'label' => 'Enrollment Start Date',
                            'field' => 'estart',
                            'align' => 'text-left',
                            'style' => 'width:70px;whiteSpace: normal;min-width:90px;',
                            'readonly' => false
                     ),
                     'eend' => array(
                            'name' => 'eend',
                            'type' => 'date',
                            'label' => 'Enrollment End Date',
                            'field' => 'eend',
                            'align' => 'text-left',
                            'style' => 'width:90px;whiteSpace: normal;min-width:90px;',
                            'readonly' => false
                     ),
                     'eext' => array(
                            'name' => 'eext',
                            'type' => 'date',
                            'label' => 'Enrollment Ext Date',
                            'field' => 'eext',
                            'align' => 'text-left',
                            'style' => 'width:90px;whiteSpace: normal;min-width:90px;',
                            'readonly' => false
                     ),

                     'astart' => array(
                            'name' => 'astart',
                            'type' => 'date',
                            'label' => 'Add/Drop Start Date',
                            'field' => 'astart',
                            'align' => 'text-left',
                            'style' => 'width:90px;whiteSpace: normal;min-width:90px;',
                            'readonly' => false
                     ),
                     'aend' => array(
                            'name' => 'aend',
                            'type' => 'date',
                            'label' => 'Add/Drop End Date',
                            'field' => 'aend',
                            'align' => 'text-left',
                            'style' => 'width:90px;whiteSpace: normal;min-width:90px;',
                            'readonly' => false
                     ),
                     'aext' => array(
                            'name' => 'aext',
                            'type' => 'date',
                            'label' => 'Add/Drop Ext Date',
                            'field' => 'aext',
                            'align' => 'text-left',
                            'style' => 'width:90px;whiteSpace: normal;min-width:90px;',
                            'readonly' => false
                     ),
                     'gccode' => array(
                            'name' => 'gccode',
                            'type' => 'input',
                            'label' => 'code',
                            'field' => 'gccode',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'gcname' => array(
                            'name' => 'gcname',
                            'type' => 'input',
                            'label' => 'Description',
                            'field' => 'gcname',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'gcpercent' => array(
                            'name' => 'gcpercent',
                            'type' => 'input',
                            'label' => 'Percentage',
                            'field' => 'gcpercent',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'range1' => array(
                            'name' => 'range1',
                            'type' => 'input',
                            'label' => 'Start Range',
                            'field' => 'range1',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'range2' => array(
                            'name' => 'range2',
                            'type' => 'input',
                            'label' => 'End Range',
                            'field' => 'range2',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'equivalent' => array(
                            'name' => 'equivalent',
                            'type' => 'input',
                            'label' => 'Equivalent',
                            'field' => 'equivalent',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'cur' => array(
                            'name' => 'cur',
                            'type' => 'input',
                            'label' => 'Cur',
                            'field' => 'cur',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'curtopeso' => array(
                            'name' => 'curtopeso',
                            'type' => 'input',
                            'label' => 'Cur To Peso',
                            'field' => 'curtopeso',
                            'align' => 'text-right',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'dollartocur' => array(
                            'name' => 'dollartocur',
                            'type' => 'input',
                            'label' => 'Dollar To Cur',
                            'field' => 'dollartocur',
                            'align' => 'text-right',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'markup' => array(
                            'name' => 'markup',
                            'type' => 'input',
                            'label' => 'Mark Up (%)',
                            'field' => 'markup',
                            'align' => 'text-right',
                            'style' => 'width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => true
                     ),
                     // Enrollment
                     'listisnew' => array(
                            'name' => 'isnew',
                            'type' => 'toggle',
                            'label' => 'New Student',
                            'field' => 'isnew',
                            'align' => 'text-center',
                            'style' => 'width:80px;whiteSpace: normal;min-width:80px;',
                            'readonly' => false
                     ),
                     'listisold' => array(
                            'name' => 'isold',
                            'type' => 'toggle',
                            'label' => 'Old Student',
                            'field' => 'isold',
                            'align' => 'text-right',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),

                     'origsubjectcode' => array(
                            'name' => 'origsubjectcode',
                            'field' => 'origsubjectcode',
                            'type'  => 'input',
                            'label' => 'Orig. Subject Code',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'origdocno' => array(
                            'name' => 'origdocno',
                            'field' => 'origdocno',
                            'type'  => 'input',
                            'label' => 'Orig. Sched. Doc#',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'ehpoints' => array(
                            'name' => 'points',
                            'field' => 'points',
                            'type' => 'label',
                            'label' => 'Points',
                            'align' => 'text-right',
                            'style' => 'width:20px; whiteSpace:normal; min-width:20px; max-width:30px; text-align:right;',
                            'readonly' => false
                     ),
                     'levelup' => array(
                            'name' => 'levelup',
                            'field' => 'levelup',
                            'type' => 'input',
                            'label' => 'Level Up',
                            'align' => 'text-right',
                            'style' => 'width:20px; whiteSpace:normal; min-width:20px; max-width:30px; text-align:right;',
                            'readonly' => false
                     ),
                     'quartercode' => array(
                            'name' => 'quartercode',
                            'field' => 'quarterid',
                            'type' => 'lookup',
                            'label' => 'Quarter',
                            'align' => 'text-right',
                            'style' => 'width:20px; whiteSpace:normal; min-width:20px; max-width:30px; text-align:right;',
                            'readonly' => false,
                            'lookupclass' => 'lookupquarter',
                            'action' => 'lookupquarter'
                     ),
                     'quartername' => array(
                            'name' => 'quartername',
                            'field' => 'quartername',
                            'type' => 'lookup',
                            'label' => 'Quarter',
                            'align' => 'text-left',
                            'style' => 'width:100px;min-width:100px;max-width:100px;whiteSpace:normal;',
                            'readonly' => false,
                            'lookupclass' => 'lookupquarter',
                            'action' => 'lookupquarter'
                     ),
                     'chinesecode' => array(
                            'name' => 'chinesecode',
                            'field' => 'chinesecode',
                            'type' => 'input',
                            'label' => 'Chinese Code',
                            'align' => 'text-left',
                            'style' => 'width:150px;min-width:150px;max-width:150px;whiteSpace:normal;',
                            'readonly' => false
                     ),
                     'gradeequivalent' => array(
                            'name' => 'gradeequivalent',
                            'type' => 'input',
                            'label' => 'English Equivalent (required)',
                            'field' => 'gradeequivalent',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false,
                            'required' => true
                     ),
                     'chineseequivalent' => array(
                            'name' => 'chineseequivalent',
                            'type' => 'input',
                            'label' => 'Chinese Equivalent (required)',
                            'field' => 'chineseequivalent',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false,
                            'required' => true
                     ),
                     'gudescription' => array(
                            'name' => 'gudescription',
                            'type' => 'input',
                            'label' => 'Description',
                            'field' => 'gudescription',
                            'align' => 'text-left',
                            'style' => 'width:150px;min-width:150px;max-width:150px;whiteSpace:normal;',
                            'readonly' => false
                     ),
                     'gudecimal' => array(
                            'name' => 'gudecimal',
                            'type' => 'input',
                            'label' => 'Decimal',
                            'field' => 'gudecimal',
                            'align' => 'text-left',
                            'style' => 'width:150px;min-width:150px;max-width:150px;whiteSpace:normal;',
                            'readonly' => false
                     ),
                     'isconduct' => array(
                            'name' => 'isconduct',
                            'type' => 'toggle',
                            'label' => 'Conduct Component',
                            'field' => 'isconduct',
                            'align' => 'text-left',
                            'style' => 'width:20px;whiteSpace: normal;min-width:30px;align:center',
                            'readonly' => false
                     ),
                     'actiontaken' => array(
                            'name' => 'actiontaken',
                            'type' => 'input',
                            'label' => 'Action Taken',
                            'field' => 'actiontaken',
                            'align' => 'text-left',
                            'style' => 'width:150px;min-width:150px;max-width:150px;whiteSpace:normal;',
                            'readonly' => false
                     ),
                     // End of Enrollment

                     // HRIS
                     'empcode' => array(
                            'name' => 'empcode',
                            'field' => 'empcode',
                            'type' => 'input',
                            'label' => 'Employee code',
                            'align' => 'text-left',
                            'style' => 'width: 130px;whiteSpace: normal;min-width:130px;max-width:140px;',
                            'readonly' => true
                     ),
                     'empname' => array(
                            'name' => 'empname',
                            'field' => 'empname',
                            'type' => 'input',
                            'label' => 'Name',
                            'align' => 'text-left',
                            'style' => 'width: 120px;whiteSpace: normal;min-width:120px;max-width:140px;',
                            'readonly' => true
                     ),
                     'empid' => array(
                            'name' => 'empid',
                            'type' => 'hidden',
                            'class' => 'csclass sbccsreadonly',
                            'style' => 'display:none'
                     ),
                     'fempname' => array(
                            'name' => 'fempname',
                            'field' => 'fempname',
                            'type' => 'input',
                            'label' => 'From',
                            'align' => 'text-left',
                            'style' => 'width: 120px;whiteSpace: normal;min-width:120px;max-width:140px;',
                            'readonly' => true
                     ),
                     'tempname' => array(
                            'name' => 'tempname',
                            'field' => 'tempname',
                            'type' => 'input',
                            'label' => 'To',
                            'align' => 'text-left',
                            'style' => 'width: 120px;whiteSpace: normal;min-width:120px;max-width:140px;',
                            'readonly' => true
                     ),
                     'ttype' => array(
                            'name' => 'ttype',
                            'field' => 'ttype',
                            'type' => 'input',
                            'label' => 'Training Type',
                            'align' => 'text-center',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'hired' => array(
                            'name' => 'hired',
                            'field' => 'hired',
                            'type' => 'input',
                            'label' => 'Date Hired',
                            'align' => 'text-center',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     //PAYROLL LIST
                     'listempcode' => array(
                            'name' => 'empcode',
                            'field' => 'empcode',
                            'type' => 'input',
                            'label' => 'Employee Code',
                            'align' => 'text-center',
                            'style' => 'width: 10px;whiteSpace: normal;min-width:10px;max-width:10px;',
                            'readonly' => false
                     ),
                     'listempname' => array(
                            'name' => 'empname',
                            'field' => 'empname',
                            'type' => 'input',
                            'label' => 'Name',
                            'align' => 'text-center',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'listapplied' => array(
                            'name' => 'appdate',
                            'field' => 'appdate',
                            'type' => 'input',
                            'label' => 'Applied Date',
                            'align' => 'text-center',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'listappstatus' => array(
                            'name' => 'jstatus',
                            'field' => 'jstatus',
                            'type' => 'input',
                            'label' => 'Status',
                            'align' => 'text-center',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'listinitialstatus' => array(
                            'name' => 'inistatus',
                            'field' => 'inistatus',
                            'type' => 'input',
                            'label' => 'Initial Status',
                            'align' => 'text-center',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'initialremarks' => array(
                            'name' => 'initialremarks',
                            'field' => 'initialremarks',
                            'type' => 'input',
                            'label' => 'Initial Reason',
                            'align' => 'text-center',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'listinitialstatus2' => array(
                            'name' => 'inistatus2',
                            'field' => 'inistatus2',
                            'type' => 'input',
                            'label' => 'Initial Status',
                            'align' => 'text-center',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'initialremarks2' => array(
                            'name' => 'initialremarks2',
                            'field' => 'initialremarks2',
                            'type' => 'input',
                            'label' => 'Initial Reason',
                            'align' => 'text-center',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'listappstatus2' => array(
                            'name' => 'status2',
                            'field' => 'status2',
                            'type' => 'input',
                            'label' => 'Status (Supervisor)',
                            'align' => 'text-center',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'listjobapplied' => array(
                            'name' => 'jobtitle',
                            'field' => 'jobtitle',
                            'type' => 'input',
                            'label' => 'Applied Position',
                            'align' => 'text-center',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),

                     'listaddress' => array(
                            'name' => 'address',
                            'field' => 'address',
                            'type' => 'input',
                            'label' => 'Address',
                            'align' => 'text-center',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),

                     'relation' => array(
                            'name' => 'relation',
                            'type' => 'input',
                            'label' => 'Relationship',
                            'field' => 'relation',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'bday' => array(
                            'name' => 'bday',
                            'field' => 'bday',
                            'type' => 'date',
                            'label' => 'Birthday',
                            'align' => 'text-left',
                            'style' => 'width:70px;whiteSpace: normal;min-width:90px;',
                            'readonly' => false
                     ),
                     'school' => array(
                            'name' => 'school',
                            'type' => 'input',
                            'label' => 'School',
                            'field' => 'school',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'gpa' => array(
                            'name' => 'gpa',
                            'type' => 'input',
                            'label' => 'GPA(%)',
                            'field' => 'gpa',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'course' => array(
                            'name' => 'course',
                            'type' => 'input',
                            'label' => 'Course',
                            'field' => 'course',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'honor' => array(
                            'name' => 'honor',
                            'type' => 'input',
                            'label' => 'Honor',
                            'field' => 'honor',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'company' => array(
                            'name' => 'company',
                            'type' => 'input',
                            'label' => 'Company',
                            'field' => 'company',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'salary' => array(
                            'name' => 'salary',
                            'type' => 'input',
                            'label' => 'Salary',
                            'field' => 'salary',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'cola' => array(
                            'name' => 'cola',
                            'type' => 'input',
                            'label' => 'Cola',
                            'field' => 'cola',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'reason' => array(
                            'name' => 'reason',
                            'type' => 'input',
                            'label' => 'Reason for leaving',
                            'field' => 'reason',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'jobtitle' => array(
                            'name' => 'jobtitle',
                            'type' => 'input',
                            'label' => 'Job Title',
                            'field' => 'jobtitle',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'period' => array(
                            'name' => 'period',
                            'type' => 'input',
                            'label' => 'Period',
                            'field' => 'period',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'reqs' => array(
                            'name' => 'reqs',
                            'type' => 'input',
                            'label' => 'Requirement',
                            'field' => 'reqs',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'pin' => array(
                            'name' => 'pin',
                            'type' => 'input',
                            'label' => 'Code',
                            'field' => 'pin',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'notes' => array(
                            'name' => 'notes',
                            'type' => 'input',
                            'label' => 'Notes',
                            'field' => 'notes',
                            'align' => 'text-left',
                            'style' => 'width:220px;whiteSpace: normal;min-width:230px;',
                            'readonly' => false
                     ),
                     'submitdate' => array(
                            'name' => 'submitdate',
                            'field' => 'submitdate',
                            'type' => 'date',
                            'label' => 'Submit Date',
                            'align' => 'text-left',
                            'style' => 'width:70px;whiteSpace: normal;min-width:90px;',
                            'readonly' => false
                     ),
                     'issubmitted' => array(
                            'name' => 'issubmitted',
                            'type' => 'toggle',
                            'label' => 'Submit',
                            'field' => 'issubmitted',
                            'align' => 'text-right',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'preemptest' => array(
                            'name' => 'preemptest',
                            'type' => 'input',
                            'label' => 'Test',
                            'field' => 'preemptest',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'result' => array(
                            'name' => 'result',
                            'type' => 'input',
                            'label' => 'Result',
                            'field' => 'result',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'empstatus' => array(
                            'name' => 'empstatus',
                            'type' => 'input',
                            'label' => 'Employee Status',
                            'field' => 'empstatus',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'leavestatus' => array(
                            'name' => 'status',
                            'type' => 'lookup',
                            'label' => 'Status',
                            'field' => 'status',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false,
                            'lookupclass' => 'lookupgridleavestatus',
                            'action' => 'lookupsetup',
                     ),
                     'stat' => array(
                            'name' => 'stat',
                            'type' => 'input',
                            'label' => 'Status Change',
                            'field' => 'stat',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'status1name' => array(
                            'name' => 'status1name',
                            'type' => 'lookup',
                            'label' => 'Receive Status1',
                            'field' => 'status1name',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false,
                            'lookupclass' => 'lookupstatus1',
                            'action' => 'lookupstatname'
                     ),
                     'qty1' => array(
                            'name' => 'qty1',
                            'field' => 'qty1',
                            'type' => 'input',
                            'label' => 'Received 1 Qty',
                            'align' => 'text-right',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'status2name' => array(
                            'name' => 'status2name',
                            'type' => 'lookup',
                            'label' => 'Receive Status2',
                            'field' => 'status2name',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false,
                            'lookupclass' => 'lookupstatus2',
                            'action' => 'lookupstatname'
                     ),
                     'qty2' => array(
                            'name' => 'qty2',
                            'field' => 'qty2',
                            'type' => 'input',
                            'label' => 'Received 2 Qty',
                            'align' => 'text-right',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'tqty' => array(
                            'name' => 'tqty',
                            'field' => 'tqty',
                            'type' => 'input',
                            'label' => 'Transmittal Qty',
                            'align' => 'text-right',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'checkstatname' => array(
                            'name' => 'checkstatname',
                            'type' => 'lookup',
                            'label' => 'Check Status',
                            'field' => 'checkstatname',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false,
                            'lookupclass' => 'lookupcheckstat',
                            'action' => 'lookupstatname'
                     ),
                     'requestorstat' => array(
                            'name' => 'requestorstat',
                            'type' => 'input',
                            'label' => 'Requestor Status',
                            'field' => 'requestorstat',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'duration' => array(
                            'name' => 'duration',
                            'type' => 'input',
                            'label' => 'Duration',
                            'field' => 'duration',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'skill' => array(
                            'name' => 'skill',
                            'type' => 'input',
                            'label' => 'Skill Requirement',
                            'field' => 'skill',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'req' => array(
                            'name' => 'req',
                            'type' => 'input',
                            'label' => 'Requirements',
                            'field' => 'req',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'test' => array(
                            'name' => 'test',
                            'type' => 'input',
                            'label' => 'Pre-Employment Test',
                            'field' => 'test',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'd1a' => array(
                            'name' => 'd1a',
                            'field' => 'd1a',
                            'type' => 'input',
                            'label' => 'First',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'd1b' => array(
                            'name' => 'd1b',
                            'field' => 'd1b',
                            'type' => 'input',
                            'label' => '# of Days',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'd2a' => array(
                            'name' => 'd2a',
                            'field' => 'd2a',
                            'type' => 'input',
                            'label' => 'Second',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'd2b' => array(
                            'name' => 'd2b',
                            'field' => 'd2b',
                            'type' => 'input',
                            'label' => '# of Days',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'd3a' => array(
                            'name' => 'd3a',
                            'field' => 'd3a',
                            'type' => 'input',
                            'label' => 'Third',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'd3b' => array(
                            'name' => 'd3b',
                            'field' => 'd3b',
                            'type' => 'input',
                            'label' => '# of Days',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'd4a' => array(
                            'name' => 'd4a',
                            'field' => 'd4a',
                            'type' => 'input',
                            'label' => 'Fourth',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'd4b' => array(
                            'name' => 'd4b',
                            'field' => 'd4b',
                            'type' => 'input',
                            'label' => '# of Days',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'd5a' => array(
                            'name' => 'd5a',
                            'field' => 'd5a',
                            'type' => 'input',
                            'label' => 'Fifth',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'd5b' => array(
                            'name' => 'd5b',
                            'field' => 'd5b',
                            'type' => 'input',
                            'label' => '# of Days',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'trackingtype' => array(
                            'name' => 'trackingtype',
                            'field' => 'trackingtype',
                            'type' => 'input',
                            'label' => 'Tracking Type',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),




                     // End of HRIS


                     // PAYROLL SETUP - DIVISION
                     'divname' => array(
                            'name' => 'divname',
                            'field' => 'divname',
                            'type' => 'input',
                            'label' => 'Division Name',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'divcode' => array(
                            'name' => 'divcode',
                            'field' => 'divcode',
                            'type' => 'input',
                            'label' => 'Division Code',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'divid' => array(
                            'name' => 'divid',
                            'field' => 'divid',
                            'type' => 'label',
                            'label' => 'ID',
                            'align' => 'text-left',
                            'style' => 'width: 40px;whiteSpace: normal;min-width:40px;max-width:40px;',
                            'readonly' => false
                     ),

                     // PAYROLL SETUP - SECTION
                     'sectname' => array(
                            'name' => 'sectname',
                            'field' => 'sectname',
                            'type' => 'input',
                            'label' => 'Section Name',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'sectcode' => array(
                            'name' => 'sectcode',
                            'field' => 'sectcode',
                            'type' => 'input',
                            'label' => 'Section Code',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     // PAYROLL SETUP - SECTION
                     'paygroup' => array(
                            'name' => 'paygroup',
                            'field' => 'paygroup',
                            'type' => 'input',
                            'label' => 'Payroll Group',
                            'align' => 'text-left',
                            'style' => 'width: 120px;whiteSpace: normal;min-width:120px;max-width:120px;',
                            'readonly' => false
                     ),
                     //PAYROLL SETUP - ANNUAL TAX
                     'bracket' => array(
                            'name' => 'bracket',
                            'field' => 'bracket',
                            'type' => 'input',
                            'label' => 'Bracket',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'range1' => array(
                            'name' => 'range1',
                            'field' => 'range1',
                            'type' => 'input',
                            'label' => 'Range 1',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'range2' => array(
                            'name' => 'range2',
                            'field' => 'range2',
                            'type' => 'input',
                            'label' => 'Range 2',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'percentage' => array(
                            'name' => 'percentage',
                            'field' => 'percentage',
                            'type' => 'input',
                            'label' => 'PERCENTAGE (%)',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     // PAYROLL SETUP - PHIC TABLE
                     'phicee' => array(
                            'name' => 'phicee',
                            'field' => 'phicee',
                            'type' => 'input',
                            'label' => 'PHIC EE',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'phicer' => array(
                            'name' => 'phicer',
                            'field' => 'phicer',
                            'type' => 'input',
                            'label' => 'PHIC ER',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'phictotal' => array(
                            'name' => 'phictotal',
                            'field' => 'phictotal',
                            'type' => 'input',
                            'label' => 'MULTIPLIER (%)',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     // PAYROLL SETUP - SSS TABLE
                     'sssee' => array(
                            'name' => 'sssee',
                            'field' => 'sssee',
                            'type' => 'input',
                            'label' => 'SSS EE',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'ssser' => array(
                            'name' => 'ssser',
                            'field' => 'ssser',
                            'type' => 'input',
                            'label' => 'SSS ER',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'eccer' => array(
                            'name' => 'eccer',
                            'field' => 'eccer',
                            'type' => 'input',
                            'label' => 'SSS EC',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'mpfee' => array(
                            'name' => 'mpfee',
                            'field' => 'mpfee',
                            'type' => 'input',
                            'label' => 'MPF EE',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'mpfer' => array(
                            'name' => 'mpfer',
                            'field' => 'mpfer',
                            'type' => 'input',
                            'label' => 'MPF ER',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'ssstotal' => array(
                            'name' => 'ssstotal',
                            'field' => 'ssstotal',
                            'type' => 'input',
                            'label' => 'TOTAL',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     //PAYROLL SETUP - HDMF TABLE
                     'hdmfmulti' => array(
                            'name' => 'hdmfmulti',
                            'field' => 'hdmfmulti',
                            'type' => 'input',
                            'label' => 'MULTIPLIER (%)',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     //PAYROLL SETUP - WITHHOLDING TAX
                     'paymode' => array(
                            'name' => 'paymode',
                            'field' => 'paymode',
                            'type' => 'input',
                            'label' => 'Paymode',
                            'align' => 'text-left',
                            'style' => 'width: 120px;whiteSpace: normal;min-width:120px;max-width:120px;',
                            'readonly' => false
                     ),
                     'teu' => array(
                            'name' => 'teu',
                            'field' => 'teu',
                            'type' => 'input',
                            'label' => 'TEU',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'depnum' => array(
                            'name' => 'depnum',
                            'field' => 'depnum',
                            'type' => 'input',
                            'label' => 'DEPENDENTS',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'tax01' => array(
                            'name' => 'tax01',
                            'field' => 'tax01',
                            'type' => 'input',
                            'label' => 'TAX 1',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'tax02' => array(
                            'name' => 'tax02',
                            'field' => 'tax02',
                            'type' => 'input',
                            'label' => 'TAX 2',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'tax03' => array(
                            'name' => 'tax03',
                            'field' => 'tax03',
                            'type' => 'input',
                            'label' => 'TAX 3',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'tax04' => array(
                            'name' => 'tax04',
                            'field' => 'tax04',
                            'type' => 'input',
                            'label' => 'TAX 4',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'tax05' => array(
                            'name' => 'tax05',
                            'field' => 'tax05',
                            'type' => 'input',
                            'label' => 'TAX 5',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'tax06' => array(
                            'name' => 'tax06',
                            'field' => 'tax06',
                            'type' => 'input',
                            'label' => 'TAX 6',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     //PAYROLL SETUP - PAYROLL ACCOUNTS
                     'codename' => array(
                            'name' => 'codename',
                            'field' => 'codename',
                            'type' => 'input',
                            'label' => 'Codename',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'alias' => array(
                            'name' => 'alias',
                            'field' => 'alias',
                            'type' => 'input',
                            'label' => 'Alias',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'alias2' => array(
                            'name' => 'alias2',
                            'field' => 'alias2',
                            'type' => 'input',
                            'label' => 'Alias2',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'type' => array(
                            'name' => 'type',
                            'field' => 'type',
                            'type' => 'input',
                            'label' => 'Type',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'seq' => array(
                            'name' => 'seq',
                            'field' => 'seq',
                            'type' => 'input',
                            'label' => 'Seq',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'qty' => array(
                            'name' => 'qty',
                            'field' => 'qty',
                            'type' => 'input',
                            'label' => 'Multiplier',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),

                     //PAYROLL SETUP - HOLIDAY SETUP

                     'daytype' => array(
                            'name' => 'daytype',
                            'type' => 'lookup',
                            'label' => 'Day Type',
                            'field' => 'daytype',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => true,
                            'lookupclass' => 'lookupdaytype',
                            'action' => 'lookupsetup'
                     ),
                     'orgdaytype' => array(
                            'name' => 'orgdaytype',
                            'type' => 'input',
                            'label' => 'Original Day Type',
                            'field' => 'orgdaytype',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => true
                     ),

                     'dayname' => array(
                            'name' => 'dayname',
                            'type' => 'lookup',
                            'label' => 'Day Name',
                            'field' => 'daytype',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => true,
                            'lookupclass' => 'lookupdaytype',
                            'action' => 'lookupsetup'
                     ),
                     'dateeffect' => array(
                            'name' => 'dateeffect',
                            'field' => 'dateeffect',
                            'type' => 'date',
                            'label' => 'Effectivity Date',
                            'align' => 'text-left',
                            'style' => 'width:70px;whiteSpace: normal;min-width:90px;',
                            'readonly' => true
                     ),
                     'dateend' => array(
                            'name' => 'dateend',
                            'field' => 'dateend',
                            'type' => 'date',
                            'label' => 'End Date',
                            'align' => 'text-left',
                            'style' => 'width:70px;whiteSpace: normal;min-width:90px;',
                            'readonly' => true
                     ),
                     'pono' => array(
                            'name' => 'pono',
                            'type' => 'input',
                            'label' => 'Customer PO#',
                            'field' => 'pono',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),
                     'remarks' => array(
                            'name' => 'remarks',
                            'type' => 'input',
                            'label' => 'Remarks',
                            'field' => 'remarks',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),
                     'remarkslast' => array(
                            'name' => 'remarkslast',
                            'type' => 'input',
                            'label' => 'Approver Remarks',
                            'field' => 'remarkslast',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),
                     'fillingtype' => array(
                            'name' => 'fillingtype',
                            'type' => 'input',
                            'label' => 'Filling Type',
                            'field' => 'fillingtype',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),
                     'void_approver' => array(
                            'name' => 'void_approver',
                            'type' => 'input',
                            'label' => 'Cancelled By',
                            'field' => 'void_approver',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),
                     'void_date' => array(
                            'name' => 'void_date',
                            'type' => 'datetime',
                            'label' => 'Cancelled Date',
                            'field' => 'void_date',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),
                     'void_remarks' => array(
                            'name' => 'void_remarks',
                            'type' => 'input',
                            'label' => 'Remarks',
                            'field' => 'void_remarks',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),
                     'others' => array(
                            'name' => 'others',
                            'type' => 'input',
                            'label' => 'Others',
                            'field' => 'others',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),
                     'mrktremarks' => array(
                            'name' => 'mrktremarks',
                            'type' => 'input',
                            'label' => 'Marketing Remarks',
                            'field' => 'mrktremarks',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),
                     'officialwebsite' => array(
                            'name' => 'officialwebsite',
                            'type' => 'input',
                            'label' => 'Official Website',
                            'field' => 'officialwebsite',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),
                     'officialemail' => array(
                            'name' => 'officialemail',
                            'type' => 'input',
                            'label' => 'Official Email',
                            'field' => 'officialemail',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),
                     'salestype' => array(
                            'name' => 'salestype',
                            'type' => 'input',
                            'label' => 'Sales Type',
                            'field' => 'salestype',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'saleremarks' => array(
                            'name' => 'saleremarks',
                            'type' => 'input',
                            'label' => 'Sales Remarks',
                            'field' => 'saleremarks',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),
                     'isapplied' => array(
                            'name' => 'isapplied',
                            'type' => 'label',
                            'label' => 'Applied',
                            'field' => 'isapplied',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),
                     'ischinese' => array(
                            'name' => 'ischinese',
                            'type' => 'toggle',
                            'label' => 'Chinese',
                            'field' => 'ischinese',
                            'align' => 'text-center',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'basicrate' => array(
                            'name' => 'basicrate',
                            'type' => 'input',
                            'label' => 'Basic Rate',
                            'field' => 'basicrate',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),
                     'allowance' => array(
                            'name' => 'allowance',
                            'type' => 'input',
                            'label' => 'Allowance',
                            'field' => 'allowance',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),
                     'type' => array(
                            'name' => 'type',
                            'type' => 'input',
                            'label' => 'Type',
                            'field' => 'type',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),
                     'effdate' => array(
                            'name' => 'effdate',
                            'field' => 'effdate',
                            'type' => 'date',
                            'label' => 'Start of Deduction',
                            'align' => 'text-left',
                            'style' => 'width:70px;whiteSpace: normal;min-width:90px;',
                            'readonly' => true
                     ),
                     'payment' => array(
                            'name' => 'payment',
                            'type' => 'input',
                            'label' => 'Payment',
                            'field' => 'payment',
                            'align' => 'text-right',
                            'style' => 'width:150px;whiteSpace: normal;min-width:30px;',
                            'readonly' => true
                     ),
                     'balance' => array(
                            'name' => 'balance',
                            'type' => 'input',
                            'label' => 'Balance',
                            'field' => 'balance',
                            'align' => 'text-right',
                            'style' => 'width:150px;whiteSpace: normal;min-width:30;',
                            'readonly' => true
                     ),
                     'amortization' => array(
                            'name' => 'amortization',
                            'type' => 'input',
                            'label' => 'Amortization',
                            'field' => 'amortization',
                            'align' => 'text-right',
                            'style' => 'width:150px;whiteSpace: normal;min-width:30px;',
                            'readonly' => true
                     ),
                     'apamortization' => array(
                            'name' => 'apamortization',
                            'type' => 'input',
                            'label' => 'Approved Amortization',
                            'field' => 'apamortization',
                            'align' => 'text-right',
                            'style' => 'width:150px;whiteSpace: normal;min-width:30px;',
                            'readonly' => true
                     ),
                     'contractn' => array(
                            'name' => 'contractn',
                            'type' => 'input',
                            'label' => 'Contract#',
                            'field' => 'contractn',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),
                     'descr' => array(
                            'name' => 'descr',
                            'type' => 'input',
                            'label' => 'Description',
                            'field' => 'descr',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),
                     'datefrom' => array(
                            'name' => 'datefrom',
                            'type' => 'date',
                            'label' => 'Date From',
                            'field' => 'datefrom',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),
                     'dateto' => array(
                            'name' => 'dateto',
                            'type' => 'date',
                            'label' => 'Date To',
                            'field' => 'dateto',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),

                     'tdate1' => array(
                            'name' => 'tdate1',
                            'type' => 'date',
                            'label' => 'Date Start',
                            'field' => 'tdate1',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => true
                     ),
                     'tdate2' => array(
                            'name' => 'tdate2',
                            'type' => 'date',
                            'label' => 'Date End',
                            'field' => 'tdate2',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => true
                     ),
                     'title' => array(
                            'name' => 'title',
                            'type' => 'input',
                            'label' => 'Title',
                            'field' => 'title',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => true
                     ),
                     'conductenglish' => array(
                            'name' => 'conductenglish',
                            'field' => 'conductenglish',
                            'type' => 'input',
                            'label' => 'Conduct English',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'conductchinese' => array(
                            'name' => 'conductchinese',
                            'field' => 'conductchinese',
                            'type' => 'input',
                            'label' => 'Conduct Chinese',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'venue' => array(
                            'name' => 'venue',
                            'type' => 'input',
                            'label' => 'Venue',
                            'field' => 'venue',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => true
                     ),
                     'speaker' => array(
                            'name' => 'speaker',
                            'type' => 'input',
                            'label' => 'Speaker',
                            'field' => 'speaker',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:100px;',
                            'readonly' => true
                     ),
                     'tr' => array(
                            'name' => 'tr',
                            'type' => 'input',
                            'label' => 'Status',
                            'field' => 'tr',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:100px;',
                            'readonly' => true
                     ),
                     'idate' => array(
                            'name' => 'idate',
                            'field' => 'idate',
                            'type' => 'input',
                            'label' => 'Incident Date',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:100px;',
                            'readonly' => true
                     ),
                     'irref' => array(
                            'name' => 'irref',
                            'field' => 'irref',
                            'type' => 'input',
                            'label' => 'Incident Ref',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:100px;',
                            'readonly' => true
                     ),
                     'penalty' => array(
                            'name' => 'penalty',
                            'field' => 'penalty',
                            'type' => 'input',
                            'label' => 'Penalty',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:100px;',
                            'readonly' => true
                     ),
                     'numdays' => array(
                            'name' => 'numdays',
                            'field' => 'numdays',
                            'type' => 'input',
                            'label' => 'No. of Days',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:100px;',
                            'readonly' => true
                     ),
                     'idescription' => array(
                            'name' => 'idescription',
                            'field' => 'idescription',
                            'type' => 'input',
                            'label' => 'Description',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:100px;',
                            'readonly' => true
                     ),
                     'iplace' => array(
                            'name' => 'iplace',
                            'field' => 'iplace',
                            'type' => 'input',
                            'label' => 'Incident Place',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:100px;',
                            'readonly' => true
                     ),
                     'violation' => array(
                            'name' => 'violation',
                            'field' => 'violation',
                            'type' => 'input',
                            'label' => 'Violation',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:100px;',
                            'readonly' => true
                     ),
                     'batch' => array(
                            'name' => 'batch',
                            'field' => 'batch',
                            'type' => 'input',
                            'label' => 'Batch',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'effdate' => array(
                            'name' => 'effdate',
                            'field' => 'effdate',
                            'type' => 'input',
                            'label' => 'Effectivity Date',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:100px;',
                            'readonly' => true
                     ),
                     'scode' => array(
                            'name' => 'scode',
                            'field' => 'scode',
                            'type' => 'input',
                            'label' => 'Status Change',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:100px;',
                            'readonly' => true
                     ),
                     'statdesc' => array(
                            'name' => 'statdesc',
                            'field' => 'statdesc',
                            'type' => 'input',
                            'label' => 'Description',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:100px;',
                            'readonly' => true
                     ),
                     'dayn' => array(
                            'name' => 'dayn',
                            'field' => 'dayn',
                            'type' => 'input',
                            'label' => 'DAYS',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'schedin' => array(
                            'name' => 'schedin',
                            'field' => 'schedin',
                            'type' => 'datetime',
                            'label' => 'Sched-In',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:230px;',
                            'readonly' => false
                     ),
                     'schedout' => array(
                            'name' => 'schedout',
                            'field' => 'schedout',
                            'type' => 'datetime',
                            'label' => 'Sched-Out',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:230px;',
                            'readonly' => false
                     ),
                     'orgschedin' => array(
                            'name' => 'orgschedin',
                            'field' => 'orgschedin',
                            'type' => 'datetime',
                            'label' => 'Original Schedule In',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:230px;',
                            'readonly' => false
                     ),
                     'orgschedout' => array(
                            'name' => 'orgschedout',
                            'field' => 'orgschedout',
                            'type' => 'datetime',
                            'label' => 'Original Schedule Out',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:230px;',
                            'readonly' => false
                     ),
                     'breakin' => array(
                            'name' => 'breakin',
                            'field' => 'breakin',
                            'type' => 'datetime',
                            'label' => 'Lunch-In',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:230px;',
                            'readonly' => false
                     ),
                     'semtime' => array(
                            'name' => 'semtime',
                            'field' => 'semtime',
                            'type' => 'time',
                            'label' => 'Time',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:230px;',
                            'readonly' => false
                     ),
                     'endsemtime' => array(
                            'name' => 'endsemtime',
                            'field' => 'endsemtime',
                            'type' => 'time',
                            'label' => 'Time',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:230px;',
                            'readonly' => false
                     ),
                     'breakout' => array(
                            'name' => 'breakout',
                            'field' => 'breakout',
                            'type' => 'datetime',
                            'label' => 'Lunch-Out',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:230px;',
                            'readonly' => false
                     ),
                     'tothrs' => array(
                            'name' => 'tothrs',
                            'field' => 'tothrs',
                            'type' => 'input',
                            'label' => 'Total Hours',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'brk1stin' => array(
                            'name' => 'brk1stin',
                            'field' => 'brk1stin',
                            'type' => 'datetime',
                            'label' => 'Break-in (AM)',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:230px;',
                            'readonly' => false
                     ),
                     'brk1stout' => array(
                            'name' => 'brk1stout',
                            'field' => 'brk1stout',
                            'type' => 'datetime',
                            'label' => 'Break-out (AM)',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:230px;',
                            'readonly' => false
                     ),
                     'abrk1stin' => array(
                            'name' => 'abrk1stin',
                            'field' => 'abrk1stin',
                            'type' => 'datetime',
                            'label' => 'Break-in (AM)',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:230px;',
                            'readonly' => false
                     ),
                     'abrk1stout' => array(
                            'name' => 'abrk1stout',
                            'field' => 'abrk1stout',
                            'type' => 'datetime',
                            'label' => 'Break-out (AM)',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:230px;',
                            'readonly' => false
                     ),
                     'brk2ndin' => array(
                            'name' => 'brk2ndin',
                            'field' => 'brk2ndin',
                            'type' => 'datetime',
                            'label' => 'Break-in (PM)',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:230px;',
                            'readonly' => false
                     ),
                     'brk2ndout' => array(
                            'name' => 'brk2ndout',
                            'field' => 'brk2ndout',
                            'type' => 'datetime',
                            'label' => 'Break-out (PM)',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:230px;',
                            'readonly' => false
                     ),
                     'abrk2ndin' => array(
                            'name' => 'abrk2ndin',
                            'field' => 'abrk2ndin',
                            'type' => 'datetime',
                            'label' => 'Break-in (PM)',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:230px;',
                            'readonly' => false
                     ),
                     'abrk2ndout' => array(
                            'name' => 'abrk2ndout',
                            'field' => 'abrk2ndout',
                            'type' => 'datetime',
                            'label' => 'Break-out (PM)',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:230px;',
                            'readonly' => false
                     ),
                     'compcode' => array(
                            'name' => 'compcode',
                            'type' => 'lookup',
                            'label' => 'Company Name',
                            'field' => 'compcode',
                            'action' => 'lookupsetup',
                            'lookupclass' => 'lookuprxscompany',
                            'readonly' => false,
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'required' => false
                     ),


                     'pjroxascode1' => array(
                            'name' => 'pjroxascode1',
                            'type' => 'lookup',
                            'label' => 'Project Name',
                            'field' => 'code',
                            'action' => 'lookupsetup',
                            'lookupclass' => 'lookuprjroxas',
                            'readonly' => false,
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'required' => false
                     ),

                     'subpjroxascode' => array(
                            'name' => 'subpjroxascode',
                            'type' => 'lookup',
                            'label' => 'Subproject Name',
                            'field' => 'code',
                            'action' => 'lookupsetup',
                            'lookupclass' => 'lookupsubpjroxascode',
                            'readonly' => false,
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'required' => false
                     ),


                     'blotroxascode' => array(
                            'name' => 'blotroxascode',
                            'type' => 'lookup',
                            'label' => 'Blocklot Roxas',
                            'field' => 'code',
                            'action' => 'lookupsetup',
                            'lookupclass' => 'lookupblocklotroxas',
                            'readonly' => false,
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'required' => false
                     ),

                     'amenityroxascode' => array(
                            'name' => 'amenityroxascode',
                            'type' => 'lookup',
                            'label' => 'Amenity',
                            'field' => 'code',
                            'action' => 'lookupsetup',
                            'lookupclass' => 'lookupamenityroxascode',
                            'readonly' => false,
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'required' => false
                     ),

                     'subamenityroxascode' => array(
                            'name' => 'subamenityroxascode',
                            'type' => 'lookup',
                            'label' => 'Sub Amenity',
                            'field' => 'code',
                            'action' => 'lookupsetup',
                            'lookupclass' => 'lookupsubamenityroxascode',
                            'readonly' => false,
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'required' => false
                     ),

                     'departmentroxascode' => array(
                            'name' => 'departmentroxascode',
                            'type' => 'lookup',
                            'label' => 'Department',
                            'field' => 'code',
                            'action' => 'lookupsetup',
                            'lookupclass' => 'lookupdepartmentroxascode',
                            'readonly' => false,
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'required' => false
                     ),

                     'effectivity' => array(
                            'name' => 'effectivity',
                            'field' => 'effectivity',
                            'type' => 'date',
                            'label' => 'Effectivity of Leave',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'adays' => array(
                            'name' => 'adays',
                            'field' => 'adays',
                            'type' => 'input',
                            'label' => 'Days',
                            'align' => 'text-right',
                            'style' => 'width:150px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'shiftcode' => array(
                            'name' => 'shiftcode',
                            'field' => 'shiftcode',
                            'type' => 'input',
                            'label' => 'Shift',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'changetime' => array(
                            'name' => 'changetime',
                            'type' => 'lookup',
                            'label' => 'Change Time',
                            'class' => 'cschangetime sbccsreadonly',
                            'action' => 'lookupchangetime',
                            'lookupclass' => 'lookupchangetime',
                            'readonly' => true,
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'required' => false
                     ),
                     'actualin' => array(
                            'name' => 'actualin',
                            'field' => 'actualin',
                            'type' => 'datetime',
                            'label' => 'Actual In',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:230px;',
                            'readonly' => false
                     ),
                     'actualout' => array(
                            'name' => 'actualout',
                            'field' => 'actualout',
                            'type' => 'datetime',
                            'label' => 'Actual Out',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:230px;',
                            'readonly' => false
                     ),
                     'actualbrkin' => array(
                            'name' => 'actualbrkin',
                            'field' => 'actualbrkin',
                            'type' => 'datetime',
                            'label' => 'Actual Lunch-In',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:230px;',
                            'readonly' => false
                     ),
                     'actualbrkout' => array(
                            'name' => 'actualbrkout',
                            'field' => 'actualbrkout',
                            'type' => 'datetime',
                            'label' => 'Actual Lunch-Out',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:230px;',
                            'readonly' => false
                     ),
                     'schedbrkin' => array(
                            'name' => 'schedbrkin',
                            'field' => 'schedbrkin',
                            'type' => 'datetime',
                            'label' => 'Sched Lunch-In',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:230px;',
                            'readonly' => false
                     ),
                     'schedbrkout' => array(
                            'name' => 'schedbrkout',
                            'field' => 'schedbrkout',
                            'type' => 'datetime',
                            'label' => 'Sched Lunch-Out',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:230px;',
                            'readonly' => false
                     ),
                     'reghrs' => array(
                            'name' => 'reghrs',
                            'field' => 'reghrs',
                            'type' => 'input',
                            'label' => 'Works Hours',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'absdays' => array(
                            'name' => 'absdays',
                            'field' => 'absdays',
                            'type' => 'input',
                            'label' => 'Absent Hours',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'latehrs' => array(
                            'name' => 'latehrs',
                            'field' => 'latehrs',
                            'type' => 'input',
                            'label' => 'Late Hours',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'underhrs' => array(
                            'name' => 'underhrs',
                            'field' => 'underhrs',
                            'type' => 'input',
                            'label' => 'Undertime Hours',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'othrs' => array(
                            'name' => 'othrs',
                            'field' => 'othrs',
                            'type' => 'input',
                            'label' => 'OT Hours',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'earlyothrs' => array(
                            'name' => 'earlyothrs',
                            'field' => 'earlyothrs',
                            'type' => 'input',
                            'label' => 'Early OT Hours',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'othrsextra' => array(
                            'name' => 'othrsextra',
                            'field' => 'othrsextra',
                            'type' => 'input',
                            'label' => 'OT > 8 Hours',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'apothrs' => array(
                            'name' => 'apothrs',
                            'field' => 'apothrs',
                            'type' => 'input',
                            'label' => 'Approved OT Hours',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'apothrsextra' => array(
                            'name' => 'apothrsextra',
                            'field' => 'apothrsextra',
                            'type' => 'input',
                            'label' => 'Approved OT > 8 Hours',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'ndiffothrs' => array(
                            'name' => 'ndiffothrs',
                            'field' => 'ndiffothrs',
                            'type' => 'input',
                            'label' => 'N-Diff OT Hours',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'apndiffothrs' => array(
                            'name' => 'apndiffothrs',
                            'field' => 'apndiffothrs',
                            'type' => 'input',
                            'label' => 'Approved N-Diff OT Hours',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'ndiffs' => array(
                            'name' => 'ndiffs',
                            'field' => 'ndiffs',
                            'type' => 'input',
                            'label' => 'NDIFF Hours',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'ndiffhrs' => array(
                            'name' => 'ndiffhrs',
                            'field' => 'ndiffhrs',
                            'type' => 'input',
                            'label' => 'NDIFF Hours',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'ndiffot' => array(
                            'name' => 'ndiffot',
                            'field' => 'ndiffot',
                            'type' => 'input',
                            'label' => 'N-Diff OT Hrs',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'othrs' => array(
                            'name' => 'othrs',
                            'field' => 'othrs',
                            'type' => 'input',
                            'label' => 'OT Hours',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'otapproved' => array(
                            'name' => 'otapproved',
                            'type' => 'toggle',
                            'label' => 'Approved',
                            'field' => 'otapproved',
                            'align' => 'text-center',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'isok' => array(
                            'name' => 'isok',
                            'type' => 'toggle',
                            'label' => 'Approved',
                            'field' => 'isok',
                            'align' => 'text-center',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'sync' => array(
                            'name' => 'sync',
                            'type' => 'label',
                            'label' => 'Sync',
                            'field' => 'sync',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'isapproved' => array(
                            'name' => 'isapproved',
                            'type' => 'toggle',
                            'label' => 'Approved',
                            'field' => 'isapproved',
                            'align' => 'text-center',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'ispartialpaid' => array(
                            'name' => 'ispartialpaid',
                            'type' => 'toggle',
                            'label' => 'Partial Payment',
                            'field' => 'ispartialpaid',
                            'align' => 'text-center',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'statusapproved' => array(
                            'name' => 'statusapproved',
                            'type' => 'toggle',
                            'label' => 'Approved',
                            'field' => 'statusapproved',
                            'align' => 'text-center',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'isselected' => array(
                            'name' => 'isselected',
                            'type' => 'toggle',
                            'label' => 'Select',
                            'field' => 'isselected',
                            'align' => 'text-center',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'dname' => array(
                            'name' => 'dname',
                            'type' => 'input',
                            'label' => 'Type',
                            'field' => 'dname',
                            'align' => 'text-right',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),
                     'dqty' => array(
                            'name' => 'dqty',
                            'type' => 'input',
                            'label' => 'Qty',
                            'field' => 'dqty',
                            'align' => 'text-right',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),
                     'diqty' => array(
                            'name' => 'diqty',
                            'type' => 'input',
                            'label' => 'Item Qty',
                            'field' => 'diqty',
                            'align' => 'text-right',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),
                     'drate' => array(
                            'name' => 'drate',
                            'type' => 'input',
                            'label' => 'Item Rate',
                            'field' => 'drate',
                            'align' => 'text-right',
                            'style' => 'width:80px;whiteSpace: normal;min-width:80px;',
                            'readonly' => false
                     ),
                     'daddon' => array(
                            'name' => 'daddon',
                            'type' => 'input',
                            'label' => 'Addon',
                            'field' => 'daddon',
                            'align' => 'text-right',
                            'style' => 'width:80px;whiteSpace: normal;min-width:80px;',
                            'readonly' => false
                     ),


                     //END OF PAYROLL SETUP

                     'attachment' => array(
                            'name' => 'attachment',
                            'field' => 'attachment',
                            'icon' => 'attach_file',
                            'type' => 'icon',
                            'label' => 'Attachment',
                            'align' => 'text-left',
                            'style' => 'width:20px;whiteSpace: normal;min-width:20px;',
                            'readonly' => true
                     ),

                     //vitaline
                     'rebate' => array(
                            'name' => 'rebate',
                            'type' => 'input',
                            'label' => 'Rebate',
                            'field' => 'rebate',
                            'align' => 'text-right',
                            'style' => 'width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'gprofit' => array(
                            'name' => 'gprofit',
                            'type' => 'input',
                            'label' => 'Gross Profit',
                            'field' => 'gprofit',
                            'align' => 'text-right',
                            'style' => 'width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),



                     #GLEN 01.27.2021
                     'e_detail' => array(
                            'name' => 'e_detail',
                            'type' => 'input',
                            'label' => 'Description',
                            'field' => 'e_detail',
                            'align' => 'text-left',
                            'style' => 'width:200px;whiteSpace: normal;min-width:200px;',
                            'readonly' => false
                     ),
                     'date_executed' => array(
                            'name' => 'date_executed',
                            'type' => 'input',
                            'label' => 'Date',
                            'field' => 'date_executed',
                            'align' => 'text-left',
                            'style' => 'width:200px;whiteSpace: normal;min-width:200px;',
                            'readonly' => false
                     ),
                     'querystring' => array(
                            'name' => 'querystring',
                            'type' => 'input',
                            'label' => 'Query String',
                            'field' => 'querystring',
                            'align' => 'text-left',
                            'style' => 'width:200px;whiteSpace: normal;min-width:200px;',
                            'readonly' => false
                     ),
                     'gcsubcode' => [
                            'name' => 'gcsubcode',
                            'type' => 'input',
                            'label' => 'Code',
                            'field' => 'gcsubcode',
                            'align' => 'text-left',
                            'style' => 'width:200px;whiteSpace:normal;min-width:200px;',
                            'readonly' => false
                     ],
                     'gcsubtopic' => [
                            'name' => 'gcsubtopic',
                            'type' => 'input',
                            'label' => 'Topic',
                            'field' => 'topic',
                            'align' => 'text-left',
                            'style' => 'width:200px;whiteSpace:normal;min-width:200px;',
                            'readonly' => false
                     ],
                     'gcsubnoofitems' => [
                            'name' => 'gcsubnoofitems',
                            'type' => 'input',
                            'label' => 'No. of Items',
                            'field' => 'noofitems',
                            'align' => 'text-left',
                            'style' => 'width:200px;whiteSpace:normal;min-width:200px;',
                            'readonly' => false
                     ],
                     'points' => [
                            'name' => 'points',
                            'type' => 'input',
                            'label' => 'Points',
                            'field' => 'noofitems',
                            'align' => 'text-left',
                            'style' => 'width:200px;whiteSpace:normal;min-width:200px;',
                            'readonly' => false
                     ],
                     'gcscoregrade' => [
                            'name' => 'gcscoregrade',
                            'type' => 'input',
                            'label' => 'Score',
                            'field' => 'gcscoregrade',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace:normal;min-width:100px;',
                            'readonly' => false
                     ],
                     'gcpercentgrade' => [
                            'name' => 'gcpercentgrade',
                            'type' => 'input',
                            'label' => 'Percent Grade',
                            'field' => 'gcpercentgrade',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace:normal;min-width:100px;',
                            'readonly' => false
                     ],
                     'gcfinaltotal' => [
                            'name' => 'gcfinaltotal',
                            'type' => 'input',
                            'label' => 'Final Total',
                            'field' => 'gcfinaltotal',
                            'align' => 'text-left',
                            'style' => 'wdith:100px;whiteSpace:normal;min-width:100px;',
                            'readonly' => false
                     ],
                     'gcrcardtotal' => [
                            'name' => 'gcrcardtotal',
                            'type' => 'input',
                            'label' => 'Report Card Total',
                            'field' => 'gcrcardtotal',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace:normal;min-width:100px;',
                            'readonly' => false
                     ],
                     'subproject' => array(
                            'name' => 'subproject',
                            'type' => 'input',
                            'label' => 'Subproject',
                            'field' => 'subproject',
                            'readonly' => false,
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'required' => false
                     ),
                     'projpercent' => array(
                            'name' => 'projpercent',
                            'type' => 'input',
                            'label' => '%Project',
                            'field' => 'projpercent',
                            'readonly' => false,
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'required' => false
                     ),
                     'completed' => array(
                            'name' => 'completed',
                            'type' => 'input',
                            'label' => '%Complete (AP)',
                            'field' => 'completed',
                            'readonly' => true,
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'required' => false
                     ),
                     'stage' => array(
                            'name' => 'stage',
                            'type' => 'input',
                            'label' => 'Stage Name',
                            'field' => 'stage',
                            'readonly' => false,
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'required' => true
                     ),
                     'stagename' => array(
                            'name' => 'stagename',
                            'type' => 'input',
                            'label' => 'Stage Name',
                            'field' => 'stagename',
                            'readonly' => true,
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'required' => true
                     ),
                     'ar' => array(
                            'name' => 'ar',
                            'type' => 'input',
                            'label' => 'AR Amount',
                            'field' => 'ar',
                            'readonly' => true,
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'required' => false
                     ),
                     'rqty' => array(
                            'name' => 'rqty',
                            'type' => 'input',
                            'label' => 'Request Qty',
                            'field' => 'rqty',
                            'align' => 'text-right',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'ap' => array(
                            'name' => 'ap',
                            'type' => 'input',
                            'label' => 'AP Amount',
                            'field' => 'ap',
                            'readonly' => true,
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'required' => false
                     ),
                     'paid' => array(
                            'name' => 'paid',
                            'type' => 'input',
                            'label' => 'Paid',
                            'field' => 'paid',
                            'readonly' => true,
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'required' => false
                     ),
                     'boq' => array(
                            'name' => 'boq',
                            'type' => 'input',
                            'label' => 'Total BOQ (Qty)',
                            'field' => 'boq',
                            'readonly' => true,
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'required' => false
                     ),
                     'pr' => array(
                            'name' => 'pr',
                            'type' => 'input',
                            'label' => 'Total PR (Qty)',
                            'field' => 'pr',
                            'readonly' => true,
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'required' => false
                     ),
                     'po' => array(
                            'name' => 'po',
                            'type' => 'input',
                            'label' => 'Total PO',
                            'field' => 'po',
                            'readonly' => true,
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'required' => false
                     ),
                     'rr' => array(
                            'name' => 'rr',
                            'type' => 'input',
                            'label' => 'Total RR',
                            'field' => 'rr',
                            'readonly' => true,
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'required' => false
                     ),
                     'jr' => array(
                            'name' => 'jr',
                            'type' => 'input',
                            'label' => 'Total JOR',
                            'field' => 'jr',
                            'readonly' => true,
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'required' => false
                     ),
                     'jo' => array(
                            'name' => 'jo',
                            'type' => 'input',
                            'label' => 'Total JO',
                            'field' => 'jo',
                            'readonly' => true,
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'required' => false
                     ),
                     'jc' => array(
                            'name' => 'jc',
                            'type' => 'input',
                            'label' => 'Total JC',
                            'field' => 'jc',
                            'readonly' => true,
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'required' => false
                     ),
                     'mi' => array(
                            'name' => 'mi',
                            'type' => 'input',
                            'label' => 'Total Issued',
                            'field' => 'mi',
                            'readonly' => true,
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'required' => false
                     ),
                     'floor' => array(
                            'name' => 'floor',
                            'type' => 'input',
                            'label' => 'Floor',
                            'field' => 'floor',
                            'readonly' => false,
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'required' => false
                     ),
                     'pallet' => array(
                            'name' => 'pallet',
                            'type' => 'lookup',
                            'label' => 'Pallet',
                            'field' => 'pallet',
                            'action' => 'lookuppallet',
                            'lookupclass' => 'palletstock',
                            'readonly' => false,
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'required' => false
                     ),
                     'pallet2' => array(
                            'name' => 'pallet2',
                            'type' => 'lookup',
                            'label' => 'Destination Pallet',
                            'field' => 'pallet2',
                            'action' => 'lookuppallet',
                            'lookupclass' => 'palletstock2',
                            'readonly' => false,
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'required' => false
                     ),

                     'picker' => array(
                            'name' => 'picker',
                            'type' => 'lookup',
                            'label' => 'Picker',
                            'field' => 'picker',
                            'action' => 'lookupsetup',
                            'lookupclass' => 'pickerstock',
                            'readonly' => false,
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'required' => false
                     ),
                     'checker' => array(
                            'name' => 'checker',
                            'type' => 'label',
                            'label' => 'Checker',
                            'field' => 'checker',
                            'readonly' => false,
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'required' => false
                     ),
                     'checkercount' => array(
                            'name' => 'checkercount',
                            'type' => 'input',
                            'label' => 'Checker Count',
                            'field' => 'checkercount',
                            'readonly' => false,
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'required' => false
                     ),
                     'checkerloc' => array(
                            'name' => 'checkerloc',
                            'type' => 'label',
                            'label' => 'Deposit Location',
                            'field' => 'checkerloc',
                            'readonly' => false,
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'required' => false
                     ),
                     'truck' => array(
                            'name' => 'truck',
                            'type' => 'label',
                            'label' => 'Truck',
                            'field' => 'truck',
                            'readonly' => false,
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'required' => false
                     ),
                     'plateno' => array(
                            'name' => 'plateno',
                            'type' => 'label',
                            'label' => 'Plate No',
                            'field' => 'plateno',
                            'readonly' => false,
                            'align' => 'text-left',
                            'style' => 'width:80px;whiteSpace: normal;min-width:80px;',
                            'required' => false
                     ),
                     'waybill' => array(
                            'name' => 'waybill',
                            'type' => 'label',
                            'label' => 'Way Bill',
                            'field' => 'waybill',
                            'readonly' => false,
                            'align' => 'text-left',
                            'style' => 'width:80px;whiteSpace: normal;min-width:80px;',
                            'required' => false
                     ),
                     'boxcount' => array(
                            'name' => 'boxcount',
                            'type' => 'label',
                            'label' => 'Box Count',
                            'field' => 'boxcount',
                            'readonly' => false,
                            'align' => 'text-left',
                            'style' => 'width:80px;whiteSpace: normal;min-width:80px;',
                            'required' => false
                     ),
                     'receivedate' => array(
                            'name' => 'receivedate',
                            'type' => 'label',
                            'label' => 'Receive Date',
                            'field' => 'receivedate',
                            'readonly' => false,
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'required' => false
                     ),
                     'receiveby' => array(
                            'name' => 'receiveby',
                            'type' => 'label',
                            'label' => 'Receive By',
                            'field' => 'receiveby',
                            'readonly' => false,
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'required' => false
                     ),
                     'courier' => array(
                            'name' => 'courier',
                            'type' => 'label',
                            'label' => 'Courier/Forwarder',
                            'field' => 'courier',
                            'readonly' => false,
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'required' => false
                     ),
                     'added' => array(
                            'name' => 'added',
                            'type' => 'toggle',
                            'label' => 'Assigned',
                            'field' => 'added',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'isquota' => array(
                            'name' => 'isquota',
                            'type' => 'toggle',
                            'label' => 'Quota',
                            'field' => 'isquota',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'classification' => array(
                            'name' => 'classification',
                            'type' => 'input',
                            'label' => 'Classification',
                            'field' => 'classification',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'issued' => array(
                            'name' => 'issued',
                            'type' => 'input',
                            'label' => 'Issued',
                            'field' => 'issued',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'oic1' => array(
                            'name' => 'oic1',
                            'type' => 'lookup',
                            'label' => 'OIC 1',
                            'field' => 'oic1',
                            'align' => 'text-left',
                            'style' => 'text-align:center;width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false,
                            'lookupclass' => 'lookupoicemployee1',
                            'action' => 'lookupsetup'
                     ),
                     'oic2' => array(
                            'name' => 'oic2',
                            'type' => 'lookup',
                            'label' => 'OIC 2',
                            'field' => 'oic2',
                            'align' => 'text-left',
                            'style' => 'text-align:center;width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false,
                            'lookupclass' => 'lookupoicemployee2',
                            'action' => 'lookupsetup'
                     ),
                     'plno' => array(
                            'name' => 'plno',
                            'type' => 'label',
                            'label' => 'Packing List No.',
                            'field' => 'plno',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'shipmentno' => array(
                            'name' => 'shipmentno',
                            'type' => 'label',
                            'label' => 'Shipment No.',
                            'field' => 'shipmentno',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'invoiceno' => array(
                            'name' => 'invoiceno',
                            'type' => 'label',
                            'label' => 'Proforma Invoice No.',
                            'field' => 'invoiceno',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'invoicedate' => array(
                            'name' => 'invoicedate',
                            'field' => 'invoicedate',
                            'type' => 'label',
                            'label' => 'Invoice Date',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'deliverytypename' => array(
                            'name' => 'deliverytypename',
                            'type' => 'label',
                            'label' => 'Delivery Type',
                            'field' => 'deliverytypename',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'boxno' => array(
                            'name' => 'boxno',
                            'type' => 'label',
                            'label' => 'Box No',
                            'field' => 'boxno',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'scandate' => array(
                            'name' => 'scandate',
                            'field' => 'scandate',
                            'type' => 'date',
                            'label' => 'Scan Date',
                            'align' => 'text-left',
                            'style' => 'width: 100px;whiteSpace: normal;min-width:100px;max-width:120px;',
                            'readonly' => false
                     ),
                     'dateacquired' => array(
                            'name' => 'dateacquired',
                            'field' => 'dateacquired',
                            'type' => 'date',
                            'label' => 'Date Acquire',
                            'align' => 'text-left',
                            'style' => 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px;',
                            'readonly' => false
                     ),
                     'scanby' => array(
                            'name' => 'scanby',
                            'type' => 'label',
                            'label' => 'Scan by',
                            'field' => 'scanby',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'canvasstatus' => array(
                            'name' => 'canvasstatus',
                            'type' => 'chip',
                            'label' => 'Status',
                            'field' => 'canvasstatus',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => true,
                            'color' => 'primary',
                            'textcolor' => 'white'
                     ),
                     'isposted' => array(
                            'name' => 'isposted',
                            'type' => 'toggle',
                            'label' => 'Post',
                            'field' => 'isposted',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'listprojectname' => array(
                            'name' => 'projectname',
                            'field' => 'projectname',
                            'type' => 'input',
                            'label' => 'Project',
                            'align' => 'text-left',
                            'style' => 'width: 100px;whiteSpace: normal;min-width:20px;max-width:100px;',
                            'readonly' => false
                     ),
                     'listsubproject' => array(
                            'name' => 'subprojectname',
                            'field' => 'subprojectname',
                            'type' => 'input',
                            'label' => 'Sub-Project',
                            'align' => 'text-left',
                            'style' => 'width: 100px;whiteSpace: normal;min-width:20px;max-width:100px;',
                            'readonly' => false
                     ),
                     'isapprove' => array(
                            'name' => 'isapprove',
                            'type' => 'toggle',
                            'label' => 'Approve',
                            'field' => 'isapprove',
                            'align' => 'text-right',
                            'style' => 'width:40px;whiteSpace: normal;min-width:40px;',
                            'readonly' => false
                     ),
                     'isaccept' => array(
                            'name' => 'isaccept',
                            'type' => 'toggle',
                            'label' => 'Accept',
                            'field' => 'isaccept',
                            'align' => 'text-left',
                            'style' => 'width:40px;whiteSpace: normal;min-width:40px;',
                            'readonly' => false
                     ),
                     'ordate' => array(
                            'name' => 'ordate',
                            'type' => 'date',
                            'label' => 'OR Date',
                            'field' => 'ordate',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'supplier' => array(
                            'name' => 'supplier',
                            'field' => 'supplier',
                            'type' => 'input',
                            'label' => 'Supplier Name',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'sku' => array(
                            'name' => 'sku',
                            'field' => 'sku',
                            'type' => 'input',
                            'label' => 'SKU',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'subcode' => array(
                            'name' => 'subcode',
                            'field' => 'subcode',
                            'type' => 'input',
                            'label' => 'Old SKU',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'amt1' => array(
                            'name' => 'amt1',
                            'type' => 'input',
                            'label' => 'January',
                            'field' => 'amt1',
                            'align' => 'text-right',
                            'style' => 'width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'amt2' => array(
                            'name' => 'amt2',
                            'type' => 'input',
                            'label' => 'February',
                            'field' => 'amt2',
                            'align' => 'text-right',
                            'style' => 'width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'amt3' => array(
                            'name' => 'amt3',
                            'type' => 'input',
                            'label' => 'March',
                            'field' => 'amt3',
                            'align' => 'text-right',
                            'style' => 'width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'amt4' => array(
                            'name' => 'amt4',
                            'type' => 'input',
                            'label' => 'April',
                            'field' => 'amt4',
                            'align' => 'text-right',
                            'style' => 'width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'amt5' => array(
                            'name' => 'amt5',
                            'type' => 'input',
                            'label' => 'May',
                            'field' => 'amt5',
                            'align' => 'text-right',
                            'style' => 'width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'amt6' => array(
                            'name' => 'amt6',
                            'type' => 'input',
                            'label' => 'June',
                            'field' => 'amt6',
                            'align' => 'text-right',
                            'style' => 'width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'amt7' => array(
                            'name' => 'amt7',
                            'type' => 'input',
                            'label' => 'July',
                            'field' => 'amt7',
                            'align' => 'text-right',
                            'style' => 'width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'amt8' => array(
                            'name' => 'amt8',
                            'type' => 'input',
                            'label' => 'August',
                            'field' => 'amt8',
                            'align' => 'text-right',
                            'style' => 'width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'amt9' => array(
                            'name' => 'amt9',
                            'type' => 'input',
                            'label' => 'September',
                            'field' => 'amt9',
                            'align' => 'text-right',
                            'style' => 'width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'amt10' => array(
                            'name' => 'amt10',
                            'type' => 'input',
                            'label' => 'October',
                            'field' => 'amt10',
                            'align' => 'text-right',
                            'style' => 'width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'amt11' => array(
                            'name' => 'amt11',
                            'type' => 'input',
                            'label' => 'November',
                            'field' => 'amt11',
                            'align' => 'text-right',
                            'style' => 'width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'amt12' => array(
                            'name' => 'amt12',
                            'type' => 'input',
                            'label' => 'December',
                            'field' => 'amt12',
                            'align' => 'text-right',
                            'style' => 'width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'janamt' => array(
                            'name' => 'janamt',
                            'type' => 'input',
                            'label' => 'January',
                            'field' => 'janamt',
                            'align' => 'text-right',
                            'style' => 'width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'febamt' => array(
                            'name' => 'febamt',
                            'type' => 'input',
                            'label' => 'February',
                            'field' => 'febamt',
                            'align' => 'text-right',
                            'style' => 'width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'maramt' => array(
                            'name' => 'maramt',
                            'type' => 'input',
                            'label' => 'March',
                            'field' => 'maramt',
                            'align' => 'text-right',
                            'style' => 'width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'apramt' => array(
                            'name' => 'apramt',
                            'type' => 'input',
                            'label' => 'April',
                            'field' => 'apramt',
                            'align' => 'text-right',
                            'style' => 'width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'mayamt' => array(
                            'name' => 'mayamt',
                            'type' => 'input',
                            'label' => 'May',
                            'field' => 'mayamt',
                            'align' => 'text-right',
                            'style' => 'width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'junamt' => array(
                            'name' => 'junamt',
                            'type' => 'input',
                            'label' => 'June',
                            'field' => 'junamt',
                            'align' => 'text-right',
                            'style' => 'width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'julamt' => array(
                            'name' => 'julamt',
                            'type' => 'input',
                            'label' => 'July',
                            'field' => 'julamt',
                            'align' => 'text-right',
                            'style' => 'width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'augamt' => array(
                            'name' => 'augamt',
                            'type' => 'input',
                            'label' => 'August',
                            'field' => 'augamt',
                            'align' => 'text-right',
                            'style' => 'width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'sepamt' => array(
                            'name' => 'sepamt',
                            'type' => 'input',
                            'label' => 'September',
                            'field' => 'sepamt',
                            'align' => 'text-right',
                            'style' => 'width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'octamt' => array(
                            'name' => 'octamt',
                            'type' => 'input',
                            'label' => 'October',
                            'field' => 'octamt',
                            'align' => 'text-right',
                            'style' => 'width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'novamt' => array(
                            'name' => 'novamt',
                            'type' => 'input',
                            'label' => 'November',
                            'field' => 'novamt',
                            'align' => 'text-right',
                            'style' => 'width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'decamt' => array(
                            'name' => 'decamt',
                            'type' => 'input',
                            'label' => 'December',
                            'field' => 'decamt',
                            'align' => 'text-right',
                            'style' => 'width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'total' => array(
                            'name' => 'total',
                            'type' => 'input',
                            'label' => 'Total',
                            'field' => 'total',
                            'align' => 'text-right',
                            'style' => 'text-align:right;width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => true
                     ),
                     'listcost' => array(
                            'name' => 'totalcost',
                            'type' => 'label',
                            'label' => 'Total Cost',
                            'field' => 'totalcost',
                            'align' => 'text-right',
                            'style' => 'text-align:right;width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => true
                     ),
                     'seen' => array(
                            'name' => 'seen',
                            'type' => 'input',
                            'label' => 'Seen',
                            'field' => 'seen',
                            'align' => 'text-right',
                            'style' => 'width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => true
                     ),
                     'totalcost' => array(
                            'name' => 'totalcost',
                            'type' => 'input',
                            'label' => 'Total Cost',
                            'field' => 'totalcost',
                            'align' => 'text-right',
                            'style' => 'width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => true
                     ),
                     'isho' => array(
                            'name' => 'isho',
                            'type' => 'toggle',
                            'label' => 'Head Office',
                            'field' => 'isho',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'ismc' => array(
                            'name' => 'ismc',
                            'type' => 'toggle',
                            'label' => 'Mode of Sales',
                            'field' => 'ismc',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'issp' => array(
                            'name' => 'issp',
                            'type' => 'toggle',
                            'label' => 'Mode of Transaction',
                            'field' => 'issp',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'isexisted' => array(
                            'name' => 'isexisted',
                            'type' => 'toggle',
                            'label' => 'Existing Code',
                            'field' => 'isexisted',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'ispartial' => array(
                            'name' => 'ispartial',
                            'type' => 'toggle',
                            'label' => 'Partial',
                            'field' => 'ispartial',
                            'align' => 'text-left',
                            'style' => 'width:60px;whiteSpace: normal;min-width:60px;',
                            'readonly' => false
                     ),
                     'isshow' => array(
                            'name' => 'isshow',
                            'type' => 'toggle',
                            'label' => 'Show in Income Statement',
                            'field' => 'isshow',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'isprojexp' => array(
                            'name' => 'isprojexp',
                            'type' => 'toggle',
                            'label' => 'Show in Project Cost Expense',
                            'field' => 'isprojexp',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),

                     'iscompute' => array(
                            'name' => 'iscompute',
                            'type' => 'toggle',
                            'label' => 'Compute Child Account',
                            'field' => 'iscompute',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'isparenttotal' => array(
                            'name' => 'isparenttotal',
                            'type' => 'toggle',
                            'label' => 'Show Parent Total',
                            'field' => 'isparenttotal',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'isoracle' => array(
                            'name' => 'isoracle',
                            'type' => 'toggle',
                            'label' => 'Request Oracle Code',
                            'field' => 'isoracle',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'ispa' => array(
                            'name' => 'ispa',
                            'type' => 'toggle',
                            'label' => 'No Price Edit',
                            'field' => 'ispa',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'approvedby_disapprovedby' => array(
                            'name' => 'approvedby_disapprovedby',
                            'type' => 'input',
                            'label' => 'Approved/Disapproved By',
                            'field' => 'approvedby_disapprovedby',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => true
                     ),
                     'date_approved_disapproved' => array(
                            'name' => 'date_approved_disapproved',
                            'type' => 'input',
                            'label' => 'Date Approved/Disapproved',
                            'field' => 'date_approved_disapproved',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => true
                     ),
                     'approvedby_disapprovedbysup' => array(
                            'name' => 'approvedby_disapprovedbysup',
                            'type' => 'input',
                            'label' => 'Approved/Disapproved (Supervisor) By ',
                            'field' => 'approvedby_disapprovedbysup',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => true
                     ),
                     'approvedby_initial' => array(
                            'name' => 'approvedby_initial',
                            'type' => 'input',
                            'label' => 'Initial Approved/Disapproved By ',
                            'field' => 'approvedby_initial',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => true
                     ),
                     'initial_date_approved' => array(
                            'name' => 'initial_date_approved',
                            'type' => 'input',
                            'label' => 'Initial Date Approved/Disapproved',
                            'field' => 'initial_date_approved',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => true
                     ),
                     'approvedby_initial2' => array(
                            'name' => 'approvedby_initial2',
                            'type' => 'input',
                            'label' => 'Initial Approved/Disapproved By ',
                            'field' => 'approvedby_initial2',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => true
                     ),
                     'initial_date_approved2' => array(
                            'name' => 'initial_date_approved2',
                            'type' => 'input',
                            'label' => 'Initial Date Approved/Disapproved',
                            'field' => 'initial_date_approved2',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => true
                     ),
                     'date_approved_disapprovedsup' => array(
                            'name' => 'date_approved_disapprovedsup',
                            'type' => 'input',
                            'label' => 'Date Approved/Disapproved (Supervisor)',
                            'field' => 'date_approved_disapprovedsup',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => true
                     ),
                     'disapproved_remarks' => array(
                            'name' => 'disapproved_remarks',
                            'type' => 'input',
                            'label' => 'Disapproved Remarks',
                            'field' => 'disapproved_remarks',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => true
                     ),
                     'disapproved_remarks2' => array(
                            'name' => 'disapproved_remarks2',
                            'type' => 'input',
                            'label' => 'Disapproved Remarks',
                            'field' => 'disapproved_remarks2',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => true
                     ),
                     'wac' => array(
                            'name' => 'wac',
                            'type' => 'input',
                            'label' => 'Total WAC',
                            'field' => 'wac',
                            'readonly' => true,
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'required' => false
                     ),
                     'supervisorname' => array(
                            'name' => 'supervisorname',
                            'field' => 'supervisorname',
                            'type' => 'input',
                            'label' => 'Supervisor',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => true
                     ),
                     'module' => array(
                            'name' => 'module',
                            'field' => 'doc',
                            'type' => 'label',
                            'label' => 'Module',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => true
                     ),
                     'moduletype' => array(
                            'name' => 'moduletype',
                            'field' => 'moduletype',
                            'type' => 'label',
                            'label' => 'Module Type',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => true
                     ),
                     'start' => array(
                            'name' => 'start',
                            'type' => 'input',
                            'label' => 'Start',
                            'field' => 'start',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'end' => array(
                            'name' => 'end',
                            'type' => 'input',
                            'label' => 'End',
                            'field' => 'end',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'variation' => array(
                            'name' => 'variation',
                            'type' => 'input',
                            'label' => 'Variation',
                            'field' => 'variation',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'current' => array(
                            'name' => 'current',
                            'type' => 'input',
                            'label' => 'Current',
                            'field' => 'current',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'curfrom' => array(
                            'name' => 'curfrom',
                            'type' => 'input',
                            'label' => 'Currency From',
                            'field' => 'curfrom',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'curto' => array(
                            'name' => 'curto',
                            'type' => 'input',
                            'label' => 'Currency To',
                            'field' => 'curto',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'addr' => array(
                            'name' => 'addr',
                            'field' => 'addr',
                            'type' => 'input',
                            'label' => 'Address',
                            'align' => 'text-left',
                            'style' => 'width: 100px;whiteSpace: normal;min-width:20px;max-width:100px;',
                            'readonly' => false
                     ),
                     'isbilling' => array(
                            'name' => 'isbilling',
                            'type' => 'toggle',
                            'label' => 'Billing',
                            'field' => 'isbilling',
                            'align' => 'text-left',
                            'style' => 'width:20px;whiteSpace: normal;min-width:30px;',
                            'readonly' => false
                     ),
                     'isshipping' => array(
                            'name' => 'isshipping',
                            'type' => 'toggle',
                            'label' => 'Shipping',
                            'field' => 'isshipping',
                            'align' => 'text-left',
                            'style' => 'width:20px;whiteSpace: normal;min-width:30px;',
                            'readonly' => false
                     ),
                     'rolename' => array(
                            'name' => 'rolename',
                            'type' => 'lookup',
                            'label' => 'Role',
                            'field' => 'rolename',
                            'align' => 'text-left',
                            'style' => 'text-align:right;width:180px;whiteSpace: normal;min-width:180px;',
                            'readonly' => true,
                            'lookupclass' => 'role',
                            'action' => 'lookupsetup'
                     ),
                     'issues' => array(
                            'name' => 'issues',
                            'type' => 'input',
                            'label' => 'Issue',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace:normal;min-width:100px;max-width:150px;',
                            'readonly' => false
                     ),
                     'industryname' => array(
                            'name' => 'industryname',
                            'type' => 'input',
                            'label' => 'Name',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace:normal;min-width:100px;max-width:150px;',
                            'readonly' => false
                     ),
                     'documenttype' => array(
                            'name' => 'documenttype',
                            'type' => 'input',
                            'label' => 'Document Type',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace:normal;min-width:100px;max-width:150px;',
                            'readonly' => false
                     ),
                     'details' => array(
                            'name' => 'details',
                            'type' => 'input',
                            'label' => 'Details',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace:normal;min-width:100px;max-width:150px;',
                            'readonly' => false
                     ),
                     'divisionname' => array(
                            'name' => 'divisionname',
                            'type' => 'input',
                            'label' => 'Name',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace:normal;min-width:100px;max-width:150px;',
                            'readonly' => false
                     ),
                     'statussort' => array(
                            'name' => 'statussort',
                            'type' => 'input',
                            'label' => 'Sort',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace:normal;min-width:100px;max-width:150px;',
                            'readonly' => false
                     ),
                     'usertype' => array(
                            'name' => 'usertype',
                            'type' => 'lookup',
                            'label' => 'User Type',
                            'field' => 'usertype',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace:normal;min-width:100px;max-width:150px;',
                            'readonly' => true,
                            'lookupclass' => 'lookupusers',
                            'action' => 'lookupsetup',
                            'class' => 'csusers sbccsreadonly'
                     ),
                     'semtype' => array(
                            'name' => 'semtype',
                            'type' => 'lookup',
                            'label' => 'Type',
                            'field' => 'semtype',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace:normal;min-width:100px;max-width:150px;',
                            'readonly' => true,
                            'lookupclass' => 'lookupsemtype',
                            'action' => 'lookupsetup',
                            'class' => 'csusers sbccsreadonly'
                     ),
                     'username' => array(
                            'name' => 'username',
                            'type' => 'input',
                            'label' => 'Username',
                            'field' => 'username',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace:normal;min-width:100px;max-width:150px;',
                            'readonly' => true
                     ),
                     'name' => array(
                            'name' => 'name',
                            'type' => 'input',
                            'label' => 'Name',
                            'field' => 'name',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace:normal;min-width:100px;max-width:150px;',
                            'readonly' => false
                     ),
                     'picture' => array(
                            'name' => 'picture',
                            'type' => 'image',
                            'label' => 'Image',
                            'field' => 'picture',
                            'align' => 'text-left',
                            'style' => 'width:50px;whiteSpace:normal;min-width:50px;max-width:50px;',
                            'readonly' => false
                     ),
                     'pincode' => array(
                            'name' => 'pincode',
                            'type' => 'input',
                            'label' => 'Cashier`s pincode',
                            'field' => 'pincode',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace:normal;min-width:100px;max-width:150px;',
                            'readonly' => false
                     ),
                     'pincode2' => array(
                            'name' => 'pincode2',
                            'type' => 'input',
                            'label' => 'Supervisor`s pincode',
                            'field' => 'pincode2',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace:normal;min-width:100px;max-width:150px;',
                            'readonly' => false
                     ),
                     'statusdoc' => array(
                            'name' => 'statusdoc',
                            'type' => 'lookup',
                            'label' => 'Status',
                            'field' => 'statusdoc',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace:normal;min-width:150px;max-width:150px;',
                            'readonly' => true,
                            'lookupclass' => 'lookupdtstatuslist',
                            'action' => 'lookupsetup'
                     ),
                     'dtstatus' => array(
                            'name' => 'dtstatus',
                            'type' => 'input',
                            'label' => 'Status',
                            'field' => 'dtstatus',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace:normal;min-width:150px;max-width:150px;',
                            'readonly' => false
                     ),
                     'whname' => array(
                            'name' => 'whname',
                            'type' => 'input',
                            'label' => 'Warehouse',
                            'field' => 'whname',
                            'align' => 'text-center',
                            'style' => 'text-align:right;width:180px;whiteSpace: normal;min-width:180px;',
                            'readonly' => true
                     ),
                     'ipaddress' => array(
                            'name' => 'ipaddress',
                            'field' => 'ipaddress',
                            'type' => 'input',
                            'label' => 'IP address',
                            'align' => 'text-left',
                            'style' => 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px;',
                            'readonly' => false
                     ),
                     'incomegrp' => array(
                            'name' => 'incomegrp',
                            'field' => 'incomegrp',
                            'type' => 'input',
                            'label' => 'Income Group',
                            'align' => 'text-left',
                            'style' => 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px;',
                            'readonly' => false
                     ),

                     'localport' => array(
                            'name' => 'localport',
                            'field' => 'localport',
                            'type' => 'input',
                            'label' => 'Port',
                            'align' => 'text-left',
                            'style' => 'width: 120px;whiteSpace: normal;min-width:120px;max-width:120px;',
                            'readonly' => false
                     ),
                     'localdb' => array(
                            'name' => 'localdb',
                            'field' => 'localdb',
                            'type' => 'input',
                            'label' => 'Database',
                            'align' => 'text-left',
                            'style' => 'width: 120px;whiteSpace: normal;min-width:120px;max-width:120px;',
                            'readonly' => false
                     ),
                     'password' => array(
                            'name' => 'password',
                            'field' => 'password',
                            'type' => 'input',
                            'label' => 'Password',
                            'align' => 'text-left',
                            'style' => 'width: 120px;whiteSpace: normal;min-width:120px;max-width:120px;',
                            'readonly' => false
                     ),
                     'compname' => array(
                            'name' => 'compname',
                            'field' => 'compname',
                            'type' => 'input',
                            'label' => 'Company Name',
                            'align' => 'text-left',
                            'style' => 'width: 180px;whiteSpace: normal;min-width:180px;max-width:180px;',
                            'readonly' => false
                     ),
                     'compaddress' => array(
                            'name' => 'compaddress',
                            'field' => 'compaddress',
                            'type' => 'input',
                            'label' => 'Company Address',
                            'align' => 'text-left',
                            'style' => 'width: 180px;whiteSpace: normal;min-width:180px;max-width:180px;',
                            'readonly' => false
                     ),
                     'comptel' => array(
                            'name' => 'comptel',
                            'field' => 'comptel',
                            'type' => 'input',
                            'label' => 'Tel#',
                            'align' => 'text-left',
                            'style' => 'width: 120px;whiteSpace: normal;min-width:120px;max-width:120px;',
                            'readonly' => false
                     ),
                     'operatedby' => array(
                            'name' => 'operatedby',
                            'field' => 'operatedby',
                            'type' => 'input',
                            'label' => 'Operated By',
                            'align' => 'text-left',
                            'style' => 'width: 180px;whiteSpace: normal;min-width:180px;max-width:180px;',
                            'readonly' => false
                     ),
                     'seniorpwddisc' => array(
                            'name' => 'seniorpwddisc',
                            'field' => 'seniorpwddisc',
                            'type' => 'input',
                            'label' => 'Senior/PWD Disc',
                            'align' => 'text-left',
                            'style' => 'width: 120px;whiteSpace: normal;min-width:120px;max-width:120px;',
                            'readonly' => false
                     ),
                     'footer1' => array(
                            'name' => 'footer1',
                            'field' => 'footer1',
                            'type' => 'input',
                            'label' => 'Footer 1',
                            'align' => 'text-left',
                            'style' => 'width: 120px;whiteSpace: normal;min-width:120px;max-width:120px;',
                            'readonly' => false
                     ),
                     'footer2' => array(
                            'name' => 'footer2',
                            'field' => 'footer2',
                            'type' => 'input',
                            'label' => 'Footer 2',
                            'align' => 'text-left',
                            'style' => 'width: 120px;whiteSpace: normal;min-width:120px;max-width:120px;',
                            'readonly' => false
                     ),
                     'footer3' => array(
                            'name' => 'footer3',
                            'field' => 'footer3',
                            'type' => 'input',
                            'label' => 'Footer 3',
                            'align' => 'text-left',
                            'style' => 'width: 120px;whiteSpace: normal;min-width:120px;max-width:120px;',
                            'readonly' => false
                     ),
                     'footer4' => array(
                            'name' => 'footer4',
                            'field' => 'footer4',
                            'type' => 'input',
                            'label' => 'Footer 4',
                            'align' => 'text-left',
                            'style' => 'width: 120px;whiteSpace: normal;min-width:120px;max-width:120px;',
                            'readonly' => false
                     ),
                     'footer5' => array(
                            'name' => 'footer5',
                            'field' => 'footer5',
                            'type' => 'input',
                            'label' => 'Footer 5',
                            'align' => 'text-left',
                            'style' => 'width: 120px;whiteSpace: normal;min-width:120px;max-width:120px;',
                            'readonly' => false
                     ),
                     'serialno' => array(
                            'name' => 'serialno',
                            'field' => 'serialno',
                            'type' => 'input',
                            'label' => 'Serial #',
                            'align' => 'text-left',
                            'style' => 'width: 120px;whiteSpace: normal;min-width:120px;max-width:120px;',
                            'readonly' => false
                     ),

                     'returnrem' => array(
                            'name' => 'returnrem',
                            'field' => 'returnrem',
                            'type' => 'input',
                            'label' => 'Return Notes',
                            'align' => 'text-left',
                            'style' => 'width: 120px;whiteSpace: normal;min-width:120px;max-width:120px;',
                            'readonly' => false
                     ),

                     'permitno' => array(
                            'name' => 'permitno',
                            'field' => 'permitno',
                            'type' => 'input',
                            'label' => 'Permit #',
                            'align' => 'text-left',
                            'style' => 'width: 120px;whiteSpace: normal;min-width:120px;max-width:120px;',
                            'readonly' => false
                     ),
                     'accredno' => array(
                            'name' => 'accredno',
                            'field' => 'accredno',
                            'type' => 'input',
                            'label' => 'Accreditation #',
                            'align' => 'text-left',
                            'style' => 'width: 120px;whiteSpace: normal;min-width:120px;max-width:120px;',
                            'readonly' => false
                     ),
                     'dateissued' => array(
                            'name' => 'dateissued',
                            'field' => 'dateissued',
                            'type' => 'input',
                            'label' => 'Date Issued',
                            'align' => 'text-left',
                            'style' => 'width: 120px;whiteSpace: normal;min-width:120px;max-width:120px;',
                            'readonly' => false
                     ),
                     'validuntil' => array(
                            'name' => 'validuntil',
                            'field' => 'validuntil',
                            'type' => 'input',
                            'label' => 'Valid Until',
                            'align' => 'text-left',
                            'style' => 'width: 120px;whiteSpace: normal;min-width:120px;max-width:120px;',
                            'readonly' => false
                     ),
                     'stock_projectname' => array(
                            'name' => 'stock_projectname',
                            'type' => 'lookup',
                            'label' => 'Project',
                            'field' => 'stock_projectname',
                            'align' => 'text-left',
                            'style' => 'width:250px;whiteSpace: normal;min-width:250px;text-align:left;',
                            'readonly' => false,
                            'lookupclass' => 'lookup_stock_project',
                            'action' => 'lookup_stock_project'
                     ),
                     'asset_stockgroup' => array(
                            'name' => 'asset_stockgroup',
                            'type' => 'lookup',
                            'label' => 'Asset',
                            'field' => 'asset_stockgroup',
                            'align' => 'text-left',
                            'style' => 'width:250px;whiteSpace: normal;min-width:250px;',
                            'readonly' => false,
                            'lookupclass' => 'lookupasset_stockgroup',
                            'action' => 'lookupasset_stockgroup'
                     ),
                     'liability_stockgroup' => array(
                            'name' => 'liability_stockgroup',
                            'type' => 'lookup',
                            'label' => 'Liability',
                            'field' => 'asset_stockgroup',
                            'align' => 'text-left',
                            'style' => 'width:250px;whiteSpace: normal;min-width:250px;',
                            'readonly' => false,
                            'lookupclass' => 'lookupliability_stockgroup',
                            'action' => 'lookupliability_stockgroup'
                     ),
                     'expense_stockgroup' => array(
                            'name' => 'expense_stockgroup',
                            'type' => 'lookup',
                            'label' => 'Expense',
                            'field' => 'asset_stockgroup',
                            'align' => 'text-left',
                            'style' => 'width:250px;whiteSpace: normal;min-width:250px;',
                            'readonly' => false,
                            'lookupclass' => 'lookupexpense_stockgroup',
                            'action' => 'lookupexpense_stockgroup'
                     ),
                     'revenue_stockgroup' => array(
                            'name' => 'revenue_stockgroup',
                            'type' => 'lookup',
                            'label' => 'Revenue',
                            'field' => 'asset_stockgroup',
                            'align' => 'text-left',
                            'style' => 'width:250px;whiteSpace: normal;min-width:250px;',
                            'readonly' => false,
                            'lookupclass' => 'lookuprevenue_stockgroup',
                            'action' => 'lookuprevenue_stockgroup'
                     ),
                     'phasename' => array(
                            'name' => 'phasename',
                            'type' => 'lookup',
                            'label' => 'Phase',
                            'field' => 'phasename',
                            'align' => 'text-left',
                            'style' => 'width:250px;whiteSpace: normal;min-width:250px;',
                            'readonly' => false,
                            'lookupclass' => 'lookup_phasename',
                            'action' => 'lookup_phasename'
                     ),
                     'housemodel' => array(
                            'name' => 'housemodel',
                            'type' => 'lookup',
                            'label' => 'House Model',
                            'field' => 'housemodel',
                            'align' => 'text-left',
                            'style' => 'width:250px;whiteSpace: normal;min-width:250px;',
                            'readonly' => false,
                            'lookupclass' => 'lookup_housemodel',
                            'action' => 'lookup_housemodel',
                     ),
                     'blocklot' => array(
                            'name' => 'phasename',
                            'type' => 'input',
                            'label' => 'BLK',
                            'field' => 'phasename',
                            'align' => 'text-left',
                            'class' => 'csblocklot sbccsreadonly',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false,
                     ),
                     'amenityname' => array(
                            'name' => 'amenityname',
                            'type' => 'lookup',
                            'label' => 'Amenity',
                            'field' => 'amenity',
                            'align' => 'text-left',
                            'style' => 'width:250px;whiteSpace: normal;min-width:250px;',
                            'readonly' => false,
                            'lookupclass' => 'lookupamenity',
                            'action' => 'lookupamenity'
                     ),
                     'subamenityname' => array(
                            'name' => 'subamenityname',
                            'type' => 'lookup',
                            'label' => 'Sub-Amenity',
                            'field' => 'subamenityname',
                            'align' => 'text-left',
                            'style' => 'width:250px;whiteSpace: normal;min-width:250px;',
                            'readonly' => false,
                            'lookupclass' => 'lookupsubamenity',
                            'action' => 'lookupsubamenity'
                     ),
                     'agent' => array(
                            'name' => 'agent',
                            'field' => 'agent',
                            'type' => 'input',
                            'label' => 'Agent',
                            'align' => 'text-left',
                            'style' => 'width: 200px;whiteSpace: normal;min-width:200px;max-width:200px;',
                            'readonly' => true
                     ),
                     'branch' => array(
                            'name' => 'branch',
                            'field' => 'branch',
                            'type' => 'input',
                            'label' => 'Branch',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => true
                     ),
                     'accessories' => array(
                            'name' => 'accessories',
                            'field' => 'accessories',
                            'type' => 'wysiwyg',
                            'label' => 'Accessories',
                            'align' => 'text-left',
                            'style' => 'width: 100px;whiteSpace: normal;min-width:20px; max-width:100px;',
                            'readonly' => false
                     ),
                     'poterms' => array(
                            'name' => 'poterms',
                            'field' => 'poterms',
                            'type' => 'wysiwyg',
                            'label' => 'PO Terms',
                            'align' => 'text-left',
                            'style' => 'width: 100%;whiteSpace: normal;min-width:100%; max-width:100%; height: 500px;',
                            'readonly' => false
                     ),
                     'itemdescription' => array(
                            'name' => 'itemdescription',
                            'field' => 'itemdescription',
                            'type' => 'wysiwyg',
                            'label' => 'Item Description',
                            'align' => 'text-left',
                            'style' => 'width:210px;whiteSpace: normal;min-width:210px;text-align:left;',
                            'readonly' => false
                     ),
                     'otherdesc' => array(
                            'name' => 'otherdesc',
                            'field' => 'otherdesc',
                            'type' => 'input',
                            'label' => 'Other Description',
                            'align' => 'text-left',
                            'style' => 'width:210px;whiteSpace: normal;min-width:210px;text-align:left;',
                            'readonly' => false
                     ),
                     'substage' => array(
                            'name' => 'substage',
                            'type' => 'input',
                            'label' => 'Activity',
                            'field' => 'substage',
                            'readonly' => false,
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'required' => true
                     ),
                     'substagename' => array(
                            'name' => 'substagename',
                            'type' => 'lookup',
                            'label' => 'Substage',
                            'field' => 'substagename',
                            'align' => 'text-left',
                            'style' => 'width:250px;whiteSpace: normal;min-width:250px;',
                            'readonly' => false,
                            'lookupclass' => 'lookupsubstage',
                            'action' => 'lookupsubstage',
                            'addedparams' => ['stageid'],
                     ),
                     'subactid' => array(
                            'name' => 'subactid',
                            'type' => 'input',
                            'label' => 'Sub Activity ID',
                            'field' => 'subactid',
                            'readonly' => false,
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'required' => true
                     ),
                     'subactivity' => array(
                            'name' => 'subactivity',
                            'type' => 'input',
                            'label' => 'Sub Activity',
                            'field' => 'subactivity',
                            'readonly' => false,
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'required' => true
                     ),
                     'subactivityname' => array(
                            'name' => 'subactivityname',
                            'type' => 'input',
                            'label' => 'Sub Activity',
                            'field' => 'subactivityname',
                            'readonly' => false,
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'required' => true
                     ),
                     'starttime' => array(
                            'name' => 'starttime',
                            'field' => 'starttime',
                            'type' => 'input',
                            'label' => 'Start Time',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'readonly' => true,
                     ),
                     'endtime' => array(
                            'name' => 'endtime',
                            'field' => 'endtime',
                            'type' => 'input',
                            'label' => 'End Time',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal; min-width:20px;max-width:30px;',
                            'readonly' => true,
                     ),
                     'salutation' => array(
                            'name' => 'salutation',
                            'field' => 'salutation',
                            'type' => 'lookup',
                            'lookupclass' => 'lookupsalutation',
                            'action' => 'lookupsetup',
                            'label' => 'Salutation',
                            'align' => 'text-left',
                            'style' => 'width: 200px;whiteSpace: normal; min-width:200px;max-width:200px;',
                            'readonly' => false,
                     ),
                     'fname' => array(
                            'name' => 'fname',
                            'field' => 'fname',
                            'type' => 'input',
                            'label' => 'Firstname',
                            'align' => 'text-left',
                            'style' => 'width: 200px;whiteSpace: normal; min-width:200px;max-width:200px;',
                            'readonly' => false,
                     ),
                     'mname' => array(
                            'name' => 'mname',
                            'field' => 'mname',
                            'type' => 'input',
                            'label' => 'Middlename',
                            'align' => 'text-left',
                            'style' => 'width: 200px;whiteSpace: normal; min-width:200px;max-width:200px;',
                            'readonly' => false,
                     ),
                     'lname' => array(
                            'name' => 'lname',
                            'field' => 'lname',
                            'type' => 'input',
                            'label' => 'Lastname',
                            'align' => 'text-left',
                            'style' => 'width: 200px;whiteSpace: normal; min-width:200px;max-width:200px;',
                            'readonly' => false,
                     ),
                     'email' => array(
                            'name' => 'email',
                            'field' => 'email',
                            'type' => 'input',
                            'label' => 'Email',
                            'align' => 'text-left',
                            'style' => 'width: 200px;whiteSpace: normal; min-width:200px;max-width:200px;',
                            'readonly' => false,
                     ),
                     'designation' => array(
                            'name' => 'designation',
                            'field' => 'designation',
                            'type' => 'input',
                            'label' => 'Designation',
                            'align' => 'text-left',
                            'style' => 'width: 200px;whiteSpace: normal; min-width:200px;max-width:200px;',
                            'readonly' => false,
                     ),
                     'activity' => array(
                            'name' => 'activity',
                            'field' => 'activity',
                            'type' => 'input',
                            'label' => 'Activity',
                            'align' => 'text-left',
                            'style' => 'width: 200px;whiteSpace: normal; min-width:200px;max-width:200px;',
                            'readonly' => false,
                     ),
                     'dept' => array(
                            'field' => 'dept',
                            'name' => 'dept',
                            'type' => 'lookup',
                            'label' => 'Department',
                            'align' => 'text-left',
                            'style' => 'width:200px;whiteSpace: normal;min-width:200px;',
                            'readonly' => false,
                            'lookupclass' => 'lookupdept',
                            'action' => 'lookupsetup'
                     ),
                     'calltype' => array(
                            'name' => 'calltype',
                            'type' => 'lookup',
                            'label' => 'Call Type',
                            'field' => 'calltype',
                            'align' => 'text-center',
                            'style' => 'text-align:right;width:180px;whiteSpace: normal;min-width:180px;',
                            'readonly' => false,
                            'class' => 'cscalltype sbccsenablealways',
                            'lookupclass' => 'calltype',
                            'action' => 'lookupsetup'
                     ),
                     'errandtype' => array(
                            'name' => 'errandtype',
                            'field' => 'errandtype',
                            'type' => 'input',
                            'label' => 'Errand Type',
                            'align' => 'text-left',
                            'style' => 'width: 100px;whiteSpace: normal;min-width:100px;max-width:100px;',
                            'readonly' => false
                     ),
                     'contact' => array(
                            'name' => 'contact',
                            'field' => 'contact',
                            'type' => 'input',
                            'label' => 'Contact Person',
                            'align' => 'text-left',
                            'style' => 'width: 120px;whiteSpace: normal;min-width:120px;max-width:120px;',
                            'readonly' => false
                     ),
                     'addrtype' => array(
                            'name' => 'addrtype',
                            'field' => 'addrtype',
                            'type' => 'editlookup',
                            'action' => 'lookupsetup',
                            'lookupclass' => 'addrtype',
                            'class' => 'csaddrtype sbccsenablealways',
                            'label' => 'Address Type',
                            'align' => 'text-left',
                            'style' => 'width: 200px;whiteSpace: normal;min-width:200px;max-width:200px;',
                            'readonly' => false
                     ),
                     'addrline1' => array(
                            'name' => 'addrline1',
                            'field' => 'addrline1',
                            'type' => 'input',
                            'label' => 'Address Line 1',
                            'align' => 'text-left',
                            'style' => 'width: 200px;whiteSpace: normal;min-width:200px;max-width:200px;',
                            'readonly' => false
                     ),
                     'addrline2' => array(
                            'name' => 'addrline2',
                            'field' => 'addrline2',
                            'type' => 'input',
                            'label' => 'Address Line 2',
                            'align' => 'text-left',
                            'style' => 'width: 200px;whiteSpace: normal;min-width:200px;max-width:200px;',
                            'readonly' => false
                     ),
                     'city' => array(
                            'name' => 'city',
                            'field' => 'city',
                            'type' => 'input',
                            'label' => 'City/Town',
                            'align' => 'text-left',
                            'style' => 'width: 200px;whiteSpace: normal;min-width:200px;max-width:200px;',
                            'readonly' => false
                     ),
                     'province' => array(
                            'name' => 'province',
                            'field' => 'province',
                            'type' => 'input',
                            'label' => 'Province',
                            'align' => 'text-left',
                            'style' => 'width: 200px;whiteSpace: normal;min-width:200px;max-width:200px;',
                            'readonly' => false
                     ),
                     'country' => array(
                            'name' => 'country',
                            'field' => 'country',
                            'type' => 'input',
                            'label' => 'Country',
                            'align' => 'text-left',
                            'style' => 'width: 200px;whiteSpace: normal;min-width:200px;max-width:200px;',
                            'readonly' => false
                     ),
                     'fax' => array(
                            'name' => 'fax',
                            'field' => 'fax',
                            'type' => 'input',
                            'label' => 'Fax',
                            'align' => 'text-left',
                            'style' => 'width: 200px;whiteSpace: normal;min-width:200px;max-width:200px;',
                            'readonly' => false
                     ),
                     'contactno' => array(
                            'name' => 'contactno',
                            'field' => 'contactno',
                            'type' => 'input',
                            'label' => 'Contact #',
                            'align' => 'text-left',
                            'style' => 'width: 120px;whiteSpace: normal;min-width:120px;max-width:120px;',
                            'readonly' => false
                     ),

                     'rcmonthjan' => array(
                            'name' => 'rcmonthjan',
                            'field' => 'rcmonthjan',
                            'type' => 'input',
                            'label' => 'January',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace:normal;min-width:100px;max-width:100px;',
                            'readonly' => false
                     ),
                     'rcmonthfeb' => array(
                            'name' => 'rcmonthfeb',
                            'field' => 'rcmonthfeb',
                            'type' => 'input',
                            'label' => 'February',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace:normal;min-width:100px;max-width:100px;',
                            'readonly' => false
                     ),
                     'rcmonthmar' => array(
                            'name' => 'rcmonthmar',
                            'field' => 'rcmonthmar',
                            'type' => 'input',
                            'label' => 'March',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace:normal;min-width:100px;max-width:100px;',
                            'readonly' => false
                     ),
                     'rcmonthapr' => array(
                            'name' => 'rcmonthapr',
                            'field' => 'rcmonthapr',
                            'type' => 'input',
                            'label' => 'April',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace:normal;min-width:100px;max-width:100px;',
                            'readonly' => false
                     ),
                     'rcmonthmay' => array(
                            'name' => 'rcmonthmay',
                            'field' => 'rcmonthmay',
                            'type' => 'input',
                            'label' => 'May',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace:normal;min-width:100px;max-width:100px;',
                            'readonly' => false
                     ),
                     'rcmonthjun' => array(
                            'name' => 'rcmonthjun',
                            'field' => 'rcmonthjun',
                            'type' => 'input',
                            'label' => 'June',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace:normal;min-width:100px;max-width:100px;',
                            'readonly' => false
                     ),
                     'rcmonthjul' => array(
                            'name' => 'rcmonthjul',
                            'field' => 'rcmonthjul',
                            'type' => 'input',
                            'label' => 'July',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace:normal;min-width:100px;max-width:100px;',
                            'readonly' => false
                     ),
                     'rcmonthaug' => array(
                            'name' => 'rcmonthaug',
                            'field' => 'rcmonthaug',
                            'type' => 'input',
                            'label' => 'August',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace:normal;min-width:100px;max-width:100px;',
                            'readonly' => false
                     ),
                     'rcmonthsep' => array(
                            'name' => 'rcmonthsep',
                            'field' => 'rcmonthsep',
                            'type' => 'input',
                            'label' => 'September',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace:normal;min-width:100px;max-width:100px;',
                            'readonly' => false
                     ),
                     'rcmonthoct' => array(
                            'name' => 'rcmonthoct',
                            'field' => 'rcmonthoct',
                            'type' => 'input',
                            'label' => 'October',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace:normal;min-width:100px;max-width:100px;',
                            'readonly' => false
                     ),
                     'rcmonthnov' => array(
                            'name' => 'rcmonthnov',
                            'field' => 'rcmonthnov',
                            'type' => 'input',
                            'label' => 'November',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace:normal;min-width:100px;max-width:100px;',
                            'readonly' => false
                     ),
                     'rcmonthdec' => array(
                            'name' => 'rcmonthdec',
                            'field' => 'rcmonthdec',
                            'type' => 'input',
                            'label' => 'December',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace:normal;min-width:100px;max-width:100px;',
                            'readonly' => false
                     ),
                     'tjan' => array(
                            'name' => 'tjan',
                            'field' => 'tjan',
                            'type' => 'input',
                            'label' => 'Jan Tardy',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace:normal;min-width:100px;max-width:100px;',
                            'readonly' => false
                     ),
                     'tfeb' => array(
                            'name' => 'tfeb',
                            'field' => 'tfeb',
                            'type' => 'input',
                            'label' => 'Feb Tardy',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace:normal;min-width:100px;max-width:100px;',
                            'readonly' => false
                     ),
                     'tmar' => array(
                            'name' => 'tmar',
                            'field' => 'tmar',
                            'type' => 'input',
                            'label' => 'Mar Tardy',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace:normal;min-width:100px;max-width:100px;',
                            'readonly' => false
                     ),
                     'tapr' => array(
                            'name' => 'tapr',
                            'field' => 'tapr',
                            'type' => 'input',
                            'label' => 'Apr Tardy',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace:normal;min-width:100px;max-width:100px;',
                            'readonly' => false
                     ),
                     'tmay' => array(
                            'name' => 'tmay',
                            'field' => 'tmay',
                            'type' => 'input',
                            'label' => 'May Tardy',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace:normal;min-width:100px;max-width:100px;',
                            'readonly' => false
                     ),
                     'tjun' => array(
                            'name' => 'tjun',
                            'field' => 'tjun',
                            'type' => 'input',
                            'label' => 'Jun Tardy',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace:normal;min-width:100px;max-width:100px;',
                            'readonly' => false
                     ),
                     'tjul' => array(
                            'name' => 'tjul',
                            'field' => 'tjul',
                            'type' => 'input',
                            'label' => 'Jul Tardy',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace:normal;min-width:100px;max-width:100px;',
                            'readonly' => false
                     ),
                     'taug' => array(
                            'name' => 'taug',
                            'field' => 'taug',
                            'type' => 'input',
                            'label' => 'Aug Tardy',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace:normal;min-width:100px;max-width:100px;',
                            'readonly' => false
                     ),
                     'tsep' => array(
                            'name' => 'tsep',
                            'field' => 'tsep',
                            'type' => 'input',
                            'label' => 'Sep Tardy',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace:normal;min-width:100px;max-width:100px;',
                            'readonly' => false
                     ),
                     'toct' => array(
                            'name' => 'toct',
                            'field' => 'toct',
                            'type' => 'input',
                            'label' => 'Oct Tardy',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace:normal;min-width:100px;max-width:100px;',
                            'readonly' => false
                     ),
                     'tnov' => array(
                            'name' => 'tnov',
                            'field' => 'tnov',
                            'type' => 'input',
                            'label' => 'Nov Tardy',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace:normal;min-width:100px;max-width:100px;',
                            'readonly' => false
                     ),
                     'tdec' => array(
                            'name' => 'tdec',
                            'field' => 'tdec',
                            'type' => 'input',
                            'label' => 'Dec Tardy',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace:normal;min-width:100px;max-width:100px;',
                            'readonly' => false
                     ),
                     'rcdayspresent' => array(
                            'name' => 'rcdayspresent',
                            'field' => 'rcdayspresent',
                            'type' => 'input',
                            'label' => 'Total',
                            'align' => 'text-left',
                            'style' => 'width:200px;whiteSpace:normal;min-width:200px;max-width:200px;',
                            'readonly' => false
                     ),
                     'attotaldays' => array(
                            'name' => 'attotaldays',
                            'type' => 'input',
                            'label' => 'Total Days',
                            'field' => 'attotaldays',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace:normal;min-width:100px;max-width:100px;',
                            'readonly' => true
                     ),
                     'atstartmonth' => array(
                            'name' => 'atstartmonth',
                            'type' => 'date',
                            'label' => 'Start Month',
                            'field' => 'atstartmonth',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'atendmonth' => array(
                            'name' => 'atendmonth',
                            'type' => 'date',
                            'label' => 'End Month',
                            'field' => 'atendmonth',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'rctype' => array(
                            'name' => 'rctype',
                            'type' => 'label',
                            'label' => 'Type',
                            'field' => 'rctype',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace:normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'poref' => array(
                            'name' => 'poref',
                            'type' => 'label',
                            'label' => 'PO #',
                            'field' => 'poref',
                            'align' => 'text-left',
                            'style' => 'text-align:left;width:180px;whiteSpace: normal;min-width:180px;',
                            'readonly' => true,
                     ),
                     'warranty' => array(
                            'name' => 'warranty',
                            'field' => 'warranty',
                            'type' => 'date',
                            'label' => 'Warranty Date',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),
                     'podate' => array(
                            'name' => 'podate',
                            'field' => 'podate',
                            'type' => 'date',
                            'label' => 'PO Date',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),
                     'pono' => array(
                            'name' => 'pono',
                            'field' => 'pono',
                            'type' => 'input',
                            'label' => 'PO #',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'buyer' => array(
                            'name' => 'buyer',
                            'field' => 'buyer',
                            'type' => 'input',
                            'label' => 'Buyer',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'mobile' => array(
                            'name' => 'mobile',
                            'field' => 'mobile',
                            'type' => 'input',
                            'label' => 'Mobile',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'contactname' => array(
                            'name' => 'contactname',
                            'field' => 'contactname',
                            'type' => 'input',
                            'label' => 'Contact Name',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false,
                     ),
                     'contactname_lookup' => array(
                            'name' => 'contactname_lookup',
                            'field' => 'contactname_lookup',
                            'type' => 'input',
                            'label' => 'Contact Name',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'addedparams' => ['clientid'],
                            'readonly' => false,
                     ),
                     'companyname' => array(
                            'name' => 'companyname',
                            'field' => 'companyname',
                            'type' => 'input',
                            'label' => 'Company Name',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'salesperson' => array(
                            'name' => 'salesperson',
                            'field' => 'salesperson',
                            'type' => 'input',
                            'label' => 'Sales Person',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'comment' => array(
                            'name' => 'comment',
                            'type' => 'input',
                            'label' => 'Comment',
                            'field' => 'comment',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),
                     'createdate' => array(
                            'name' => 'createdate',
                            'type' => 'input',
                            'label' => 'Create Date',
                            'field' => 'createdate',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'donedate' => array(
                            'name' => 'donedate',
                            'type' => 'input',
                            'label' => 'Done Date',
                            'field' => 'donedate',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'seendate' => array(
                            'name' => 'seendate',
                            'type' => 'input',
                            'label' => 'Seen Date',
                            'field' => 'seendate',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'complain' => array(
                            'name' => 'complain',
                            'field' => 'complain',
                            'type' => 'input',
                            'label' => 'Complain',
                            'align' => 'text-left',
                            'style' => 'width: 100px;whiteSpace: normal;min-width:20px;max-width:100px;',
                            'readonly' => false
                     ),
                     'recommend' => array(
                            'name' => 'recommend',
                            'field' => 'recommend',
                            'type' => 'input',
                            'label' => 'Recommend',
                            'align' => 'text-left',
                            'style' => 'width: 100px;whiteSpace: normal;min-width:20px;max-width:100px;',
                            'readonly' => false
                     ),
                     'cardtype' => array(
                            'name' => 'cardtype',
                            'type' => 'input',
                            'label' => 'Card Type',
                            'field' => 'cardtype',
                            'align' => 'text-left',
                            'style' => 'min-width:200px;',
                            'readonly' => false
                     ),
                     'type' => array(
                            'name' => 'type',
                            'type' => 'input',
                            'label' => 'Payment Type',
                            'field' => 'type',
                            'align' => 'text-left',
                            'style' => 'min-width:200px;',
                            'readonly' => false
                     ),
                     'inactive' => array(
                            'name' => 'inactive',
                            'type' => 'toggle',
                            'label' => 'Inactive',
                            'field' => 'inactive',
                            'align' => 'text-left',
                            'style' => 'width:20px;whiteSpace: normal;min-width:30px;',
                            'readonly' => false
                     ),
                     'dlock' => array(
                            'name' => 'dlock',
                            'type' => 'input',
                            'label' => 'Last Update',
                            'field' => 'dlock',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:130px;',
                            'readonly' => true
                     ),
                     'completedar' => array(
                            'name' => 'completedar',
                            'type' => 'input',
                            'label' => '%Complete (AR)',
                            'field' => 'completedar',
                            'readonly' => true,
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'required' => false
                     ),
                     'projectprice' => array(
                            'name' => 'projectprice',
                            'type' => 'input',
                            'label' => 'Project Price',
                            'field' => 'projectprice',
                            'readonly' => false,
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'required' => false
                     ),
                     'driver' => array(
                            'name' => 'driver',
                            'field' => 'driver',
                            'type' => 'input',
                            'label' => 'Driver Name',
                            'align' => 'text-left',
                            'style' => 'width: 120px;whiteSpace: normal;min-width:120px;max-width:140px;',
                            'readonly' => true
                     ),
                     'vehicle' => array(
                            'name' => 'vehicle',
                            'field' => 'vehicle',
                            'type' => 'input',
                            'label' => 'Vehicle',
                            'align' => 'text-left',
                            'style' => 'width: 120px;whiteSpace: normal;min-width:120px;max-width:140px;',
                            'readonly' => true
                     ),
                     'purpose' => array(
                            'name' => 'purpose',
                            'field' => 'purpose',
                            'type' => 'input',
                            'label' => 'Purpose',
                            'align' => 'text-left',
                            'style' => 'width: 120px;whiteSpace: normal;min-width:120px;max-width:140px;',
                            'readonly' => false
                     ),
                     'destination' => array(
                            'name' => 'destination',
                            'field' => 'destination',
                            'type' => 'input',
                            'label' => 'Destination',
                            'align' => 'text-left',
                            'style' => 'width: 120px;whiteSpace: normal;min-width:120px;max-width:140px;',
                            'readonly' => false
                     ),
                     'passenger' => array(
                            'name' => 'passenger',
                            'type' => 'lookup',
                            'label' => 'Passenger Code',
                            'field' => 'client',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => true,
                            'lookupclass' => 'lookuppassenger',
                            'action' => 'lookupclient'
                     ),
                     'passengername' => array(
                            'name' => 'passengername',
                            'field' => 'passengername',
                            'type' => 'input',
                            'label' => 'Passenger Name',
                            'align' => 'text-left',
                            'style' => 'width: 100px;whiteSpace: normal;min-width:20px;max-width:100px;',
                            'readonly' => false
                     ),
                     'dropoff' => array(
                            'name' => 'dropoff',
                            'type' => 'toggle',
                            'label' => 'Drop Off',
                            'field' => 'dropoff',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'siref' => array(
                            'name' => 'siref',
                            'type' => 'label',
                            'label' => 'SI #',
                            'field' => 'siref',
                            'align' => 'text-left',
                            'style' => 'text-align:left;width:180px;whiteSpace: normal;min-width:180px;',
                            'readonly' => true,
                     ),
                     'sidate' => array(
                            'name' => 'sidate',
                            'field' => 'sidate',
                            'type' => 'date',
                            'label' => 'SI Date',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),
                     'overrideagent' => array(
                            'name' => 'overrideagent',
                            'field' => 'overrideagent',
                            'type' => 'label',
                            'label' => 'Override Agent',
                            'align' => 'text-left',
                            'style' => 'width: 100px;whiteSpace: normal;min-width:20px;max-width:100px;',
                            'readonly' => false
                     ),
                     'agent2comamt' => array(
                            'name' => 'agent2comamt',
                            'field' => 'agent2comamt',
                            'type' => 'label',
                            'label' => 'Override Commission',
                            'align' => 'text-right',
                            'style' => 'text-align:right; width: 100px;whiteSpace: normal;min-width:20px;max-width:100px;',
                            'readonly' => false
                     ),
                     'headagentname' => array(
                            'name' => 'headagentname',
                            'field' => 'headagentname',
                            'type' => 'label',
                            'label' => 'Product Head',
                            'align' => 'text-left',
                            'style' => 'width: 100px;whiteSpace: normal;min-width:20px;max-width:100px;',
                            'readonly' => false
                     ),
                     'famt' => array(
                            'name' => 'famt',
                            'type' => 'input',
                            'label' => 'TP Dollar',
                            'field' => 'famt',
                            'align' => 'text-right',
                            'style' => 'width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'isdp' => array(
                            'name' => 'isdp',
                            'type' => 'toggle',
                            'label' => 'With DP',
                            'field' => 'isdp',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'isnotallow' => array(
                            'name' => 'isnotallow',
                            'type' => 'toggle',
                            'label' => 'For Admin Only',
                            'field' => 'isnotallow',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'startqty' => array(
                            'name' => 'startqty',
                            'field' => 'startqty',
                            'type' => 'input',
                            'label' => 'Start Quantity',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'endqty' => array(
                            'name' => 'endqty',
                            'field' => 'endqty',
                            'type' => 'input',
                            'label' => 'End Quantity',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'yr' => array(
                            'name' => 'yr',
                            'field' => 'yr',
                            'type' => 'input',
                            'label' => 'Year',
                            'align' => 'text-left',
                            'style' => 'width: 120px;whiteSpace: normal;min-width:80px;max-width:80px;',
                            'readonly' => false
                     ),
                     'sono' => array(
                            'name' => 'sono',
                            'field' => 'sono',
                            'type' => 'input',
                            'label' => 'SO #',
                            'align' => 'text-left',
                            'style' => 'width: 100px;whiteSpace: normal;min-width:20px;max-width:100px;',
                            'readonly' => false
                     ),
                     'sodetails' => array(
                            'name' => 'sodetails',
                            'field' => 'sodetails',
                            'type' => 'label',
                            'label' => 'SO Details',
                            'align' => 'text-left',
                            'style' => 'width: 150px;whiteSpace: normal;min-width:150px;max-width:150px;',
                            'readonly' => false
                     ),

                     'rtno' => array(
                            'name' => 'rtno',
                            'field' => 'rtno',
                            'type' => 'input',
                            'label' => 'RT #',
                            'align' => 'text-left',
                            'style' => 'width: 100px;whiteSpace: normal;min-width:20px;max-width:100px;',
                            'readonly' => false
                     ),
                     'sanodesc' => array(
                            'name' => 'sanodesc',
                            'field' => 'sanodesc',
                            'type' => 'input',
                            'label' => 'SA #',
                            'align' => 'text-left',
                            'style' => 'width: 100px;whiteSpace: normal;min-width:20px;max-width:100px;',
                            'readonly' => false
                     ),
                     'sano' => array(
                            'name' => 'sano',
                            'field' => 'sano',
                            'type' => 'input',
                            'label' => 'SA #',
                            'align' => 'text-left',
                            'style' => 'width: 100px;whiteSpace: normal;min-width:20px;max-width:100px;',
                            'readonly' => false
                     ),
                     'journalamt' => array(
                            'name' => 'journalamt',
                            'type' => 'input',
                            'label' => 'Reading Amount',
                            'field' => 'journalamt',
                            'align' => 'text-right',
                            'style' => 'width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'probability' => array(
                            'name' => 'probability',
                            'type' => 'lookup',
                            'label' => 'Probability',
                            'field' => 'probability',
                            'align' => 'text-center',
                            'style' => 'text-align:right;width:180px;whiteSpace: normal;min-width:180px;',
                            'readonly' => false,
                            'class' => 'csprobability sbccsenablealways',
                            'lookupclass' => 'probability',
                            'action' => 'lookupsetup'
                     ),
                     'listpddocno' => array(
                            'name' => 'pddocno',
                            'field' => 'pddocno',
                            'type' => 'input',
                            'label' => 'Prod Order #',
                            'align' => 'text-left',
                            'style' => 'width: 120px;whiteSpace: normal;min-width:120px;max-width:140px;',
                            'readonly' => true
                     ),
                     'subprojectname' => array(
                            'name' => 'subprojectname',
                            'type' => 'lookup',
                            'label' => 'Sub Project',
                            'field' => 'subprojectname',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => true,
                            'lookupclass' => 'gridsubproj',
                            'action' => 'lookupsubproject'
                     ),
                     'lastdp' => array(
                            'name' => 'lastdp',
                            'type' => 'toggle',
                            'label' => 'Final DP',
                            'field' => 'lastdp',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'insurance' => array(
                            'name' => 'insurance',
                            'type' => 'input',
                            'label' => 'Insurance',
                            'field' => 'insurance',
                            'align' => 'text-right',
                            'style' => 'text-align:right;width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => true
                     ),
                     'voidqty' => array(
                            'name' => 'voidqty',
                            'type' => 'chip',
                            'label' => 'Void Quantity',
                            'field' => 'voidqty',
                            'align' => 'text-right',
                            'style' => 'width:90px;whiteSpace: normal;min-width:90px;',
                            'readonly' => true,
                            'color' => 'primary',
                            'textcolor' => 'white'
                     ),
                     'addons' => array(
                            'name' => 'addons',
                            'type' => 'editlookup',
                            'label' => 'Addons',
                            'field' => 'addons',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace:normal;min-width:120px;',
                            'readonly' => false,
                            'lookupclass' => 'addons',
                            'action' => 'lookupsetup'
                     ),
                     'side' => array(
                            'name' => 'side',
                            'type' => 'editlookup',
                            'label' => 'Side',
                            'field' => 'side',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace:normal;min-width:120px;',
                            'readonly' => false,
                            'lookupclass' => 'side',
                            'action' => 'lookupsetup'
                     ),
                     'parts' => array(
                            'name' => 'parts',
                            'type' => 'editlookup',
                            'label' => 'Parts',
                            'field' => 'parts',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace:normal;min-width:120px;',
                            'readonly' => false,
                            'lookupclass' => 'parts',
                            'action' => 'lookupsetup'
                     ),
                     'delcharge' => array(
                            'name' => 'delcharge',
                            'type' => 'input',
                            'label' => 'Delivery Charge',
                            'field' => 'delcharge',
                            'align' => 'text-right',
                            'style' => 'text-align:right;width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => true
                     ),

                     'acctstatus' => array(
                            'name' => 'acctstatus',
                            'type' => 'input',
                            'label' => 'Status',
                            'field' => 'acctstatus',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace:normal;min-width:150px;max-width:150px;',
                            'readonly' => false
                     ),
                     'isreleased' => array(
                            'name' => 'isreleased',
                            'type' => 'label',
                            'label' => 'Released',
                            'field' => 'isreleased',
                            'align' => 'text-center',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => true
                     ),
                     'businessnature' => array(
                            'name' => 'businessnature',
                            'type' => 'editlookup',
                            'label' => 'Business Nature',
                            'field' => 'businessnature',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace:normal;min-width:120px;',
                            'readonly' => false,
                            'lookupclass' => 'lookupbusinessnature',
                            'action' => 'lookupsetup'
                     ),
                     'reqtype' => array(
                            'name' => 'reqtype',
                            'field' => 'reqtype',
                            'type' => 'input',
                            'label' => 'Type',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'listgroup' => array(
                            'name' => 'groupid',
                            'field' => 'groupid',
                            'type' => 'input',
                            'label' => 'Group',
                            'align' => 'text-left',
                            'style' => 'width: 280px;whiteSpace: normal;min-width:280px;max-width:290px;text-align:left;',
                            'readonly' => false
                     ),
                     'blk' => array(
                            'name' => 'blk',
                            'field' => 'blk',
                            'type' => 'input',
                            'label' => 'Block',
                            'align' => 'text-left',
                            'style' => 'width: 280px;whiteSpace: normal; min-width:280px;max-width:290px;text-align:left;',
                            'readonly' => false
                     ),
                     'lot' => array(
                            'name' => 'lot',
                            'field' => 'lot',
                            'type' => 'input',
                            'label' => 'Lot',
                            'align' => 'text-left',
                            'style' => 'width: 280px;whiteSpace: normal; min-width:280px;max-width:290px;text-align:left;',
                            'readonly' => false
                     ),
                     'price' => array(
                            'name' => 'price',
                            'type' => 'input',
                            'label' => 'Price',
                            'field' => 'price',
                            'align' => 'text-right',
                            'style' => 'text-align:right;width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'sqm' => array(
                            'name' => 'sqm',
                            'type' => 'input',
                            'label' => 'SQM',
                            'field' => 'sqm',
                            'align' => 'text-right',
                            'style' => 'text-align:right;width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'groupid' => array(
                            'name' => 'groupid',
                            'field' => 'groupid',
                            'type' => 'input',
                            'label' => 'Group',
                            'align' => 'text-left',
                            'style' => 'width: 280px;whiteSpace: normal; min-width:280px;max-width:290px;text-align:left;',
                            'readonly' => false
                     ),
                     'color' => array(
                            'name' => 'color',
                            'type' => 'colorpicker',
                            'field' => 'color',
                            'label' => 'Color',
                            'align' => 'text-left',
                            'style' => 'width: 280px;whiteSpace: normal; min-width:280px;max-width:290px;text-align:left;',
                            'readonly' => false
                     ),
                     'age' => array(
                            'name' => 'age',
                            'field' => 'age',
                            'type'  => 'input',
                            'label' => 'Age',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'cash' => array(
                            'name' => 'cash',
                            'type' => 'input',
                            'label' => 'Spot Cash',
                            'field' => 'cash',
                            'align' => 'text-right',
                            'style' => 'text-align:right;width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'annual' => array(
                            'name' => 'annual',
                            'type' => 'input',
                            'label' => 'Annual',
                            'field' => 'annual',
                            'align' => 'text-right',
                            'style' => 'text-align:right;width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'semi' => array(
                            'name' => 'semi',
                            'type' => 'input',
                            'label' => 'Semi Annual',
                            'field' => 'semi',
                            'align' => 'text-right',
                            'style' => 'text-align:right;width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'quarterly' => array(
                            'name' => 'quarterly',
                            'type' => 'input',
                            'label' => 'Quarterly',
                            'field' => 'quarterly',
                            'align' => 'text-right',
                            'style' => 'text-align:right;width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'monthly' => array(
                            'name' => 'monthly',
                            'type' => 'input',
                            'label' => 'Monthly',
                            'field' => 'monthly',
                            'align' => 'text-right',
                            'style' => 'text-align:right;width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'processfee' => array(
                            'name' => 'processfee',
                            'type' => 'input',
                            'label' => 'Processing Fee',
                            'field' => 'processfee',
                            'align' => 'text-right',
                            'style' => 'text-align:right;width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'reconfee' => array(
                            'name' => 'reconfee',
                            'type' => 'input',
                            'label' => 'Reconnection Fee',
                            'field' => 'reconfee',
                            'align' => 'text-right',
                            'style' => 'text-align:right;width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'surcharge' => array(
                            'name' => 'surcharge',
                            'type' => 'input',
                            'label' => 'Surcharge (%)',
                            'field' => 'surcharge',
                            'align' => 'text-right',
                            'style' => 'text-align:right;width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'prevqty' => array(
                            'name' => 'prevqty',
                            'type' => 'input',
                            'label' => 'Prev',
                            'field' => 'prevqty',
                            'align' => 'text-right',
                            'style' => 'width:90px;whiteSpace: normal;min-width:90px;',
                            'readonly' => false
                     ),
                     'planholder' => array(
                            'name' => 'planholder',
                            'field' => 'planholder',
                            'type' => 'input',
                            'label' => 'Plan Holder',
                            'align' => 'text-left',
                            'style' => 'width: 200px;whiteSpace: normal; min-width:200px;max-width:200px;',
                            'readonly' => false,
                     ),
                     'clearedby' => array(
                            'name' => 'clearedby',
                            'field' => 'clearedby',
                            'type' => 'input',
                            'label' => 'Cleared By',
                            'align' => 'text-left',
                            'style' => 'width: 200px;whiteSpace: normal; min-width:200px;max-width:200px;',
                            'readonly' => false,
                     ),
                     'cleareddate' => array(
                            'name' => 'cleareddate',
                            'type' => 'date',
                            'label' => 'Clear Date',
                            'field' => 'cleareddate',
                            'align' => 'text-left',
                            'style' => 'text-align:left;width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'isbo' => array(
                            'name' => 'isbo',
                            'type' => 'toggle',
                            'label' => 'BO',
                            'field' => 'isbo',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false,
                            'checkfield' => ''
                     ),
                     'netamt' => array(
                            'name' => 'netamt',
                            'type' => 'label',
                            'field' => 'netamt',
                            'label' => 'Net Price',
                            'align' => 'text-right',
                            'style' => 'text-align:right;width:90px;whiteSpace:normal;min-width:90px;',
                            'readonly' => false
                     ),
                     'isgeneric' => array(
                            'name' => 'isgeneric',
                            'type' => 'toggle',
                            'label' => 'Generic Item',
                            'field' => 'isgeneric',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false,
                            'checkfield' => ''
                     ),
                     'isdisable' => array(
                            'name' => 'isdisable',
                            'type' => 'toggle',
                            'label' => 'Disable',
                            'field' => 'isenable',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false,
                            'checkfield' => ''
                     ),
                     'discr' => array(
                            'name' => 'discr',
                            'type' => 'input',
                            'label' => 'DiscR',
                            'field' => 'discr',
                            'align' => 'text-left',
                            'style' => 'width:90px;whiteSpace: normal;min-width:90px;',
                            'readonly' => false
                     ),
                     'discws' => array(
                            'name' => 'discws',
                            'type' => 'input',
                            'label' => 'DiscWS',
                            'field' => 'discws',
                            'align' => 'text-left',
                            'style' => 'width:90px;whiteSpace:normal;min-width:90px;',
                            'readonly' => false
                     ),
                     'disca' => array(
                            'name' => 'disca',
                            'type' => 'input',
                            'label' => 'DiscA',
                            'field' => 'disca',
                            'align' => 'text-left',
                            'style' => 'width:90px;whiteSpace:normal;min-width:90px;',
                            'readonly' => false
                     ),
                     'discb' => array(
                            'name' => 'discb',
                            'type' => 'input',
                            'label' => 'DiscB',
                            'field' => 'discb',
                            'align' => 'text-left',
                            'style' => 'width:90px;whiteSpace:normal;min-width:90px;',
                            'readonly' => false
                     ),
                     'discc' => array(
                            'name' => 'discc',
                            'type' => 'input',
                            'label' => 'DiscC',
                            'field' => 'discc',
                            'align' => 'text-left',
                            'style' => 'width:90px;whiteSpace:normal;min-width:90px;',
                            'readonly' => false
                     ),
                     'discd' => array(
                            'name' => 'discd',
                            'type' => 'input',
                            'label' => 'DiscD',
                            'field' => 'discd',
                            'align' => 'text-left',
                            'style' => 'width:90px;whiteSpace:normal;min-width:90px;',
                            'readonly' => false
                     ),
                     'disce' => array(
                            'name' => 'disce',
                            'type' => 'input',
                            'label' => 'DiscE',
                            'field' => 'disce',
                            'align' => 'text-left',
                            'style' => 'width:90px;whiteSpace:normal;min-width:90px;',
                            'readonly' => false
                     ),
                     'cashamt' => array(
                            'name' => 'cashamt',
                            'type' => 'input',
                            'label' => 'Cash Price',
                            'field' => 'cashamt',
                            'align' => 'text-left',
                            'style' => 'width:90px;whiteSpace:normal;min-width:90px;',
                            'readonly' => false
                     ),
                     'cashdisc' => array(
                            'name' => 'cashdisc',
                            'type' => 'input',
                            'label' => 'Cash Disc',
                            'field' => 'cashdisc',
                            'align' => 'text-left',
                            'style' => 'width:90px;whiteSpace:normal;min-width:90px;',
                            'readonly' => false
                     ),
                     'wsamt' => array(
                            'name' => 'wsamt',
                            'type' => 'input',
                            'label' => 'WS Price',
                            'field' => 'wsamt',
                            'align' => 'text-left',
                            'style' => 'width:90px;whiteSpace:normal;min-width:90px;',
                            'readonly' => false
                     ),
                     'wsdisc' => array(
                            'name' => 'wsdisc',
                            'type' => 'input',
                            'label' => 'WS Disc',
                            'field' => 'wsdisc',
                            'align' => 'text-left',
                            'style' => 'width:90px;whiteSpace:normal;min-width:90px;',
                            'readonly' => false
                     ),
                     'disc1' => array(
                            'name' => 'disc1',
                            'type' => 'input',
                            'label' => 'Price1 Disc',
                            'field' => 'disc1',
                            'align' => 'text-left',
                            'style' => 'width:90px;whiteSpace:normal;min-width:90px;',
                            'readonly' => false
                     ),
                     'disc2' => array(
                            'name' => 'disc2',
                            'type' => 'input',
                            'label' => 'Price2 Disc',
                            'field' => 'disc2',
                            'align' => 'text-left',
                            'style' => 'width:90px;whiteSpace:normal;min-width:90px;',
                            'readonly' => false
                     ),
                     'listcollector' => array(
                            'name' => 'collector',
                            'field' => 'collector',
                            'type' => 'input',
                            'label' => 'Collector',
                            'align' => 'text-left',
                            'style' => 'width: 280px;whiteSpace: normal;min-width:280px;max-width:290px;text-align:left;',
                            'readonly' => false
                     ),
                     'cvno' => array(
                            'name' => 'cvno',
                            'field' => 'cvno',
                            'type' => 'label',
                            'label' => 'CV No.',
                            'align' => 'text-left',
                            'style' => 'width:90px;whiteSpace:normal;min-width:90px;max-width:100px;'
                     ),
                     'checkdetails' => array(
                            'name' => 'checkdetails',
                            'field' => 'checkdetails',
                            'type' => 'label',
                            'label' => 'Check Details',
                            'align' => 'text-left',
                            'style' => 'width:90px;whiteSpace:normal;min-width:90px;max-width:100px;'
                     ),
                     'releasetoap' => array(
                            'name' => 'releasetoap',
                            'field' => 'releasetoap',
                            'type' => 'date',
                            'label' => 'Release to AP',
                            'align' => 'text-left',
                            'style' => 'width:130px;whiteSpace:normal;min-width:130px;max-width:130px;',
                            'readonly' => true
                     ),
                     'releasetosupp' => array(
                            'name' => 'releasetosupp',
                            'field' => 'releasetosupp',
                            'label' => 'Release to Supp',
                            'type' => 'label',
                            'align' => 'text-left',
                            'style' => 'width:90px;whiteSpace:normal;min-width:90px;max-width:100px;'
                     ),
                     'cleardate' => array(
                            'name' => 'cleardate',
                            'field' => 'cleardate',
                            'label' => 'Clear Date',
                            'type' => 'label',
                            'align' => 'text-left',
                            'style' => 'width:90px;whiteSpace:normal;min-width:90px;max-width:100px;'
                     ),
                     'pldate' => array(
                            'name' => 'pldate',
                            'field' => 'pldate',
                            'label' => 'PL Date',
                            'type' => 'label',
                            'align' => 'text-left',
                            'style' => 'width:90px;whiteSpace:normal;min-width:90px;max-width:100px;'
                     ),
                     'quantity' => array(
                            'name' => 'quantity',
                            'field' => 'quantity',
                            'label' => 'Quantity',
                            'type' => 'input',
                            'style' => 'width:90px;whiteSpace:normal;min-width:90px;max-width:90px;',
                            'readonly' => false
                     ),
                     'chassis' => array(
                            'name' => 'chassis',
                            'type' => 'input',
                            'label' => 'Chassis',
                            'field' => 'chassis',
                            'align' => 'text-left',
                            'style' => 'min-width:80px;',
                            'readonly' => false
                     ),
                     'pnp' => array(
                            'name' => 'pnp',
                            'type' => 'input',
                            'label' => 'PNP#',
                            'field' => 'pnp',
                            'align' => 'text-left',
                            'style' => 'min-width:80px;',
                            'readonly' => false
                     ),
                     'csr' => array(
                            'name' => 'csr',
                            'type' => 'input',
                            'label' => 'CSR#',
                            'field' => 'csr',
                            'align' => 'text-left',
                            'style' => 'min-width:80px;',
                            'readonly' => false
                     ),
                     'lastcost' => array(
                            'name' => 'lastcost',
                            'type' => 'input',
                            'label' => 'PCost',
                            'field' => 'lastcost',
                            'align' => 'text-right',
                            'style' => 'width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'charges' => array(
                            'name' => 'charges',
                            'type' => 'input',
                            'label' => 'Freight',
                            'field' => 'charges',
                            'align' => 'text-right',
                            'style' => 'text-align:right;width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => false
                     ),
                     'charge1' => array(
                            'name' => 'charge1',
                            'type' => 'input',
                            'label' => 'Retainer Fee',
                            'field' => 'charge1',
                            'align' => 'text-right',
                            'style' => 'text-align:right;width:130px;whiteSpace: normal;min-width:130px;',
                            'readonly' => true
                     ),
                     'isactivity' => array(
                            'name' => 'isactivity',
                            'type' => 'toggle',
                            'label' => 'Activity',
                            'field' => 'isactivity',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false,
                     ),
                     'mktg' => array(
                            'name' => 'mktg',
                            'type' => 'input',
                            'label' => 'Marketing Fee (%)',
                            'field' => 'mktg',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'dc' => array(
                            'name' => 'dc',
                            'type' => 'input',
                            'label' => 'Dock Fee (%)',
                            'field' => 'dc',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'bo' => array(
                            'name' => 'bo',
                            'type' => 'input',
                            'label' => 'BO (%)',
                            'field' => 'bo',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'card' => array(
                            'name' => 'card',
                            'type' => 'input',
                            'label' => 'Card (%)',
                            'field' => 'card',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'openingintro' => array(
                            'name' => 'openingintro',
                            'type' => 'input',
                            'label' => 'Opening Intro (%)',
                            'field' => 'openingintro',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'e2e' => array(
                            'name' => 'e2e',
                            'type' => 'input',
                            'label' => 'E2E (%)',
                            'field' => 'e2e',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'rebate' => array(
                            'name' => 'rebate',
                            'type' => 'input',
                            'label' => 'Rebate (%)',
                            'field' => 'rebate',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'rtv' => array(
                            'name' => 'rtv',
                            'type' => 'input',
                            'label' => 'RTV (%)',
                            'field' => 'rtv',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'interest' => array(
                            'name' => 'interest',
                            'field' => 'interest',
                            'type' => 'input',
                            'label' => 'Interest',
                            'align' => 'text-right',
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'readonly' => false,
                     ),
                     'principal' => array(
                            'name' => 'principal',
                            'field' => 'principal',
                            'type' => 'input',
                            'label' => 'Principal',
                            'align' => 'text-right',
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'readonly' => false,
                     ),
                     'principal' => array(
                            'name' => 'principal',
                            'field' => 'principal',
                            'type' => 'input',
                            'label' => 'Principal',
                            'align' => 'text-right',
                            'style' => 'width: 20px;whiteSpace: normal;
                                  min-width:20px;max-width:30px;',
                            'readonly' => false,
                     ),
                     'modeofsales' => array(
                            'name' => 'modeofsales',
                            'field' => 'modeofsales',
                            'type' => 'input',
                            'label' => 'Mode of Sales',
                            'align' => 'text-left',
                            'style' => 'width: 140px;whiteSpace: normal;min-width:140px;max-width:150px;text-align:left;',
                            'readonly' => false,
                     ),
                     'isorder' => array(
                            'name' => 'isorder',
                            'type' => 'toggle',
                            'label' => 'Order Type',
                            'field' => 'isorder',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false,
                            'checkfield' => ''
                     ),
                     'ischannel' => array(
                            'name' => 'ischannel',
                            'type' => 'toggle',
                            'label' => 'Channel',
                            'field' => 'ischannel',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false,
                            'checkfield' => ''
                     ),
                     'vessel' => array(
                            'name' => 'vessel',
                            'type' => 'label',
                            'label' => 'Vessel',
                            'field' => 'vessel',
                            'readonly' => false,
                            'align' => 'text-left',
                            'style' => 'width:80px;whiteSpace: normal;min-width:80px;',
                            'required' => false
                     ),
                     'voyageno' => array(
                            'name' => 'voyageno',
                            'type' => 'label',
                            'label' => 'Voyage No',
                            'field' => 'voyageno',
                            'readonly' => false,
                            'align' => 'text-left',
                            'style' => 'width:80px;whiteSpace: normal;min-width:80px;',
                            'required' => false
                     ),
                     'sealno' => array(
                            'name' => 'sealno',
                            'type' => 'label',
                            'label' => 'Seal No',
                            'field' => 'sealno',
                            'readonly' => false,
                            'align' => 'text-left',
                            'style' => 'width:80px;whiteSpace: normal;min-width:80px;',
                            'required' => false
                     ),
                     'suppinvno' => array(
                            'name' => 'suppinvno',
                            'type' => 'label',
                            'label' => 'Supplier Inv No.',
                            'field' => 'suppinvno',
                            'readonly' => false,
                            'align' => 'text-left',
                            'style' => 'width:80px;whiteSpace: normal;min-width:80px;',
                            'required' => false
                     ),
                     'sicsino' => array(
                            'name' => 'sicsino',
                            'type' => 'label',
                            'label' => 'CSI#',
                            'field' => 'sicsino',
                            'readonly' => false,
                            'align' => 'text-left',
                            'style' => 'width:80px;whiteSpace: normal;min-width:80px;',
                            'required' => false
                     ),

                     'count' => array(
                            'name' => 'count',
                            'type' => 'input',
                            'label' => 'Count',
                            'field' => 'count',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),

                     'maxqty' => array(
                            'name' => 'maxqty',
                            'type' => 'input',
                            'label' => 'Max Qty',
                            'field' => 'maxqty',
                            'align' => 'text-right',
                            'style' => 'text-align:right;width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'listcomakername' => array(
                            'name' => 'comakername',
                            'field' => 'comakername',
                            'type' => 'input',
                            'label' => 'Name',
                            'align' => 'text-left',
                            'style' => 'width: 280px;whiteSpace: normal;min-width:280px;max-width:290px;text-align:left;',
                            'readonly' => false
                     ),

                     'shipto' => array(
                            'name' => 'shipto',
                            'field' => 'shipto',
                            'type' => 'input',
                            'label' => 'Delivered To',
                            'align' => 'text-left',
                            'style' => 'width: 280px;whiteSpace: normal;min-width:280px;max-width:290px;text-align:left;',
                            'readonly' => false
                     ),
                     'elapsed' => array(
                            'name' => 'elapsed',
                            'type' => 'input',
                            'label' => 'Elapsed',
                            'field' => 'elapsed',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),

                     'reasonname' => array(
                            'name' => 'reasonname',
                            'type' => 'lookup',
                            'label' => 'Reason',
                            'field' => 'reasonname',
                            'align' => 'text-left',
                            'readonly' => true,
                            'style' => 'text-align:left;width:180px;whiteSpace: normal;min-width:180px;',
                            'lookupclass' => 'lookupreasoncode',
                            'action' => 'lookupreasoncode'
                     ),
                     'expiry2' => array(
                            'name' => 'expiry2',
                            'type' => 'input',
                            'label' => 'Expiry',
                            'field' => 'expiry2',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),

                     'checkinfo' => array(
                            'name' => 'checkinfo',
                            'type' => 'input',
                            'label' => 'Checkinfo',
                            'field' => 'checkinfo',
                            'align' => 'text-left',
                            'style' => 'width:180px;whiteSpace: normal;min-width:180px;',
                            'readonly' => false
                     ),

                     'purposeofpayment' => array(
                            'name' => 'purposeofpayment',
                            'type' => 'input',
                            'label' => 'Purpose of Payment',
                            'field' => 'purposeofpayment',
                            'align' => 'text-left',
                            'style' => 'width:200px;whiteSpace: normal;min-width:200px;',
                            'readonly' => false
                     ),

                     'nf' => array(
                            'name' => 'nf',
                            'type' => 'input',
                            'label' => 'NF',
                            'field' => 'nf',
                            'align' => 'text-right',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),

                     'deduction' => array(
                            'name' => 'deduction',
                            'type' => 'input',
                            'field' => 'deduction',
                            'label' => 'Deduction ',
                            'class' => 'csdeduction sbccsreadonly',
                            'align' => 'text-left',
                            'readonly' => false,
                            'style' => 'width:120px;whiteSpace: normal;min-width:130px;',
                            'required' => false
                     ),
                     'isreplenish' => array(
                            'name' => 'isreplenish',
                            'type' => 'toggle',
                            'label' => 'Replenish',
                            'field' => 'isreplenish',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => true,
                            'checkfield' => ''
                     ),
                     'prevamt' => array(
                            'name' => 'prevamt',
                            'type' => 'input',
                            'label' => 'Previous Amount',
                            'field' => 'prevamt',
                            'align' => 'text-right',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'prevdate' => array(
                            'name' => 'prevdate',
                            'type' => 'date',
                            'label' => 'Previous Date',
                            'field' => 'prevdate',
                            'align' => 'text-left',
                            'style' => 'text-align:left;width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),
                     'amount2' => array(
                            'name' => 'amount2',
                            'field' => 'amount2',
                            'type'  => 'input',
                            'label' => 'Price 2',
                            'align' => 'text-right',
                            'style' => 'width: 20px;whiteSpace: normal; min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'petty' => array(
                            'name' => 'petty',
                            'field' => 'petty',
                            'type' => 'input',
                            'label' => 'Petty Cash',
                            'readonly' => false,
                            'style' =>  'width: 90px;whiteSpace: normal; min-width:90px;max-width:90px;'
                     ),
                     'listqtype' => array(
                            'name' => 'qtype',
                            'field' => 'qtype',
                            'type' => 'label',
                            'label' => 'Title',
                            'align' => 'text-left',
                            'style' => 'width: 140px;whiteSpace: normal;min-width:140px;max-width:150px;text-align:left;',
                            'readonly' => true
                     ),
                     'listrem' => array(
                            'name' => 'rem',
                            'field' => 'rem',
                            'type' => 'label',
                            'label' => 'Remarks',
                            'align' => 'text-left',
                            'style' => 'width: 140px;whiteSpace: normal;min-width:140px;max-width:150px;text-align:left;',
                            'readonly' => true
                     ),

                     'colorname' => array(
                            'name' => 'colorname',
                            'type' => 'lookup',
                            'label' => 'Colors',
                            'field' => 'colorname',
                            'align' => 'text-left',
                            'style' => 'width:20px;whiteSpace: normal;min-width:30px;',
                            'readonly' => true,
                            'lookupclass' => 'lookup_catcolors',
                            'action' => 'lookupsetup'
                     ),
                     'sbu' => array(
                            'name' => 'sbu',
                            'field' => 'sbu',
                            'type' => 'label',
                            'label' => 'SBU',
                            'align' => 'text-left',
                            'style' => 'width: 90px;whiteSpace: normal;min-width:90px;max-width:90px;text-align:left;',
                            'readonly' => true
                     ),
                     'startyear' => array(
                            'name' => 'startyear',
                            'type' => 'input',
                            'label' => 'Start Year',
                            'field' => 'startyear',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'endyear' => array(
                            'name' => 'endyear',
                            'type' => 'input',
                            'label' => 'End Year',
                            'field' => 'endyear',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'generation' => array(
                            'name' => 'generation',
                            'type' => 'input',
                            'label' => 'Generation',
                            'field' => 'generation',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'sortline' => array(
                            'name' => 'sortline',
                            'type' => 'input',
                            'label' => 'Line',
                            'field' => 'sortline',
                            'align' => 'text-left',
                            'style' =>  'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'num' => array(
                            'name' => 'num',
                            'field' => 'num',
                            'type' => 'input',
                            'label' => 'No. of Days',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),

                     'ownertype' => array(
                            'name' => 'ownertype',
                            'field' => 'ownertype',
                            'type' => 'input',
                            'label' => 'Owner Type',
                            'align' => 'text-left',
                            'style' => 'width: 200px;whiteSpace: normal; min-width:200px;max-width:200px;',
                            'readonly' => false,
                     ),

                     'ownername' => array(
                            'name' => 'ownername',
                            'field' => 'ownername',
                            'type' => 'input',
                            'label' => 'Owner Name',
                            'align' => 'text-left',
                            'style' => 'width: 200px;whiteSpace: normal; min-width:200px;max-width:200px;',
                            'readonly' => false,
                     ),

                     'addr2' => array(
                            'name' => 'addr2',
                            'field' => 'addr2',
                            'type' => 'input',
                            'label' => 'Owner Address',
                            'align' => 'text-left',
                            'style' => 'width: 200px;whiteSpace: normal; min-width:200px;max-width:200px;',
                            'readonly' => false,
                     ),
                     'ontrip' => array(
                            'name' => 'ontrip',
                            'type' => 'input',
                            'label' => 'Logs Type',
                            'field' => 'ontrip',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'agentamt' => array(
                            'name' => 'agentamt',
                            'type' => 'input',
                            'label' => 'Agent Amount',
                            'field' => 'agentamt',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'startwire' => array(
                            'name' => 'startwire',
                            'type' => 'input',
                            'label' => 'Start Wire',
                            'field' => 'startwire',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'endwire' => array(
                            'name' => 'endwire',
                            'type' => 'input',
                            'label' => 'End Wire',
                            'field' => 'endwire',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'locname' => array(
                            'name' => 'locname',
                            'field' => 'locname',
                            'type' => 'input',
                            'label' => 'Location',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'modulename' => array(
                            'name' => 'modulename',
                            'field' => 'modulename',
                            'type' => 'lookup',
                            'action' => 'lookupsetup',
                            'lookupclass' => 'lookupmodulelist2',
                            'label' => 'Module Name',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),
                     'labelname' => array(
                            'name' => 'labelname',
                            'field' => 'labelname',
                            'type' => 'input',
                            'label' => 'Module Label',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),
                     'countsupervisor' => array(
                            'name' => 'countsupervisor',
                            'type' => 'input',
                            'label' => 'Supervisor Count',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => true
                     ),
                     'countapprover' => array(
                            'name' => 'countapprover',
                            'type' => 'input',
                            'label' => 'Approver Count',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => true
                     ),
                     'entitled' => array(
                            'name' => 'entitled',
                            'type' => 'input',
                            'label' => 'Entitled',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'approverseq' => array(
                            'name' => 'approverseq',
                            'label' => 'Approver Seq',
                            'type' => 'lookup',
                            'action' => 'lookupsetup',
                            'lookupclass' => 'lookupapproverseq',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => true
                     ),
                     'appcount' => array(
                            'name' => 'appcount',
                            'label' => 'Count',
                            'field' => 'appcount',
                            'type' => 'label',
                            'align' => 'text-center',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;'
                     ),
                     'rem2' => array(
                            'name' => 'rem2',
                            'type' => 'input',
                            'label' => 'Second Approver',
                            'field' => 'rem2',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),

                     'iseq' => array(
                            'name' => 'iseq',
                            'type' => 'input',
                            'label' => 'Sequence',
                            'field' => 'iseq',
                            'align' => 'text-center',
                            'style' => 'min-width:80px;',
                            'readonly' => false
                     ),

                     'contact1' => array(
                            'name' => 'contact1',
                            'field' => 'contact1',
                            'type' => 'input',
                            'label' => 'Contact Person',
                            'align' => 'text-left',
                            'style' => 'width: 120px;whiteSpace: normal;min-width:120px;max-width:120px;',
                            'readonly' => false
                     ),
                     'relation1' => array(
                            'name' => 'relation1',
                            'field' => 'relation1',
                            'type' => 'input',
                            'label' => 'Relationship',
                            'align' => 'text-left',
                            'style' => 'width: 120px;whiteSpace: normal;min-width:120px;max-width:120px;',
                            'readonly' => false
                     ),

                     'addr1' => array(
                            'name' => 'addr1',
                            'field' => 'addr1',
                            'type' => 'input',
                            'label' => 'Address',
                            'align' => 'text-left',
                            'style' => 'width: 120px;whiteSpace: normal;min-width:120px;max-width:120px;',
                            'readonly' => false
                     ),

                     'homeno1' => array(
                            'name' => 'homeno1',
                            'field' => 'homeno1',
                            'type' => 'input',
                            'label' => 'Home No.',
                            'align' => 'text-left',
                            'style' => 'width: 120px;whiteSpace: normal;min-width:120px;max-width:120px;',
                            'readonly' => false
                     ),

                     'mobileno1' => array(
                            'name' => 'mobileno1',
                            'field' => 'mobileno1',
                            'type' => 'input',
                            'label' => 'Mobile No.',
                            'align' => 'text-left',
                            'style' => 'width: 120px;whiteSpace: normal;min-width:120px;max-width:120px;',
                            'readonly' => false
                     ),


                     'officeno1' => array(
                            'name' => 'officeno1',
                            'field' => 'officeno1',
                            'type' => 'input',
                            'label' => 'Office No.',
                            'align' => 'text-left',
                            'style' => 'width: 120px;whiteSpace: normal;min-width:120px;max-width:120px;',
                            'readonly' => false
                     ),

                     'ext1' => array(
                            'name' => 'ext1',
                            'field' => 'ext1',
                            'type' => 'input',
                            'label' => 'Extension',
                            'align' => 'text-left',
                            'style' => 'width: 120px;whiteSpace: normal;min-width:120px;max-width:120px;',
                            'readonly' => false
                     ),

                     'notes1' => array(
                            'name' => 'notes1',
                            'field' => 'notes1',
                            'type' => 'input',
                            'label' => 'Notes',
                            'align' => 'text-left',
                            'style' => 'width: 120px;whiteSpace: normal;min-width:120px;max-width:120px;',
                            'readonly' => false
                     ),

                     'schoollevel' => array(
                            'name' => 'schoollevel',
                            'field' => 'schoollevel',
                            'type' => 'input',
                            'label' => 'School Level',
                            'align' => 'text-left',
                            'style' => 'width: 120px;whiteSpace: normal;min-width:120px;max-width:120px;',
                            'readonly' => false
                     ),

                     'occupation' => array(
                            'name' => 'occupation',
                            'field' => 'occupation',
                            'type' => 'input',
                            'label' => 'Occupation',
                            'align' => 'text-left',
                            'style' => 'width: 120px;whiteSpace: normal;min-width:120px;max-width:120px;',
                            'readonly' => false
                     ),
                     'first' => array(
                            'name' => 'first',
                            'field' => 'first',
                            'type' => 'input',
                            'label' => 'Start',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'last' => array(
                            'name' => 'last',
                            'field' => 'last',
                            'type' => 'input',
                            'label' => 'End',
                            'align' => 'text-left',
                            'style' => 'width: 20px;whiteSpace: normal;min-width:20px;max-width:30px;',
                            'readonly' => false
                     ),
                     'oandaphpusd' => array(
                            'name' => 'oandaphpusd',
                            'type' => 'input',
                            'label' => 'OANDA PHP-USD',
                            'field' => 'oandaphpusd',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'oandausdphp' => array(
                            'name' => 'oandausdphp',
                            'type' => 'input',
                            'label' => 'OANDA USD-PHP',
                            'field' => 'oandausdphp',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'osphpusd' => array(
                            'name' => 'osphpusd',
                            'type' => 'input',
                            'label' => 'OS PHP-USD',
                            'field' => 'osphpusd',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'tonnage' => array(
                            'name' => 'tonnage',
                            'field' => 'tonnage',
                            'type' => 'input',
                            'label' => 'Tonnage',
                            'align' => 'text-center',
                            'style' => 'text-align:right; width: 150px;whiteSpace: normal;min-width:150px;max-width:150px;',
                            'readonly' => false
                     ),

                     'diesel' => array(
                            'name' => 'diesel',
                            'field' => 'diesel',
                            'type' => 'input',
                            'label' => 'Diesel',
                            'align' => 'text-center',
                            'style' => 'text-align:right; width: 100px;whiteSpace: normal;min-width:20px;max-width:100px;',
                            'readonly' => false
                     ),
                     'memodocno' => array(
                            'name' => 'memodocno',
                            'type' => 'input',
                            'label' => 'Incident Report #',
                            'field' => 'memodocno',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'findings' => array(
                            'name' => 'findings',
                            'type' => 'input',
                            'label' => 'Summary of Findings',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace:normal;min-width:100px;max-width:150px;',
                            'readonly' => false
                     ),
                     'categoryname' => array(
                            'name' => 'categoryname',
                            'type' => 'lookup',
                            'label' => 'Category',
                            'field' => 'categoryname',
                            'align' => 'text-left',
                            'style' => 'text-align:right;width:180px;whiteSpace: normal;min-width:180px;',
                            'readonly' => true,
                            'lookupclass' => 'lookuprscategory',
                            'action' => 'lookuprscategory'
                     ),
                     'irno' => array(
                            'name' => 'irno',
                            'type' => 'input',
                            'label' => 'Ref Incident Report #',
                            'field' => 'irno',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'articlename' => array(
                            'name' => 'articlename',
                            'type' => 'input',
                            'label' => 'Article Description',
                            'field' => 'articlename',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),
                     'violationno' => array(
                            'name' => 'violationno',
                            'type' => 'input',
                            'label' => 'Times Violated',
                            'field' => 'violationno',
                            'align' => 'text-left',
                            'style' => 'width:20px;whiteSpace: normal;min-width:20px;',
                            'readonly' => false
                     ),
                     'detail' => array(
                            'name' => 'detail',
                            'type' => 'input',
                            'label' => 'Details',
                            'field' => 'detail',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),
                     'appliedhrs' => array(
                            'name' => 'appliedhrs',
                            'field' => 'appliedhrs',
                            'type' => 'input',
                            'label' => 'Applied Hours',
                            'align' => 'text-left',
                            'style' => 'width:50px;whiteSpace: normal;min-width:50px;',
                            'readonly' => true
                     ),
                     'approvedhrs' => array(
                            'name' => 'approvedhrs',
                            'field' => 'approvedhrs',
                            'type' => 'input',
                            'label' => 'Approved Hours',
                            'align' => 'text-left',
                            'style' => 'width:50px;whiteSpace: normal;min-width:50px;',
                            'readonly' => false
                     ),
                     'crate' => array(
                            'name' => 'crate',
                            'type' => 'input',
                            'label' => 'Rate (percentage)',
                            'field' => 'crate',
                            'align' => 'text-right',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false
                     ),

                     'isevaluator' => array(
                            'name' => 'isevaluator',
                            'type' => 'toggle',
                            'label' => 'Evaluator',
                            'field' => 'isevaluator',
                            'align' => 'text-left',
                            'style' => 'width:100px;whiteSpace: normal;min-width:100px;',
                            'readonly' => false,
                            'checkfield' => ''
                     ),

                     'requestby' => array(
                            'name' => 'requestby',
                            'field' => 'requestby',
                            'type' => 'input',
                            'label' => 'Request By',
                            'align' => 'text-left',
                            'style' => 'width: 280px;whiteSpace: normal;min-width:280px;max-width:290px;text-align:left;',
                            'readonly' => false
                     ),

                     'assignto' => array(
                            'name' => 'assignto',
                            'field' => 'assignto',
                            'type' => 'input',
                            'label' => 'Assigned',
                            'align' => 'text-left',
                            'style' => 'width: 280px;whiteSpace: normal;min-width:280px;max-width:290px;text-align:left;',
                            'readonly' => false
                     ),
                     'iscomm' => array(
                            'name' => 'iscomm',
                            'type' => 'toggle',
                            'label' => 'Deducted to Commission',
                            'field' => 'iscomm',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'namt2' => array(
                            'name' => 'namt2',
                            'type' => 'input',
                            'label' => 'Net Whole',
                            'field' => 'namt2',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),

                     'namt5' => array(
                            'name' => 'namt5',
                            'type' => 'input',
                            'label' => 'Net Invoice',
                            'field' => 'namt5',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'namt7' => array(
                            'name' => 'namt7',
                            'type' => 'input',
                            'label' => 'Net DR',
                            'field' => 'namt7',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'isdailytask' => array(
                            'name' => 'isdailytask',
                            'type' => 'toggle',
                            'label' => 'Not Include in Pending App',
                            'field' => 'isdailytask',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => true
                     ),
                     'iss' => array(
                            'name' => 'iss',
                            'type' => 'input',
                            'label' => 'OUT',
                            'field' => 'iss',
                            'align' => 'text-center',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),
                     'namt4' => array(
                            'name' => 'namt4',
                            'type' => 'input',
                            'label' => 'Net Cost',
                            'field' => 'namt4',
                            'align' => 'text-left',
                            'style' => 'width:120px;whiteSpace: normal;min-width:120px;',
                            'readonly' => false
                     ),

                     'disc4' => array(
                            'name' => 'disc4',
                            'type' => 'input',
                            'label' => 'Cost Discount',
                            'field' => 'disc4',
                            'align' => 'text-left',
                            'style' => 'width:90px;whiteSpace:normal;min-width:90px;',
                            'readonly' => false
                     ),

                     'jono' => array(
                            'name' => 'jono',
                            'type' => 'input',
                            'label' => 'JO#',
                            'field' => 'jono',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),
                     'filename' => array(
                            'name' => 'filename',
                            'type' => 'label',
                            'label' => 'Image',
                            'field' => 'filename',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false
                     ),


                     'complexity' => array(
                            'name' => 'complexity',
                            'type' => 'lookup',
                            'label' => 'Task Complexity',
                            'field' => 'complexity',
                            'align' => 'text-left',
                            'style' => 'text-align:right;width:180px;whiteSpace: normal;min-width:180px;',
                            'readonly' => true,
                            'lookupclass' => 'lookupcomplex',
                            'action' => 'lookupsetup'
                     ),
                     'taskcategory' => array(
                            'name' => 'taskcategory',
                            'type' => 'lookup',
                            'label' => 'Task Category',
                            'field' => 'taskcategory',
                            'align' => 'text-left',
                            'style' => 'text-align:right;width:180px;whiteSpace: normal;min-width:180px;',
                            'readonly' => true,
                            'lookupclass' => 'lookuptaskcategory',
                            'action' => 'lookupsetup'
                     ),

                     'ctag' => array(
                            'name' => 'ctag',
                            'type' => 'toggle',
                            'label' => '',
                            'field' => 'ctag',
                            'align' => 'text-left',
                            'style' => 'width:150px;whiteSpace: normal;min-width:150px;',
                            'readonly' => false,
                            'checkfield' => ''
                     ),
                     'listinfratype' => array(
                            'name' => 'infratype',
                            'field' => 'infratype',
                            'type' => 'input',
                            'label' => 'Infra Type',
                            'align' => 'text-center',
                            'style' => 'width: 150px;whiteSpace: normal;min-width:150px;max-width:160px;text-align:left;',
                            'readonly' => true
                     ),
                     'listregdate' => array(
                            'name' => 'regdate',
                            'field' => 'regdate',
                            'type' => 'input',
                            'label' => 'Register Date',
                            'align' => 'text-left',
                            'style' => 'width: 90px;whiteSpace: normal;min-width:90px;max-width:100px;',
                            'readonly' => true
                     ),

                     'address1' => array(
                            'name' => 'address1',
                            'field' => 'address1',
                            'type' => 'input',
                            'label' => 'Collection Details Address',
                            'align' => 'text-left',
                            'style' => 'width: 200px;whiteSpace: normal;min-width:200px;max-width:200px;',
                            'readonly' => false
                     ),

                     'cperson' => array(
                            'name' => 'cperson',
                            'field' => 'cperson',
                            'type' => 'input',
                            'label' => 'Collection Contact Name',
                            'align' => 'text-left',
                            'style' => 'width: 200px;whiteSpace: normal;min-width:200px;max-width:200px;',
                            'readonly' => false
                     ),

                     'contactno2' => array(
                            'name' => 'contactno2',
                            'field' => 'contactno2',
                            'type' => 'input',
                            'label' => 'Collection Contact#',
                            'align' => 'text-left',
                            'style' => 'width: 200px;whiteSpace: normal;min-width:200px;max-width:200px;',
                            'readonly' => false
                     ),


              );

              return $this->columns;
       }

       public function __construct() {} // end function

} // end class
