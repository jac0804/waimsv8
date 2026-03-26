<?php

namespace App\Http\Classes\builder;

use DB;
use Exception;
use Throwable;

class helpClass
{

    private $fields = [];

    public function __construct()
    {
        $this->fields = [
            'btnnew' => [
                'target' => '.btnnew',
                'header' => ['title' => 'New button'],
                'content' => 'Click this to create new document and generate automatic document-series number',
                'params' => ['placement' => 'right']
            ],
            'btndelete' => [
                'target' => '.btndelete',
                'header' => ['title' => 'Delete button in Header'],
                'content' => 'Click to delete whole transaction',
                'params' => ['placement' => 'bottom']
            ],
            'btnsave' => [
                'target' => '.btnsave',
                'header' => ['title' => 'Save button'],
                'content' => 'Click to save this document <br> Transaction saved successfully. <br> You may now add item/s to this transaction',
                'params' => ['placement' => 'bottom']
            ],
            'btnedit' => [
                'target' => '.btnedit',
                'header' => ['title' => 'Edit button'],
                'content' => 'Click this to allow edit the details in Head',
                'params' => ['placement' => 'bottom']
            ],
            'btnadddocument' => [
                'target' => '.btnadddocument',
                'header' => ['title' => 'Supplier Lookup'],
                'content' => 'Discover <strong>Vue Tour</strong>!',
                'params' => ['placement' => 'top']
            ],


            //textbox                
            'supplier' => [
                'target' => '.csclient',
                'header' => ['title' => 'Supplier lookup'],
                'content' => 'Click this to select supplier',
                'params' => ['placement' => 'right']
            ],
            'department' => [
                'target' => '.csclient',
                'header' => ['title' => 'Depertment lookup'],
                'content' => 'Click this to select department',
                'params' => ['placement' => 'right']
            ],
            'dept' => [
                'target' => '.csdept',
                'header' => ['title' => 'Depertment lookup'],
                'content' => 'Click this to select department',
                'params' => ['placement' => 'right']
            ],
            'customer' => [
                'target' => '.csclient',
                'header' => ['title' => 'Customer lookup'],
                'content' => 'Click this to select customer',
                'params' => ['placement' => 'right']
            ],
            'customersupplier' => [
                'target' => '.csclient',
                'header' => ['title' => 'Customer/Supplier lookup'],
                'content' => 'Click this to select customer/supplier',
                'params' => ['placement' => 'right']
            ],
            'dcontra' => [
                'target' => '.cscontra',
                'header' => ['title' => 'Account lookup'],
                'content' => 'Click this to select account',
                'params' => ['placement' => 'right']
            ],
            'destination' => [
                'target' => '.csclient',
                'header' => ['title' => 'Destination lookup'],
                'content' => 'Click this to select destination',
                'params' => ['placement' => 'right']
            ],
            'warehouse' => [
                'target' => '.csclient',
                'header' => ['title' => 'Warehouse lookup'],
                'content' => 'Click this to select warehouse',
                'params' => ['placement' => 'right']
            ],
            'dateid' => [
                'target' => '.csdateid',
                'header' => ['title' => 'Date picker'],
                'content' => 'Click or type to change default date',
                'params' => ['placement' => 'right']
            ],
            'terms' => [
                'target' => '.csterms',
                'header' => ['title' => 'Term lookup'],
                'content' => 'Click to select term',
                'params' => ['placement' => 'right']
            ],
            'cswhname' => [
                'target' => '.cswhname',
                'header' => ['title' => 'Warehouse lookup'],
                'content' => 'Click to select warehouse for the item',
                'params' => ['placement' => 'right']
            ],
            'whcode' => [
                'target' => '.cswh',
                'header' => ['title' => 'Warehouse lookup'],
                'content' => 'Click to select warehouse for the item',
                'params' => ['placement' => 'right']
            ],
            'yourref' => [
                'target' => '.csyourref',
                'header' => ['title' => 'Your ref '],
                'content' => 'Type document reference if there any',
                'params' => ['placement' => 'right']
            ],
            'ourref' => [
                'target' => '.csourref',
                'header' => ['title' => 'Our ref '],
                'content' => 'Type document reference if there any',
                'params' => ['placement' => 'right']
            ],
            'cur' => [
                'target' => '.cscur',
                'header' => ['title' => 'You can change the dafault Forex'],
                'content' => 'Elaborate <strong> Here </strong>!',
                'params' => ['placement' => 'right']
            ],
            'csrem' => [
                'target' => '.csrem',
                'header' => ['title' => 'Note/Remarks'],
                'content' => 'Type remarks if ther any',
                'params' => ['placement' => 'right']
            ],

            //btn tab                
            'btnadditem' => [
                'target' => '.btnadditem',
                'header' => ['title' => 'add item button'],
                'content' => 'Click to select item from products list',
                'params' => ['placement' => 'top']
            ],
            'btnaddaccount' => [
                'target' => '.btnadditem',
                'header' => ['title' => 'add account button'],
                'content' => 'Click to select account from account list',
                'params' => ['placement' => 'top']
            ],

            'btnquickadd' => [
                'target' => '.btnquickadd',
                'header' => ['title' => 'Quick add button'],
                'content' => 'If you know the barcode, click this button and type/scan the barcode for faster way to add item',
                'params' => ['placement' => 'top']
            ],
            'btnsaveitem' => [
                'target' => '.btnsaveitem',
                'header' => ['title' => 'Save button(all items)'],
                'content' => 'Click to save all items',
                'params' => ['placement' => 'top']
            ],
            'btnsaveaccount' => [
                'target' => '.btnsaveitem',
                'header' => ['title' => 'Save button(all accounts)'],
                'content' => 'Click to save all accounts',
                'params' => ['placement' => 'top']
            ],

            'btndeleteallitem' => [
                'target' => '.btndeleteallitem',
                'header' => ['title' => 'Delete button(all items)'],
                'content' => 'Click to Delete all items',
                'params' => ['placement' => 'top']
            ],

            'btndeleteallaccount' => [
                'target' => '.btndeleteallitem',
                'header' => ['title' => 'Delete button(all accounts)'],
                'content' => 'Click to Delete all accounts',
                'params' => ['placement' => 'top']
            ],

            'btnunpaidkr' => [
                'target' => '.btnunpaidkr',
                'header' => ['title' => 'add unpaid button'],
                'content' => 'Click to select unpaid transaction from list',
                'params' => ['placement' => 'top']
            ],

            'btnrchecks' => [
                'target' => '.btnrchecks',
                'header' => ['title' => 'add unpaid button'],
                'content' => 'Click to select unpaid transaction from list',
                'params' => ['placement' => 'top']
            ],


            'btnundepositeddscol' => [
                'target' => '.btnundepositeddscol',
                'header' => ['title' => 'add uncollected button'],
                'content' => 'Click to select uncollected transaction from list',
                'params' => ['placement' => 'top']
            ],

            'amount' => [
                'target' => '.amount',
                'header' => ['title' => 'Amount'],
                'content' => 'Encode Amount',
                'params' => ['placement' => 'right']
            ],

            //grid column field                
            'rrqty' => [
                'target' => '.rrqty',
                'header' => ['title' => 'Quantity'],
                'content' => 'Encode the item quantity',
                'params' => ['placement' => 'right']
            ],

            'reqqty' => [
                'target' => '.reqqty',
                'header' => ['title' => 'Request quantity'],
                'content' => 'Encode the item request quantity',
                'params' => ['placement' => 'right']
            ],

            'rrcost' => [
                'target' => '.rrcost',
                'header' => ['title' => 'Amount'],
                'content' => 'Encode the item unit cost',
                'params' => ['placement' => 'right']
            ],

            'isqty' => [
                'target' => '.isqty',
                'header' => ['title' => 'Quantity'],
                'content' => 'Encode the item quantity',
                'params' => ['placement' => 'right']
            ],

            'isamt' => [
                'target' => '.isamt',
                'header' => ['title' => 'Amount'],
                'content' => 'Encode the item amount',
                'params' => ['placement' => 'right']
            ],

            'uom' => [
                'target' => '.uom',
                'header' => ['title' => 'UOM lookup'],
                'content' => 'Click this to change necessary unit of measurement of the item',
                'params' => ['placement' => 'right']
            ],

            'disc' => [
                'target' => '.disc',
                'header' => ['title' => 'Discount'],
                'content' => 'Encode the item discount if there any',
                'params' => ['placement' => 'right']
            ],

            'wh' => [
                'target' => '.wh',
                'header' => ['title' => 'Warehouse Lookup'],
                'content' => 'Click to change warehouse',
                'params' => ['placement' => 'right']
            ],

            'rem' => [
                'target' => '.rem',
                'header' => ['title' => 'Notes/Remarks'],
                'content' => 'Encode Notes/Remarks per record',
                'params' => ['placement' => 'right']
            ],

            'db' => [
                'target' => '.db',
                'header' => ['title' => 'Debit'],
                'content' => 'Encode debit',
                'params' => ['placement' => 'right']
            ],

            'cr' => [
                'target' => '.cr',
                'header' => ['title' => 'Credit'],
                'content' => 'Encode credit',
                'params' => ['placement' => 'right']
            ],

            'maxqty' => [
                'target' => '.maxqty',
                'header' => ['title' => 'Max Quantity'],
                'content' => 'Encode the item quantity',
                'params' => ['placement' => 'right']
            ],


            //grid button column                
            'btnstocksave' => [
                'target' => '.btnstocksave',
                'header' => ['title' => 'Save button(per item)'],
                'content' => 'Click to save per item',
                'params' => ['placement' => 'right']
            ],

            'btnstocksaveaccount' => [
                'target' => '.btnstocksave',
                'header' => ['title' => 'Save button(per account)'],
                'content' => 'Click to save per account',
                'params' => ['placement' => 'right']
            ],

            'btnstockdelete' => [
                'target' => '.btnstockdelete',
                'header' => ['title' => 'Delete button(per item)'],
                'content' => 'Click to delete per item',
                'params' => ['placement' => 'right']
            ],

            'btnstockdeleteaccount' => [
                'target' => '.btnstockdelete',
                'header' => ['title' => 'Delete button(per account)'],
                'content' => 'Click to delete per account',
                'params' => ['placement' => 'right']
            ],


        ];
    }


    public function getFields($fieldnames)
    {
        $txtfield = [];
        $i = 0;
        foreach ($fieldnames as $key) {
            $txtfield[$i] = $this->fields[$key];
            $i++;
        }
        return $txtfield;
    }
}
