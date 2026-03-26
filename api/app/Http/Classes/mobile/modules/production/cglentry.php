<?php
namespace App\Http\Classes\mobile\modules\production;

use App\Http\Classes\builder\mobiletxtFieldClass;
use App\Http\Classes\builder\mobileButtonClass;

class cglentry {
  private $fieldClass;
  private $buttonClass;
  private $company;

  public function __construct() {
    $this->fieldClass = new mobiletxtFieldClass;
    $this->buttonClass = new mobileButtonClass;
    $this->company = env('COMPANY', 'sbc');
  }
  public function getLayout() {
    $fields = ['scanstat', 'searchcoil', 'refresh'];
    $cfHeadFields = $this->fieldClass->create($fields);
    data_set($cfHeadFields, 'refresh.func', 'loadTableData');
    data_set($cfHeadFields, 'refresh.functype', 'module');

    $fields = ['docno', 'dateid', 'clientname', 'lcno', 'pdrno', 'ourref'];
    $cfTableCols = $this->fieldClass->create($fields);
    data_set($cfTableCols, 'docno.field', 'docno');
    data_set($cfTableCols, 'dateid.field', 'dateid');
    data_set($cfTableCols, 'clientname.type', 'label');
    data_set($cfTableCols, 'clientname.field', 'clientname');
    $btns = ['viewdoc'];
    $cfTableButtons = $this->buttonClass->create($btns);
    $btns = ['download'];
    $cfTableHeadButtons = $this->buttonClass->create($btns);

    $cLookupHeadFields = [];
    $cLookupHeadButtons = [];
    $cLookupTableCols = [];
    $cLookupTableButtons = [];
    $fields = ['docno', 'dateid', 'clientname', 'lcno'];
    $docLookupFields = $this->fieldClass->create($fields);
    data_set($docLookupFields, 'docno.type', 'label');
    data_set($docLookupFields, 'docno.label', 'Doc #: ');
    data_set($docLookupFields, 'dateid.type', 'label');
    data_set($docLookupFields, 'dateid.label', 'Date: ');
    data_set($docLookupFields, 'clientname.type', 'label');
    data_set($docLookupFields, 'clientname.label', 'Supplier: ');
    data_set($docLookupFields, 'lcno.type', 'label');
    data_set($docLookupFields, 'lcno.label', 'LC No.: ');

    $btns = ['scan', 'upload'];
    $docLookupButtons = $this->buttonClass->create($btns);
    array_push($cLookupHeadButtons, ['form'=>'cglentrydocLookupHeadButtons', 'btns'=>$docLookupButtons]);

    $btns = ['stockinfo'];
    $docLookupTableButtons = $this->buttonClass->create($btns);

    array_push($cLookupTableButtons, ['form'=>'cglentrydocLookupTableButtons', 'btns'=>$docLookupTableButtons]);

    array_push($cLookupHeadFields, ['form'=>'cglentrydocLookupFields', 'fields'=>$docLookupFields]);

    $fields = ['bundleno', 'itemname', 'itemnetweight', 'itemgrossweight', 'rem'];
    $docLookupTableCols = $this->fieldClass->create($fields);
    data_set($docLookupTableCols, 'itemname.type', 'label');
    data_set($docLookupTableCols, 'itemname.field', 'itemname');
    array_push($cLookupTableCols, ['form'=>'cglentrydocLookupTableCols', 'fields'=>$docLookupTableCols]);

    $inputLookupFields = [];
    $fields = ['bundleno', 'itemname', 'itemnetweight', 'itemgrossweight', 'drno', 'rem'];
    $stockLookupFields = $this->fieldClass->create($fields);
    data_set($stockLookupFields, 'bundleno.type', 'input');
    data_set($stockLookupFields, 'itemnetweight.type', 'input');
    data_set($stockLookupFields, 'itemgrossweight.type', 'input');
    data_set($stockLookupFields, 'rem.type', 'input');
    array_push($inputLookupFields, ['form'=>'stockLookupFields', 'fields'=>$stockLookupFields]);

    $inputLookupButtons = [];
    $btns = ['saverecord', 'cancelrecord'];
    $stockLookupButtons = $this->buttonClass->create($btns);
    data_set($stockLookupButtons, 'saverecord.form', 'stockLookupButtons');
    data_set($stockLookupButtons, 'saverecord.func', 'submitDR');
    data_set($stockLookupButtons, 'saverecord.functype', 'global');
    data_set($stockLookupButtons, 'cancelrecord.form', 'stockLookupButtons');
    data_set($stockLookupButtons, 'cancelrecord.func', 'cancelDR');
    data_set($stockLookupButtons, 'cancelrecord.functype', 'global');
    array_push($inputLookupButtons, ['form'=>'stockLookupButtons', 'btns'=>$stockLookupButtons]);

    return ['cfHeadFields'=>$cfHeadFields, 'cfTableCols'=>$cfTableCols, 'cfTableButtons'=>$cfTableButtons, 'cfTableHeadButtons'=>$cfTableHeadButtons, 'cLookupHeadFields'=>$cLookupHeadFields, 'cLookupHeadButtons'=>$cLookupHeadButtons, 'cLookupTableCols'=>$cLookupTableCols, 'cLookupTableButtons'=>$cLookupTableButtons, 'inputLookupFields'=>$inputLookupFields, 'inputLookupButtons'=>$inputLookupButtons];
  }

  public function getFunc() {
    return '({
      docForm: { scanstat: "0", searchcoil: "" },
      tableData: [],
      loadTableData: function () {
        console.log("loadTableData receiving report called");
        let filter = "";
        let scanned = " and isentry=0";
        let docbref = sbc.globalFunc.getDocBref();
        let data = [docbref.bref];
        cfunc.showLoading();
        if (sbc.modulefunc.docForm.searchcoil !== "") {
          filter = " and bundleno like ?";
          data.push("%" + sbc.modulefunc.docForm.searchcoil + "%");
        }
        if (sbc.modulefunc.docForm.scanstat === "1") scanned = " and isentry=1";
        sbc.db.transaction(function (tx) {
          tx.executeSql("select trno, line, docno, bundleno, itemname, isentry from myentry where bref=?" + filter + scanned, data, function (tx, res) {
            sbc.modulefunc.tableData = [];
            if (res.rows.length > 0) {
              for (var x = 0; x < res.rows.length; x++) {
                sbc.modulefunc.tableData.push(res.rows.item(x));
              }
            }
            $q.loading.hide();
          }, function (tx, err) {
            console.log(err);
            $q.loading.hide();
          });
        });
      },
      loadDocStocksProd: function () {
        let scanned = "";
        let filter = "";
        let qry = "select * from stock where trno=?";
        let data = [sbc.globalFunc.selDocProd.trno];
        if (sbc.globalFunc.searchCoil !== "" && sbc.globalFunc.searchCoil !== null && typeof (sbc.globalFunc.searchCoil) !== "undefined") {
          qry += " and bundleno like ?";
          data.push("%" + sbc.globalFunc.searchCoil + "%");
        }
        scanned = " and (rem = ? or isscanned=  0)";
        data.push("");
        if (sbc.globalFunc.scanStat === 1) {
          scanned = " and (rem <> ? or isscanned = 1)";
        }
        qry += scanned;
        sbc.modulefunc.lookupTableData = [];
        sbc.db.transaction(function (tx) {
          tx.executeSql(qry, data, function (tx, res) {
            console.log("bbbbbbbbbbbbb", res.rows.length);
            if (res.rows.length > 0) {
              for (var x = 0; x < res.rows.length; x++) {
                console.log("11111111111111111", res.rows.item(x));
                sbc.modulefunc.lookupTableData.push(res.rows.item(x));
              }
            }
            $q.loading.hide();
          }, function (tx, err) {
            console.log("zzzzzzzzzz", err);
            cfunc.showMsgBox("Error loading stocks, " + err.message, "negative", "warning");
            $q.loading.hide();
          });
        }, function (err) {
          console.log("lllllllllllll", err);
        });
      }
    })';
  }
}