<?php

namespace App\Http\Classes\mobile\modules\inventoryapp;

use App\Http\Classes\builder\mobiletxtFieldClass;
use App\Http\Classes\builder\mobileButtonClass;

class inventory
{
  private $fieldClass;
  private $buttonClass;
  private $company;

  public function __construct()
  {
    $this->fieldClass = new mobiletxtFieldClass;
    $this->buttonClass = new mobileButtonClass;
    $this->company = env('appcompany', 'mbs');
  }

  public function getLayout()
  {
    $cLookupFooterFields = [];

    $fields = ['wh', 'loc', 'dateid'];
    $cfTableCols = $this->fieldClass->create($fields);
    data_set($cfTableCols, 'wh.label', 'Warehouse');
    data_set($cfTableCols, 'wh.align', 'center');
    data_set($cfTableCols, 'loc.type', 'label');
    data_set($cfTableCols, 'loc.label', 'Location');
    data_set($cfTableCols, 'loc.field', 'loc');
    data_set($cfTableCols, 'loc.sortable', true);
    data_set($cfTableCols, 'loc.align', 'center');
    data_set($cfTableCols, 'dateid.type', 'label');
    data_set($cfTableCols, 'dateid.field', 'dateid');
    data_set($cfTableCols, 'dateid.sortable', true);
    data_set($cfTableCols, 'dateid.align', 'center');

    $fields = ['transtype'];
    $cfTableHeadFields = $this->fieldClass->create($fields);
    data_set($cfTableHeadFields, 'transtype.type', 'option');
    data_set($cfTableHeadFields, 'transtype.options', '[{ name: "unposted", label: "Unposted" }, { name: "posted", label: "Posted" }]');
    data_set($cfTableHeadFields, 'transtype.func', 'loadTableData');
    data_set($cfTableHeadFields, 'transtype.functype', 'module');

    $btns = ['download', 'upload', 'generate', 'create'];
    $cfTableHeadButtons = $this->buttonClass->create($btns);
    data_set($cfTableHeadButtons, 'download.func', 'downloadInventoryCon');
    data_set($cfTableHeadButtons, 'download.functype', 'module');
    if ($this->company == 'ulitc') {
      data_set($cfTableHeadButtons, 'upload.func', 'uploadInventoryDoc');
    } else {
      data_set($cfTableHeadButtons, 'upload.func', 'uploadInvMBSDoc');
    }
    data_set($cfTableHeadButtons, 'upload.functype', 'module');
    data_set($cfTableHeadButtons, 'upload.label', 'Upload');
    data_set($cfTableHeadButtons, 'generate.label', 'Generate');
    data_set($cfTableHeadButtons, 'create.label', '');
    data_set($cfTableHeadButtons, 'create.func', 'createInventoryDoc');
    data_set($cfTableHeadButtons, 'create.label', 'Add');

    $cLookupHeadFields = [];
    $fields = [['saveinventory', 'deleteinventory'], 'wh', 'loc', 'brand', 'scanitem'];
    $invDocLookupHeadFields = $this->fieldClass->create($fields);
    data_set($invDocLookupHeadFields, 'wh.type', 'select');
    data_set($invDocLookupHeadFields, 'wh.options', []);
    data_set($invDocLookupHeadFields, 'wh.label', 'Warehouse');
    data_set($invDocLookupHeadFields, 'wh.readonly', true);
    data_set($invDocLookupHeadFields, 'wh.enterfunc', '');
    data_set($invDocLookupHeadFields, 'loc.label', 'Location');
    data_set($invDocLookupHeadFields, 'loc.readonly', false);
    data_set($invDocLookupHeadFields, 'brand.type', 'select');
    data_set($invDocLookupHeadFields, 'brand.options', []);
    data_set($invDocLookupHeadFields, 'brand.readonly', false);
    data_set($invDocLookupHeadFields, 'brand.enterfunc', '');
    data_set($invDocLookupHeadFields, 'brand.multiple', true);
    data_set($invDocLookupHeadFields, 'brand.selectall', true);
    data_set($invDocLookupHeadFields, 'brand.class', 'cut-text');
    data_set($invDocLookupHeadFields, 'scanitem.style', 'margin-top:20px;');
    data_set($invDocLookupHeadFields, 'scanitem.enterfunc', 'scanBarcode');
    data_set($invDocLookupHeadFields, 'scanitem.functype', 'module');
    data_set($invDocLookupHeadFields, 'saveInventory.label', 'Save');
    data_set($invDocLookupHeadFields, 'saveinventory.show', true);
    data_set($invDocLookupHeadFields, 'deleteinventory.label', 'Delete');
    data_set($invDocLookupHeadFields, 'deleteinventory.show', true);
    array_push($cLookupHeadFields, ['form' => 'invDocLookupHeadFields', 'fields' => $invDocLookupHeadFields]);

    $cLookupTableCols = [];
    $fields = ['barcode', 'sku', 'itemname', 'brand', 'syscount', 'qty', 'variance'];
    $invDocLookupTableCols = $this->fieldClass->create($fields);
    data_set($invDocLookupTableCols, 'barcode.type', 'label');
    data_set($invDocLookupTableCols, 'barcode.field', 'barcode');
    data_set($invDocLookupTableCols, 'barcode.label', 'Item Code');
    data_set($invDocLookupTableCols, 'barcode.sortable', true);
    data_set($invDocLookupTableCols, 'brand.label', 'Brand');
    data_set($invDocLookupTableCols, 'brand.field', 'brand');
    data_set($invDocLookupTableCols, 'itemname.type', 'label');
    data_set($invDocLookupTableCols, 'itemname.field', 'itemname');
    data_set($invDocLookupTableCols, 'itemname.label', 'Item Desc.');
    data_set($invDocLookupTableCols, 'itemname.sortable', true);
    array_push($cLookupTableCols, ['form' => 'invDocLookupTableCols', 'fields' => $invDocLookupTableCols]);

    $fields = [['total', 'skuqty', 'qty']];
    $invDocLookupFooterFields = $this->fieldClass->create($fields);
    data_set($invDocLookupFooterFields, 'total.type', 'label');
    data_set($invDocLookupFooterFields, 'total.field', 'total');
    data_set($invDocLookupFooterFields, 'total.fields', '');
    data_set($invDocLookupFooterFields, 'total.style', 'font-size:100%;');
    data_set($invDocLookupFooterFields, 'total.label', 'TOTAL');
    data_set($invDocLookupFooterFields, 'qty.label', 'Qty: ');
    data_set($invDocLookupFooterFields, 'qty.style', 'font-size:100%;');
    data_set($invDocLookupFooterFields, 'qty.fields', '');
    array_push($cLookupFooterFields, ['form' => 'invDocLookupFooterFields', 'fields' => $invDocLookupFooterFields]);

    $inputLookupFields = [];
    $inputLookupButtons = [];

    $fields = ['barcode', 'itemname', 'qty'];
    $stockLookupFields = $this->fieldClass->create($fields);
    data_set($stockLookupFields, 'barcode.type', 'label');
    data_set($stockLookupFields, 'barcode.label', 'Barcode: ');
    data_set($stockLookupFields, 'itemname.type', 'label');
    data_set($stockLookupFields, 'itemname.label', 'Item Desc: ');
    data_set($stockLookupFields, 'qty.type', 'input');
    data_set($stockLookupFields, 'qty.autofocus', true);
    data_set($stockLookupFields, 'qty.enterfunc', 'saveStockQty');
    data_set($stockLookupFields, 'qty.functype', 'module');
    array_push($inputLookupFields, ['form' => 'stockLookupFields', 'fields' => $stockLookupFields]);

    $btns = ['saverecord', 'cancelrecord'];
    $stockLookupButtons = $this->buttonClass->create($btns);
    data_set($stockLookupButtons, 'saverecord.func', 'saveStockQty');
    data_set($stockLookupButtons, 'saverecord.functype', 'module');
    data_set($stockLookupButtons, 'cancelrecord.func', 'cancelStockQty');
    data_set($stockLookupButtons, 'cancelrecord.functype', 'module');
    array_push($inputLookupButtons, ['form' => 'stockLookupButtons', 'btns' => $stockLookupButtons]);

    $fields = ['barcode', 'itemname', 'syscount', 'qty', 'sales', 'variance'];
    if ($this->company == 'mbs') $fields = ['barcode', 'itemname', 'syscount', 'qty', 'variance'];
    $generateInvTableCols = $this->fieldClass->create($fields);
    data_set($generateInvTableCols, 'barcode.type', 'label');
    data_set($generateInvTableCols, 'barcode.field', 'barcode');
    data_set($generateInvTableCols, 'barcode.sortable', true);
    data_set($generateInvTableCols, 'itemname.type', 'label');
    data_set($generateInvTableCols, 'itemname.field', 'itemname');
    data_set($generateInvTableCols, 'itemname.label', 'Item Name');
    data_set($generateInvTableCols, 'itemname.sortable', true);
    data_set($generateInvTableCols, 'syscount.align', 'right');
    data_set($generateInvTableCols, 'qty.align', 'right');
    data_set($generateInvTableCols, 'sales.align', 'right');
    data_set($generateInvTableCols, 'variance.align', 'right');
    array_push($cLookupTableCols, ['form' => 'generateInvTableCols', 'fields' => $generateInvTableCols]);

    $fields = ['generatetype'];
    $generateInvHeadFields = $this->fieldClass->create($fields);
    array_push($cLookupHeadFields, ['form' => 'generateInvHeadFields', 'fields' => $generateInvHeadFields]);

    $fields = ['itemcount'];
    $generateInvFooterFields = $this->fieldClass->create($fields);
    data_set($generateInvFooterFields, 'itemcount.style', 'font-size:100%');
    array_push($cLookupFooterFields, ['form' => 'generateInvFooterFields', 'fields' => $generateInvFooterFields]);

    $cLookupButtons = [];
    $btns = ['generate', 'viewdoc', 'senddoc'];
    $generateButtons = $this->buttonClass->create($btns);
    data_set($generateButtons, 'generate.func', 'generateDocument');
    data_set($generateButtons, 'generate.color', 'primary');
    data_set($generateButtons, 'generate.label', 'Generate');
    data_set($generateButtons, 'viewdoc.func', 'viewDocument');
    data_set($generateButtons, 'viewdoc.functype', 'module');
    data_set($generateButtons, 'viewdoc.label', 'View');
    array_push($cLookupButtons, ['form' => 'generateButtons', 'btns' => $generateButtons]);

    $fields = ['signee'];
    $signeeLookupFields = $this->fieldClass->create($fields);
    array_push($inputLookupFields, ['form' => 'signeeLookupFields', 'fields' => $signeeLookupFields]);

    $fields = ['signee2'];
    $signee2LookupFields = $this->fieldClass->create($fields);
    array_push($inputLookupFields, ['form' => 'signee2LookupFields', 'fields' => $signee2LookupFields]);

    $fields = ['signee3'];
    $signee3LookupFields = $this->fieldClass->create($fields);
    array_push($inputLookupFields, ['form' => 'signee3LookupFields', 'fields' => $signee3LookupFields]);

    $btns = ['saverecord', 'cancelrecord'];
    $signeeLookupButtons = $this->buttonClass->create($btns);
    data_set($signeeLookupButtons, 'saverecord.func', 'saveSignee');
    data_set($signeeLookupButtons, 'saverecord.functype', 'module');
    data_set($signeeLookupButtons, 'saverecord.label', 'Submit');
    data_set($signeeLookupButtons, 'cancelrecord.func', 'cancelSignee');
    data_set($signeeLookupButtons, 'cancelrecord.functype', 'module');
    array_push($inputLookupButtons, ['form' => 'signeeLookupButtons', 'btns' => $signeeLookupButtons]);

    $fields = ['email'];
    $emailLookupFields = $this->fieldClass->create($fields);
    data_set($emailLookupFields, 'email.label', 'Enter recipient address');
    data_set($emailLookupFields, 'email.type', 'input');
    data_set($emailLookupFields, 'email.autofocus', true);
    data_set($emailLookupFields, 'email.enterfunc', 'sendEmail');
    data_set($emailLookupFields, 'email.functype', 'module');
    array_push($inputLookupFields, ['form' => 'emailLookupFields', 'fields' => $emailLookupFields]);

    $btns = ['saverecord', 'cancelrecord'];
    $emailLookupButtons = $this->buttonClass->create($btns);
    data_set($emailLookupButtons, 'saverecord.func', 'sendEmail');
    data_set($emailLookupButtons, 'saverecord.functype', 'module');
    data_set($emailLookupButtons, 'saverecord.label', 'Send');
    data_set($emailLookupButtons, 'cancelrecord.func', 'cancelEmail');
    data_set($emailLookupButtons, 'cancelrecord.functype', 'module');
    array_push($inputLookupButtons, ['form' => 'emailLookupButtons', 'btns' => $emailLookupButtons]);

    $fields = ['qty'];
    $generateInputQtyField = $this->fieldClass->create($fields);
    data_set($generateInputQtyField, 'qty.type', 'input');
    data_set($generateInputQtyField, 'qty.autofocus', true);
    data_set($generateInputQtyField, 'qty.enterfunc', 'saveSoldQty');
    data_set($generateInputQtyField, 'qty.functype', 'module');
    array_push($inputLookupFields, ['form' => 'generateInputQtyField', 'fields' => $generateInputQtyField]);

    $btns = ['saverecord', 'cancelrecord'];
    $generateInputQtyBtns = $this->buttonClass->create($btns);
    data_set($generateInputQtyBtns, 'saverecord.func', 'saveSoldQty');
    data_set($generateInputQtyBtns, 'saverecord.functype', 'module');
    data_set($generateInputQtyBtns, 'cancelrecord.func', 'cancelSoldQty');
    data_set($generateInputQtyBtns, 'cancelrecord.functype', 'module');
    array_push($inputLookupButtons, ['form' => 'generateInputQtyBtns', 'btns' => $generateInputQtyBtns]);

    return ['cfTableCols' => $cfTableCols, 'cfTableHeadFields' => $cfTableHeadFields, 'cfTableHeadButtons' => $cfTableHeadButtons, 'cLookupHeadFields' => $cLookupHeadFields, 'cLookupTableCols' => $cLookupTableCols, 'inputLookupFields' => $inputLookupFields, 'inputLookupButtons' => $inputLookupButtons, 'cLookupButtons' => $cLookupButtons, 'cLookupFooterFields' => $cLookupFooterFields];
  }

  public function getFunc()
  {
    return '({
      docForm: { wh: [], loc: "", brand: [], transtype: "unposted", trno: 0, docstat: 0, dateid: "", brands: "" },
      cftableheadbuttonsalign: "right",
      txtSearchItem: "",
      tableData: [],
      loadTableData: function () {
        console.log("inventory loadtabledata called");
        // errorSound.value = error2;
        cfunc.showLoading();
        sbc.globalFunc.cfTableClick = true;
        sbc.globalFunc.cfTableClickFunctype = "module";
        sbc.globalFunc.cfTableClickFunc = "viewInventoryDoc";
        sbc.modulefunc.tableFilter = { type: "filter", field: "", label: "", func: "" };
        sbc.db.transaction(function (tx) {
          tx.executeSql("select * from item", [], function (tx, ress) {
            let itemss = [];
            for (var a = 0; a < ress.rows.length; a++) {
              itemss.push(ress.rows.item(a));
            }
          });
        });
        sbc.db.transaction(function (tx) {
          let qry = "select trno, wh, loc, brand, substr(dateid,1,10) as dateid, 0 as docstat from head order by trno asc";
          if (sbc.modulefunc.docForm.transtype === "posted") qry = "select trno, wh, loc, brand, substr(dateid,1,10) as dateid, 1 as docstat, uploaded from hhead order by trno asc";
          tx.executeSql(qry, [], function (tx, res) {
            let heads = [];
            sbc.modulefunc.tableData = [];
            if (res.rows.length > 0) {
              for (var x = 0; x < res.rows.length; x++) {
                heads.push(res.rows.item(x));
                if (parseInt(x) + 1 === res.rows.length) {
                  sbc.modulefunc.tableData = heads;
                  $q.loading.hide();
                }
              }
            } else {
              $q.loading.hide();
            }
          }, function (tx, err) {
            console.log("err1: ", err.message);
            $q.loading.hide();
          });
        }, function (err) {
          console.log("err2: ", err.message);
          $q.loading.hide();
        });
      },
      viewInventoryDoc: function (row) {
        console.log(row.uploaded);
        // if (row.uploaded === null || row.uploaded === undefined || row.uploaded === false || row.uploaded === 0 || row.uploaded === "") {
        // }
        sbc.modulefunc.viewDocType = "view";
        sbc.modulefunc.docForm.trno = row.trno;
        sbc.modulefunc.docForm.wh = row.wh;
        sbc.modulefunc.docForm.loc = row.loc;
        sbc.modulefunc.docForm.brand = row.brand;
        sbc.modulefunc.docForm.dateid = row.dateid;
        sbc.modulefunc.docForm.docstat = row.docstat;
        sbc.modulefunc.docForm.brands = row.brand;
        sbc.globalFunc.multiSelectOpts = row.brand.split(",");
        sbc.globalFunc.cLookupMaximized = true;
        sbc.modulefunc.loadInvDoc();
      },
      downloadInventoryItems: function () {
        sbc.db.transaction(function (tx) {
          tx.executeSql("select trno from head union all select trno from hhead where uploaded is null or uploaded = 0", [], function (tx, res) {
            if (res.rows.length > 0) {
              // cfunc.showMsgBox("Please upload all transactions first before downloading items", "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
              sbc.globalFunc.showErrMsg("Please upload all transactions first before downloading items");
            } else {
              tx.executeSql("select count(itemid) as icount from item", [], function (tx, ires) {
                if (ires.rows.item(0).icount > 0) {
                  $q.dialog({
                    message: "Downloading new items will delete transaction history, Do you want to continue?",
                    ok: { flat: true, color: "primary" },
                    cancel: { flat: true, color: "negative" }
                  }).onOk(() => {
                    contDownload();
                  });
                } else {
                  contDownload();
                }
              }, function (tx, err) {
                // cfunc.showMsgBox("Err1: " + err.message, "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
                sbc.globalFunc.showErrMsg("Err1: " + err.message);
              });
            }
          });
        }, function (err) {
          console.log("err2: ", err.message);
        });

        function contDownload () {
          cfunc.showLoading();
          cfunc.getTableData("config", "serveraddr").then(serveraddr => {
            if (serveraddr === "" || serveraddr === null || serveraddr === undefined) {
              // cfunc.showMsgBox("Server Address not set", "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
              sbc.globalFunc.showErrMsg("Server Address not set");
              return;
            }
            sbc.globalFunc.lookupTableSelect = true;
            sbc.globalFunc.lookupTableSelection = "multiple";
            sbc.globalFunc.lookupTableRowKey = "client";
            sbc.globalFunc.lookupCols = [
              { name: "client", label: "Code", align: "left", field: "client" },
              { name: "clientname", label: "Name", align: "left", field: "clientname" }
            ];
            let wh = [];
            api.post(serveraddr + "/sbcmobilev2/download", { type: "wh" }).then(res => {
              wh = res.data.wh;
              sbc.modulefunc.lookupTableFilter = { type: "filter", field: "", label: "Search Warehouse", func: "" };
              sbc.globalFunc.lookupData = wh;
              sbc.globalFunc.lookupAction = "whlookup";
              sbc.globalFunc.selectLookupBtnLabel = "Download";
              sbc.globalFunc.selectLookupType = "inventorywh";
              sbc.lookupTitle = "Select Warehouse";
              sbc.showSelectLookup = true;
              $q.loading.hide();
            }).catch(err => {
              // cfunc.showMsgBox(err.message, "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
              sbc.globalFunc.showErrMsg(err.message);
              $q.loading.hide();
            });
          });
        }
      },
      uploadInvMBSDoc: function () {
        sbc.globalFunc.lookupCols = [
          { name: "gtype", label: "", field: "gtype", align: "center", sortable: true }
        ];
        sbc.modulefunc.lookupTableFilter = { type: "filter", field: "", label: "Search", func: "" };
        sbc.globalFunc.lookupTableSelect = false;
        sbc.globalFunc.lookupAction = "mbsUpload";
        sbc.lookupTitle = "";
        sbc.globalFunc.lookupData = [
          { gtype: "Initial" },
          { gtype: "Final" }
        ];
        sbc.showLookup = true;
        $q.loading.hide();
      },
      uploadInventoryDoc: function () {
        console.log("uploadInventoryDoc called");
        cfunc.showLoading();
        sbc.db.transaction(function (tx) {
          tx.executeSql("select client, clientname, generated, uploaded, filename from wh", [], function (tx, res) {
            let whs = [];
            if (res.rows.length > 0) {
              for (var x = 0; x < res.rows.length; x++) {
                whs.push(res.rows.item(x));
                if (parseInt(x) + 1 === res.rows.length) contFunc(whs);
              }
            } else {
              // cfunc.showMsgBox("No data to upload", "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
              sbc.globalFunc.showErrMsg("No data to upload");
              $q.loading.hide();
            }
          }, function (tx, err) {
            console.log("err1: ", err.message);
            // cfunc.showMsgBox(err.message, "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
            sbc.globalFunc.showErrMsg(err.message);
            $q.loading.hide();
          });
        }, function (err) {
          console.log("err2: ", err.message);
          // cfunc.showMsgBox(err.message, "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
          sbc.globalFunc.showErrMsg(err.message);
          $q.loading.hide();
        });

        function contFunc (whs) {
          sbc.globalFunc.lookupCols = [
            { name: "client", label: "Code", align: "left", field: "client", sortable: true },
            { name: "clientname", label: "Name", align: "left", field: "clientname", sortable: true }
          ];
          sbc.modulefunc.lookupTableFilter = { type: "filter", field: "", label: "Search Warehouse", func: "" };
          sbc.globalFunc.lookupTableSelect = false;
          sbc.globalFunc.lookupRowsPerPage = 20;
          sbc.lookupTitle = "Warehouse List";
          sbc.globalFunc.lookupData = whs;
          sbc.showLookup = true;
          sbc.globalFunc.lookupAction = "invWhsLookup";
          sbc.modulefunc.whlookupType = "upload";
          $q.loading.hide();
        }
      },
      generateInventory: function () {
        console.log("generateInventory called");
        cfunc.showLoading();
        switch (sbc.globalFunc.company) {
          case "ulitc":
            sbc.globalFunc.lookupCols = [
              { name: "client", label: "Code", field: "client", align: "left", sortable: true },
              { name: "clientname", label: "Name", field: "clientname", align: "left", sortable: true }
            ];
            sbc.modulefunc.lookupTableFilter = { type: "filter", field: "", label: "Search Warehouse", func: "" };
            sbc.globalFunc.lookupTableSelect = false;
            sbc.globalFunc.lookupRowsPerPage = 20;
            sbc.globalFunc.lookupAction = "invWhsLookup";
            sbc.modulefunc.whlookupType = "generate";
            sbc.lookupTitle = "Warehouse List";
            sbc.db.transaction(function (tx) {
              tx.executeSql("select client, clientname, generated, uploaded, filename from wh", [], function (tx, res) {
                sbc.globalFunc.lookupData = [];
                let whs = [];
                sbc.showLookup = true;
                if (res.rows.length > 0) {
                  for (var x = 0; x < res.rows.length; x++) {
                    whs.push(res.rows.item(x));
                    if (parseInt(x) + 1 === res.rows.length) {
                      sbc.globalFunc.lookupData = whs;
                      $q.loading.hide();
                    }
                  }
                } else {
                  $q.loading.hide();
                }
              }, function (tx, err) {
                console.log("err1: ", err.message);
                sbc.globalFunc.showErrMsg(err.message);
                $q.loading.hide();
              });
            }, function (err) {
              console.log("err2: ", err.message);
              sbc.globalFunc.showErrMsg(err.message);
              $q.loading.hide();
            });
            break;
          case "mbs":
            sbc.globalFunc.lookupCols = [
              { name: "gtype", label: "", field: "gtype", align: "center", sortable: true }
            ];
            sbc.modulefunc.lookupTableFilter = { type: "filter", field: "", label: "Search", func: "" };
            sbc.globalFunc.lookupTableSelect = false;
            sbc.globalFunc.lookupAction = "mbsGenerate";
            sbc.lookupTitle = "";
            sbc.globalFunc.lookupData = [
              { gtype: "Initial" },
              { gtype: "Final" }
            ];
            sbc.showLookup = true;
            $q.loading.hide();
            break;
        }
      },
      createInventoryDoc: function () {
        console.log("createInventoryDoc called");
        if (sbc.globalFunc.company === "mbs") {
          sbc.modulefunc.isConsUploaded().then(res => {
            if (res) {
              sbc.globalFunc.showErrMsg("A physical count has already been uploaded, can no longer create transaction");
            } else {
              sbc.modulefunc.viewDocType = "new";
              sbc.globalFunc.cLookupMaximized = true;
              sbc.modulefunc.loadInvDoc();
            }
          })
        } else {
          sbc.modulefunc.viewDocType = "new";
          sbc.globalFunc.cLookupMaximized = true;
          sbc.modulefunc.loadInvDoc();
        }
      },
      loadInvDoc: function () {
        console.log("loadInvDoc called");
        let title = "Add Transaction";
        if (sbc.modulefunc.viewDocType !== "new") {
          title = sbc.modulefunc.docForm.wh;
          if (sbc.modulefunc.docForm.docstat === 1) title += " - POSTED";
        }
        sbc.cLookupTitle = title;
        // sbc.cLookupTitle = sbc.modulefunc.viewDocType === "new" ? "Add Transaction" : (sbc.modulefunc.docForm.wh);
        sbc.selclookupheadfields = sbc.globalFunc.getLookupForm(sbc.clookupheadfields, "invDocLookupHeadFields", "inputFields");
        sbc.selclookupheadfieldsplot = sbc.globalFunc.getLookupForm(sbc.clookupheadfieldsplot, "invDocLookupHeadFields", "inputPlot");
        sbc.selclookupheadbuttons = sbc.globalFunc.getLookupForm(sbc.clookupheadbuttons, "invDocLookupHeadButtons", "buttons");
        sbc.selclookuptablecols = sbc.globalFunc.getLookupForm(sbc.clookuptablecols, "invDocLookupTableCols", "inputFields");
        sbc.selclookuptablecolsplot = sbc.globalFunc.getLookupForm(sbc.clookuptablecolsplot, "invDocLookupTableCols", "inputPlot");
        sbc.selclookupfooterfields = sbc.globalFunc.getLookupForm(sbc.clookupfooterfields, "invDocLookupFooterFields", "inputFields");
        sbc.selclookupfooterfieldsplot = sbc.globalFunc.getLookupForm(sbc.clookupfooterfieldsplot, "invDocLookupFooterFields", "inputPlot");
        sbc.modulefunc.cLookupTableFilter = { type: "filter", field: "", label: "Search Item", func: "" };
        sbc.selclookupbuttons = [];
        sbc.selclookupheadfields.find(waw => waw.name === "wh").options = [];
        sbc.selclookupheadfields.find(waw => waw.name === "brand").options = [];
        if (sbc.globalFunc.company === "mbs") sbc.selclookupheadfields.find(waw => waw.name === "brand").show = "false";
        sbc.modulefunc.lookupTableData = [];
        let readonlyform = "false";
        sbc.modulefunc.cLookupForm = { total: "", skuqty: 0, qty: 0 };
        if (sbc.modulefunc.viewDocType === "new") {
          sbc.modulefunc.docForm.wh = [];
          sbc.modulefunc.docForm.brand = [];
          sbc.modulefunc.docForm.loc = "";
          sbc.modulefunc.docForm.brands = "";
          sbc.modulefunc.docForm.docstat = 0;
          sbc.globalFunc.multiSelectOpts = [];
          if (sbc.selclookupheadfields.length > 0) sbc.modulefunc.loadWHS();
          sbc.selclookupheadfields.find(waw => waw.name === "saveinventory").label = "save";
          sbc.selclookupheadfields.find(waw => waw.name === "saveinventory").icon = "save";
        } else {
          sbc.selclookupheadfields.find(waw => waw.name === "saveinventory").label = "post";
          sbc.selclookupheadfields.find(waw => waw.name === "saveinventory").icon = "fact_check";
          sbc.modulefunc.loadInvStock();
          readonlyform = "true";
        }
        if (sbc.modulefunc.docForm.docstat === 1) {
          for (var i in sbc.selclookupheadfields) {
            sbc.selclookupheadfields[i].readonly = readonlyform;
            if (sbc.globalFunc.company === "mbs") {
              if (sbc.selclookupheadfields[i].name === "wh") sbc.selclookupheadfields[i].readonly = "true";
            }
            if (sbc.selclookupheadfields[i].name === "saveinventory") sbc.selclookupheadfields[i].show = "false";
            if (sbc.selclookupheadfields[i].name === "deleteinventory") sbc.selclookupheadfields[i].show = "false";
          }
        } else {
          for (var i in sbc.selclookupheadfields) {
            switch (sbc.selclookupheadfields[i].name) {
              case "saveinventory": case "deleteinventory":
                sbc.selclookupheadfields[i].show = "true";
                break;
              case "scanitem":
                if (sbc.modulefunc.viewDocType === "new") {
                  sbc.selclookupheadfields[i].readonly = "true";
                } else {
                  sbc.selclookupheadfields[i].readonly = "false";
                  if (sbc.globalFunc.company === "mbs") {
                    if (sbc.selclookupheadfields[i].name === "wh") sbc.selclookupheadfields[i].readonly = "true";
                  }
                }
                break;
              default:
                sbc.selclookupheadfields[i].readonly = readonlyform;
                if (sbc.globalFunc.company === "mbs") {
                  if (sbc.selclookupheadfields[i].name === "wh") sbc.selclookupheadfields[i].readonly = "true";
                }
                break;
            }
          }
          // sbc.selclookupheadfields.find(waw => waw.name === "saveinventory").show = "true";
          // sbc.selclookupheadfields.find(waw => waw.name === "deleteInventory").show = "true";
          // sbc.selclookupheadfields.find(waw => waw.name === "wh").readonly = readonlyform;
          // sbc.selclookupheadfields.find(waw => waw.name === "loc").readonly = readonlyform;
          // sbc.selclookupheadfields.find(waw => waw.name === "brand").readonly = readonlyform;
          // if (sbc.modulefunc.viewDocType === "new") {
          //   sbc.selclookupheadfields.find(waw => waw.name === "scanitem").readonly = "true";
          // } else {
          //   sbc.selclookupheadfields.find(waw => waw.name === "scanitem").readonly = "false";
          // }
        }
        sbc.modulefunc.cLookupForm = sbc.modulefunc.docForm;
        sbc.modulefunc.cLookupForm.total = "";
        sbc.modulefunc.cLookupForm.skuqty = 0;
        sbc.modulefunc.cLookupForm.qty = 0;
        sbc.showCustomLookup = true;
        sbc.isFormEdit = true;
      },
      loadInvStock: function () {
        console.log("loadInvStock called");
        cfunc.showLoading("Loading stocks...");
        sbc.db.transaction(function (tx) {
          tx.executeSql("select trno, line, barcode, sku, itemname, brand, syscount, qty, variance, seq, null as bgColor from stock where trno=? union all select trno, line, barcode, sku, itemname, brand, syscount, qty, variance, seq, null as bgColor from hstock where trno=? order by seq desc", [sbc.modulefunc.docForm.trno, sbc.modulefunc.docForm.trno], function (tx, res) {
            sbc.modulefunc.lookupTableData = [];
            sbc.globalFunc.cLookupTableClick = true;
            sbc.globalFunc.cLookupTableClickFunc = "selectStock";
            sbc.globalFunc.cLookupTableClickFunctype = "module";
            let stocks = [];
            let skus = 0;
            let qtys = 0;
            if (res.rows.length > 0) {
              for (var x = 0; x < res.rows.length; x++) {
                stocks.push(res.rows.item(x));
                stocks[x].itemname = stocks[x].itemname.replace(/"/g, "``");
                console.log("skus: ", res.rows.item(x).sku);
                // if (res.rows.item(x).sku !== "" && res.rows.item(x).sku !== undefined && res.rows.item(x).sku !== null) skus += 1;
                skus += 1;
                qtys = parseInt(qtys) + parseInt(res.rows.item(x).qty);
                if (parseInt(x) + 1 === res.rows.length) {
                  stocks[0].bgColor = "bg-blue-2";
                  sbc.modulefunc.lookupTableData = stocks;
                  sbc.modulefunc.cLookupForm.skuqty = skus;
                  sbc.modulefunc.cLookupForm.qty = qtys;
                  console.log("-=====-", stocks);
                  $q.loading.hide();
                }
              }
            } else {
              $q.loading.hide();
            }
          }, function (tx, err) {
            console.log("err1: ", err.message);
            $q.loading.hide();
          });
        }, function (err) {
          console.log("err2: ", err.message);
          $q.loading.hide();
        });
      },
      loadWHS: function () {
        sbc.db.transaction(function (tx) {
          tx.executeSql("select client, uploaded, filename from wh", [], function (tx, res) {
            let whs = [];
            for (var x = 0; x < res.rows.length; x++) {
              if (res.rows.item(x).uploaded === null || res.rows.item(x).uploaded === undefined || res.rows.item(x).uploaded === 0 || res.rows.item(x).uploaded === false) {
                whs.push(res.rows.item(x).client);
              }
              if (parseInt(x) + 1 === res.rows.length) {
                sbc.selclookupheadfields.find(waw => waw.name === "wh").options = whs;
                sbc.modulefunc.docForm.wh = whs[0];
                sbc.modulefunc.loadBrands();
              }
            }
          });
        });
      },
      loadBrands: function () {
        console.log("loadBrands called");
        sbc.modulefunc.docForm.brand = "";
        if (sbc.modulefunc.docForm.wh !== "") {
          sbc.db.transaction(function (tx) {
            tx.executeSql("select distinct brand from item order by brand", [], function (tx, res) {
              if (res.rows.length) {
                let brands = [];
                for (var x = 0; x < res.rows.length; x++) {
                  brands.push(res.rows.item(x).brand);
                  if (parseInt(x) + 1 === res.rows.length) sbc.selclookupheadfields.find(waw => waw.name === "brand").options = brands;
                }
              }
            });
            // tx.executeSql("select distinct item.brand from item left join clientitem on clientitem.barcode=item.barcode where clientitem.wh=?", [sbc.modulefunc.docForm.wh], function (tx, res) {
            //   if (res.rows.length) {
            //     let brands = [];
            //     for (var x = 0; x < res.rows.length; x++) {
            //       brands.push(res.rows.item(x).brand);
            //       if (parseInt(x) + 1 === res.rows.length) sbc.selclookupheadfields.find(waw => waw.name === "brand").options = brands;
            //     }
            //   }
            // });
          });
        }
      },
      saveInventory: function () {
        console.log("saveInventory called");
        let docForm = sbc.modulefunc.docForm;
        let brands = sbc.globalFunc.multiSelectOpts;
        let sbutton = sbc.selclookupheadfields.find(waw => waw.name === "saveinventory").label;
        if (sbutton.toLowerCase() === "save") {
          if (docForm.wh === "" || docForm.wh === null || docForm.wh === undefined) {
            sbc.globalFunc.showErrMsg("Please select warehouse");
            return;
          }
          if (docForm.loc === "" || docForm.loc === null || docForm.loc === undefined) {
            sbc.globalFunc.showErrMsg("Please enter Location");
            return;
          }
          if (brands.length === 0 && sbc.globalFunc.company !== "mbs") {
            sbc.globalFunc.showErrMsg("Please select brand");
            return;
          } else {
            brands = brands.join(",");
          }
          sbc.db.transaction(function (tx) {
            tx.executeSql("select trno from head where wh=? and loc=? union all select trno from hhead where wh=? and loc=?", [docForm.wh, docForm.loc, docForm.wh, docForm.loc], function (tx, ress) {
              if (ress.rows.length > 0) {
                sbc.globalFunc.showErrMsg("Duplicate record for warehouse and location, Please try again");
              } else {
                $q.dialog({
                  message: "Do you want to save this transaction?",
                  ok: { flat: true, color: "primary" },
                  cancel: { flat: true, color: "negative" }
                }).onOk(() => {
                  cfunc.showLoading();
                  const datenow = cfunc.getDateTime("datetime");
                  sbc.db.transaction(function (tx) {
                    tx.executeSql("insert into head(wh, loc, brand, dateid) values(?, ?, ?, ?)", [docForm.wh, docForm.loc, brands, datenow], function (tx, res) {
                      cfunc.showMsgBox("Transaction saved", "positive");
                      sbc.modulefunc.docForm.trno = res.insertId;
                      sbc.modulefunc.docForm.dateid = datenow;
                      sbc.modulefunc.docForm.docstat = 0;
                      sbc.modulefunc.docForm.brands = brands;
                      sbc.cLookupTitle = docForm.wh;
                      sbc.selclookupheadfields.find(waw => waw.name === "wh").readonly = "true";
                      sbc.selclookupheadfields.find(waw => waw.name === "loc").readonly = "true";
                      sbc.selclookupheadfields.find(waw => waw.name === "brand").readonly = "true";
                      sbc.modulefunc.docForm.brand = sbc.globalFunc.multiSelectOpts;
                      console.log("----++++----", sbc.globalFunc.multiSelectOpts);
                      sbc.selclookupheadfields.find(waw => waw.name === "saveinventory").label = "post";
                      sbc.selclookupheadfields.find(waw => waw.name === "saveinventory").icon = "fact_check";
                      sbc.selclookupheadfields.find(waw => waw.name === "scanitem").readonly = "false";
                      // for (var i in sbc.selclookupheadfields) {
                      //   switch (sbc.selclookupheadfields[i].name) {
                      //     case "wh": case "loc": case "brand":
                      //       sbc.selclookupheadfields[i].readonly = "true";
                      //       break;
                      //     case "scanitem":
                      //       sbc.selclookupheadfields[i].readonly = "false";
                      //       break;
                      //     case "saveinventory":
                      //       sbc.selclookupheadfields[i].label = "post":
                      //       sbc.selclookupheadfields[i].icon = "fact_check";
                      //       break;
                      //   }
                      // }
                      sbc.modulefunc.loadTableData();
                      $q.loading.hide();
                    }, function (tx, err) {
                      // cfunc.showMsgBox("Error saving transaction err:" + err.message, "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
                      sbc.globalFunc.showErrMsg("Error saving transaction err: " + err.message);
                      $q.loading.hide();
                    });
                  });
                });
              }
            }, function (tx, err) {
              // cfunc.showMsgBox(err.message, "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
              sbc.globalFunc.showErrMsg(err.message);
              $q.loading.hide();
            });
          });
        } else {
          if (sbc.modulefunc.docForm.docstat === 0) {
            $q.dialog({
              message: "Do you want to post this transaction?",
              ok: { flat: true, color: "primary" },
              cancel: { flat: true, color: "negative" }
            }).onOk(() => {
              cfunc.showLoading();
              sbc.db.transaction(function (tx) {
                tx.executeSql("insert into hhead(trno, wh, loc, brand, dateid) select trno, wh, loc, brand, dateid from head where trno=?", [docForm.trno], function (tx, res) {
                  tx.executeSql("insert into hstock(trno, line, barcode, sku, itemname, brand, syscount, qty, variance, seq, wh, dateid) select trno, line, barcode, sku, itemname, brand, syscount, qty, variance, seq, wh, dateid from stock where trno=?", [docForm.trno], function (tx, res) {
                    tx.executeSql("delete from head where trno=?", [docForm.trno]);
                    tx.executeSql("delete from stock where trno=?", [docForm.trno]);
                    cfunc.showMsgBox("Transaction posted", "positive");
                    for (var i in sbc.selclookupheadfields) {
                      if (sbc.selclookupheadfields[i].name === "saveinventory" || sbc.selclookupheadfields[i].name === "deleteinventory") {
                        sbc.selclookupheadfields[i].show = "false";
                      } else {
                        sbc.selclookupheadfields[i].readonly = "true";
                      }
                    }
                    sbc.modulefunc.docForm.docstat = 1;
                    sbc.modulefunc.docForm.transtype = "posted";
                    sbc.cLookupTitle += "- POSTED";
                    sbc.modulefunc.loadTableData();
                    $q.loading.hide();
                  }, function (tx, err) {
                    console.log("err3: ", err.message);
                    // cfunc.showMsgBox(err.message, "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
                    sbc.globalFunc.showErrMsg(err.message);
                    $q.loading.hide();
                  });
                }, function (tx, err) {
                  console.log("err1: ", err.message);
                  // cfunc.showMsgBox(err.message, "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
                  sbc.globalFunc.showErrMsg(err.message);
                  $q.loading.hide();
                });
              }, function (err) {
                console.log("err2: ", err.message);
                // cfunc.showMsgBox(err.message, "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
                sbc.globalFunc.showErrMsg(err.message);
                $q.loading.hide();
              });
            });
          }
        }
      },
      deleteInventory: function () {
        if (sbc.modulefunc.docForm.transtype === "unposted" && sbc.modulefunc.docForm.trno !== "" && sbc.modulefunc.docForm.trno !== 0 && sbc.modulefunc.docForm.trno !== undefined) {
          $q.dialog({
            message: "Do you want to delete this transaction?",
            ok: { flat: true, color: "primary" },
            cancel: { flat: true, color: "negative" }
          }).onOk(() => {
            cfunc.showLoading();
            sbc.db.transaction(function (tx) {
              tx.executeSql("delete from head where trno=?", [sbc.modulefunc.docForm.trno]);
              tx.executeSql("delete from stock where trno=?", [sbc.modulefunc.docForm.trno]);
              cfunc.showMsgBox("Transaction deleted", "positive");
              sbc.modulefunc.loadTableData();
              sbc.showCustomLookup = false;
              sbc.modulefunc.docForm.trno = 0;
              sbc.modulefunc.docForm.wh = [];
              sbc.modulefunc.docForm.loc = "";
              sbc.modulefunc.docForm.brand = [];
              $q.loading.hide();
            }, function (err) {
              console.log("err1: ", err.message);
              // cfunc.showMsgBox(err.message, "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
              sbc.globalFunc.showErrMsg(err.message);
              $q.loading.hide();
            });
          });
        }
      },
      clearItems: function () {
        return new Promise((resolve) => {
          sbc.db.transaction(function (tx) {
            tx.executeSql("delete from wh");
            tx.executeSql("delete from item");
            tx.executeSql("delete from itembal");
            tx.executeSql("delete from clientitem");
            resolve();
          });
        });
      },
      downloadSelectedWH: function () {
        let idata = [];
        let iend = 0;
        let isavedcount = 0;
        let icount = 0;

        let ibdata = [];
        let ibend = 0;
        let ibsavedcount = 0;
        let ibcount = 0;

        let icdata = [];
        let icend = 0;
        let icsavedcount = 0;
        let iccount = 0;

        let whs = [];
        let whscount = 0;
        if (sbc.globalFunc.lookupSelected.length > 0) {
          sbc.modulefunc.clearItems().then(() => {
            saveWH();
          });
        } else {
          // cfunc.showMsgBox("No WH(s) selected", "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
          sbc.globalFunc.showErrMsg("No WH(s) selected");
        }

        function saveWH () {
          let swhs = [];
          sbc.globalFunc.lookupSelected.map((waw, i, rows) => {
            swhs.push({ client: waw.client, clientname: waw.clientname });
            if (i + 1 === rows.length) {
              whscount = rows.length;
              let d = swhs;
              let dd = [];
              while (d.length) dd.push(d.splice(0, 100));
              save(dd);
            }
          });

          function save (whd, index = 0) {
            cfunc.showLoading("Saving Warehouse Data (Batch " + index + " of " + whd.length + ")");
            if (index === 0) $q.loading.hide();
            if (index === whd.length) {
              cfunc.showLoading("Successfully imported " + whscount + " Warehouse");
              setTimeout(function () {
                $q.loading.hide();
                contDownloadDetails();
              }, 1500);
            } else {
              sbc.db.transaction(function (tx) {
                for (var a in whd[index]) {
                  insertWH(whd[index][a]);
                  if (parseInt(a) + 1 === whd[index].length) save(whd, parseInt(index) + 1);
                }
              });
            }
          }

          function insertWH (data) {
            sbc.db.transaction(function (tx) {
              let qry = "insert into wh(client, clientname) values(?, ?)";
              let param = [data.client, data.clientname];
              tx.executeSql(qry, param, function (tx, res) {
                console.log("=========", res);
              }, function (tx, err) {
                cfunc.saveErrLog(qry, param, err.message);
              });
            });
          }
        }

        function contDownloadDetails () {
          sbc.globalFunc.lookupSelected.map((waw, i, rows) => {
            whs.push(waw.client);
            if (i + 1 === rows.length) {
              cfunc.getTableData("config", "serveraddr").then(serveraddr => {
                if (serveraddr === "" || serveraddr === null || serveraddr === undefined) {
                  // cfunc.showMsgBox("Server Address not set", "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
                  sbc.globalFunc.showErrMsg("Server Address not set");
                  return;
                }
                getItems(serveraddr);
              });
            }
          });
        }

        function getItems (serveraddr) {
          cfunc.showLoading("Downloading Items, Please wait...");
          api.post(serveraddr + "/sbcmobilev2/download", { type: "invItems", iend: iend, whs: whs }).then(res => {
            if (res) {
              if (res.data.items.length > 0) {
                if (iend === 0) icount = res.data.icount;
                if (isavedcount !== icount) {
                  saveItems(res.data.items, serveraddr);
                } else {
                  cfunc.showLoading(`Successfully imported ${icount} Items`);
                  setTimeout(function () {
                    $q.loading.hide();
                    getItemBal(serveraddr);
                  }, 1500);
                }
                isavedcount += res.data.items.length;
                iend = res.data.iend;
              } else {
                cfunc.showLoading(`Successfully imported ${icount} Items`);
                setTimeout(function () {
                  $q.loading.hide();
                  getItemBal(serveraddr);
                }, 1500);
              }
            } else {
              $q.loading.hide();
            }
          });
        }

        function saveItems (data, serveraddr) {
          idata = { data: { inserts: { item: data } } };
          cordova.plugins.sqlitePorter.importJsonToDb(sbc.db, idata, {
            successFn: function () {
              getItems(serveraddr);
            },
            errorFn: function (error) {
              // cfunc.showMsgBox("Import Error: " + error.message, "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
              sbc.globalFunc.showErrMsg("Import Error: " + error.message);
              $q.loading.hide();
            },
            progressFn: function (current, total) {
              cfunc.showLoading("Saving Items (Batch " + current + " of " + total + ")");
            }
          });
        }

        function getItemBal (serveraddr) {
          // cfunc.showLoading("Downloading Item Balance, Please wait...");
          // api.post(serveraddr + "/sbcmobilev2/download", { type: "invItemBal", whs: whs, ibend: ibend }).then(res => {
          //   if (res) {
          //     if (res.data.itembal.length > 0) {
          //       if (ibend === 0) ibcount = res.data.ibcount;
          //       ibsavedcount += res.data.itembal.length;
          //       ibend = res.data.ibend;
          //       if (ibsavedcount !== ibcount) {
          //         saveItemBal(res.data.itembal, serveraddr);
          //       } else {
          //         cfunc.showLoading(`Successfully imported ${ibcount} Item Balance`);
          //         setTimeout(function () {
          //           $q.loading.hide();
          //           getClientItems(serveraddr);
          //         }, 1500);
          //       }
          //     } else {
          //       cfunc.showLoading(`Successfully imported ${ibcount} Item Balance`);
          //       setTimeout(function () {
          //         $q.loading.hide();
          //         getClientItems(serveraddr);
          //       }, 1500);
          //     }
          //   } else {
          //     $q.loading.hide();
          //   }
          // });

          cfunc.showLoading("Downloading Item Balance, Please wait...");
          api.post(serveraddr + "/sbcmobilev2/download", { type: "invItemBal", whs: whs }).then(res => {
            if (res) {
              if (res.data.itembal.length > 0) {
                ibcount = res.data.itembal.length;
                saveItemBal(res.data.itembal, serveraddr);
              } else {
                // cfunc.showMsgBox("No Item Balance to download", "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
                sbc.globalFunc.showErrMsg("No Item Balance to download");
                getClientItems(serveraddr);
              }
            } else {
              $q.loading.hide();
            }
          }).catch(err => {
            // cfunc.showMsgBox("error downloading item balance. " + err.message, "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
            sbc.globalFunc.showErrMsg("Error downloading item balance. " + err.message);
            $q.loading.hide();
          });
        }

        function saveItemBal (data, serveraddr) {
          // ibdata = { data: { inserts: { itembal: data } } };
          // cordova.plugins.sqlitePorter.importJsonToDb(sbc.db, ibdata, {
          //   successFn: function () {
          //     getItemBal(serveraddr);
          //   },
          //   errorFn: function (error) {
          //     cfunc.showMsgBox("Import Error: " + error.message, "negative", "warning");
          //     $q.loading.hide();
          //   },
          //   progressFn: function (current, total) {
          //     cfunc.showLoading("Saving Item Balance (Batch " + current + " of " + total + ")");
          //   }
          // });
          var d = data;
          var dd = [];
          while (d.length) dd.push(d.splice(0, 100));
          save(dd);

          function save (itembal, index = 0) {
            cfunc.showLoading("Saving Item Balance (Batch " + index + " of " + itembal.length + ")");
            if (index === 0) $q.loading.hide();
            if (index === itembal.length) {
              cfunc.showLoading("Successfully imported " + ibcount + " Item Balance and Client Items");
              setTimeout(function () {
                $q.loading.hide();
                getClientItems(serveraddr);
              }, 1500);
            } else {
              sbc.db.transaction(function (tx) {
                for (var a in itembal[index]) {
                  insertItemBal(itembal[index][a]);
                  if (parseInt(a) + 1 === itembal[index].length) save(itembal, parseInt(index) + 1);
                }
              });
            }
          }

          function insertItemBal (data) {
            sbc.db.transaction(function (tx) {
              let qry = "insert into itembal(itemid, bal, wh) values(?, ?, ?)";
              let param = [data.itemid, data.bal, data.wh];
              tx.executeSql(qry, param, function (tx, res) {
                // console.log("itembal saved");
              }, function (tx, err) {
                cfunc.saveErrLog(qry, param, err.message);
              });
            });
          }
        }

        function getClientItems (serveraddr) {
          cfunc.showLoading("Downloading Client Items, Please wait...");
          api.post(serveraddr + "/sbcmobilev2/download", { type: "invClientItem", whs: whs }).then(res => {
            if (res) {
              if (res.data.clientitem.length > 0) {
                iccount = res.data.clientitem.length;
                saveClientItem(res.data.clientitem, serveraddr);
              } else {
                sbc.globalFunc.showErrMsg("No Client Item to download");
                $q.loading.hide();
              }
            } else {
              $q.loading.hide();
            }
          });

          // api.post(serveraddr + "/sbcmobilev2/download", { type: "invClientItem", whs: whs, icend: icend }).then(res => {
          //   if (res) {
          //     if (res.data.clientitem.length > 0) {
          //       if (icend === 0) iccount = res.data.clientitem.length;
          //       if (icsavedcount !== iccount) {
          //         saveClientItem(res.data.clientitem, serveraddr);
          //       } else {
          //         cfunc.showLoading(`Successfully imported ${iccount} Client Items`);
          //         setTimeout(function () {
          //           $q.loading.hide();
          //           sbc.showSelectLookup = false;
          //           sbc.globalFunc.lookupSelected = [];
          //         }, 1500);
          //       }
          //       icend = res.data.icend;
          //       icsavedcount += res.data.clientitem.length;
          //     } else {
          //       if (icsavedcount !== iccount) {
          //         $q.loading.hide();
          //       } else {
          //         cfunc.showLoading(`Successfully imported ${iccount} Client Items`);
          //         setTimeout(function () {
          //           $q.loading.hide();
          //           sbc.showSelectLookup = false;
          //           sbc.globalFunc.lookupSelected = [];
          //         }, 1500);
          //       }
          //     }
          //   } else {
          //     $q.loading.hide();
          //   }
          // }).catch(err => {
          //   // cfunc.showMsgBox("Error downloading client items. " + err.message, "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
          //   sbc.globalFunc.showErrMsg("Error downloading client items. " + err.message);
          //   $q.loading.hide();
          // });


          // cfunc.showLoading("Downloading Client Items, Please wait...");
          // api.post(serveraddr + "/sbcmobilev2/download", { type: "invClientItem", whs: whs }).then(res => {
          //   if (res) {
          //     if (res.data.clientitem.length > 0) {
          //       iccount = res.data.clientitem.length;
          //       saveClientItem(res.data.clientitem);
          //     } else {
          //       cfunc.showMsgBox("No Client Item to download", "negative", "warning");
          //       sbc.globalFunc.lookupSelected = [];
          //       sbc.showSelectLookup = false;
          //       $q.loading.hide();
          //     }
          //   } else {
          //     $q.loading.hide();
          //   }
          // });
        }

        function saveClientItem (data) {
          let d = data;
          let dd = [];
          while (d.length) dd.push(d.splice(0, 100));
          save(dd);

          function save (clientitem, index = 0) {
            cfunc.showLoading("Saving Client Items (Batch " + index + " of " + clientitem.length + ")");
            if (index === 0) $q.loading.hide();
            if (index === clientitem.length) {
              cfunc.showLoading("Successfully imported " + iccount + " Client Items");
              setTimeout(function () {
                $q.loading.hide();
              }, 1500);
            } else {
              sbc.db.transaction(function (tx) {
                for (var a in clientitem[index]) {
                  insertClientItem(clientitem[index][a]);
                  if (parseInt(a) + 1 === clientitem[index].length) save(clientitem, parseInt(index) + 1);
                }
              });
            }
          }

          function insertClientItem (data) {
            sbc.db.transaction(function (tx) {
              let qry = "insert into clientitem(wh, barcode, sku) values(?, ?, ?)";
              let param = [data.wh, data.barcode, data.sku];
              tx.executeSql(qry, param, null, function (tx, err) {
                cfunc.saveErrLog(qry, param, err.message);
              });
            });
          }


          // icdata = { data: { inserts: { clientitem: data } } };
          // cordova.plugins.sqlitePorter.importJsonToDb(sbc.db, icdata, {
          //   successFn: function () {
          //     getClientItems(serveraddr);
          //   },
          //   errorFn: function (error) {
          //     // cfunc.showMsgBox("Import Error: " + error.message, "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
          //     sbc.globalFunc.showErrMsg("Import Error: " + error.message);
          //     $q.loading.hide();
          //   },
          //   progressFn: function (current, total) {
          //     cfunc.showLoading("Saving Client Items (Batch " + current + " of " + total + ")");
          //   }
          // })




          // var d = data;
          // var dd = [];
          // while (d.length) dd.push(d.splice(0, 100));
          // save(dd);

          // function save (citem, index = 0) {
          //   cfunc.showLoading("Saving Client Items (Batch " + index + " of " + citem.length + ")");
          //   if (index === 0) $q.loading.hide();
          //   if (index === citem.length) {
          //     cfunc.showLoading("Successfully imported " + iccount + " Client Items");
          //     setTimeout(function () {
          //       $q.loading.hide();
          //       sbc.showSelectLookup = false;
          //       sbc.globalFunc.lookupSelected = [];
          //     }, 1500);
          //   } else {
          //     sbc.db.transaction(function (tx) {
          //       for (var a in citem[index]) {
          //         insertClientItem(citem[index][a]);
          //         if (parseInt(a) + 1 === citem[index].length) save(citem, parseInt(index) + 1);
          //       }
          //     });
          //   }
          // }

          // function insertClientItem (data) {
          //   sbc.db.transaction(function (tx) {
          //     let qry = "insert into clientitem(wh, barcode, sku) values(?, ?, ?)";
          //     let param = [data.wh, data.barcode, data.sku];
          //     tx.executeSql(qry, param, function (tx, res) {
          //       console.log("---------- clientitem saved");
          //     }, function (tx, err) {
          //       cfunc.saveErrLog(qry, param, err.message);
          //     })
          //   });
          // }
        }
      },
      loadItems: function () {
        console.log("loadItems called", sbc.modulefunc.txtSearchItem);
        if (sbc.modulefunc.docForm.trno === "" || sbc.modulefunc.docForm.trno === 0 || sbc.modulefunc.docForm.trno === undefined) {
          // cfunc.showMsgBox("Please save transaction first", "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
          sbc.globalFunc.showErrMsg("Please save transaction first");
          return;
        }
        if (sbc.modulefunc.docForm.docstat === 0) {
          cfunc.showLoading("Loading items...");
          sbc.globalFunc.lookupCols = [
            { name: "barcode", label: "Item Code", align: "left", field: "barcode", sortable: true },
            { name: "itemname", label: "Item Name", align: "left", field: "itemname", sortable: true },
            { name: "brand", label: "Brand", align: "left", field: "brand", sortable: true },
            { name: "bal", label: "Balance", align: "right", field: "bal", sortable: false }
          ];
          sbc.globalFunc.visibleCols = ["barcode", "itemname", "brand", "bal"];
          let brands = sbc.modulefunc.docForm.brands;
          brands = brands.split(",");
          brands = brands.map(waw => `"${waw}"`).join(",");
          // sbc.modulefunc.lookupTableFilter = { type: "filter", field: "", label: "Search Item", func: "" };
          sbc.modulefunc.lookupTableFilter = { type: "searchItem", field: "txtSearchItem", label: "Search Item", func: "searchItem", livesearch: true, debounce: "500" };
          sbc.globalFunc.lookupTableSelect = false;
          sbc.globalFunc.lookupRowsPerPage = 20;
          sbc.lookupTitle = "Items List";
          sbc.showLookup = true;
          sbc.db.transaction(function (tx) {
            let qry = "select item.itemid, item.barcode, item.itemname, item.brand, item.partno, round(ifnull(cast(itembal.bal as float), 0), 2) as bal, clientitem.sku from item left join itembal on itembal.itemid=cast(item.itemid as integer) left join clientitem on clientitem.barcode=item.barcode where item.brand in (" + brands + ") order by item.barcode asc";
            if (sbc.globalFunc.company === "mbs") {
              // qry = "select item.itemid, item.barcode, item.itemname, item.brand, item.partno, round(ifnull(cast(itembal.bal as float), 0), 2) as bal, clientitem.sku from item left join itembal on itembal.itemid=cast(item.itemid as integer) left join clientitem on clientitem.barcode=item.barcode order by item.barcode asc";
              qry = "select item.itemid, item.barcode, item.itemname, item.brand, item.partno, round(ifnull(cast(item.bal as float), 0), 2) as bal, clientitem.sku from item left join clientitem on clientitem.barcode=item.barcode order by item.barcode asc";
            }
            tx.executeSql(qry, [], function (tx, res) {
              sbc.globalFunc.lookupData = [];
              let items = [];
              if (res.rows.length > 0) {
                for (var x = 0; x < res.rows.length; x++) {
                  items.push(res.rows.item(x));
                  if (parseInt(x) + 1 === res.rows.length) {
                    sbc.globalFunc.lookupData = items;
                    $q.loading.hide();
                  }
                }
              } else {
                $q.loading.hide();
              }
            }, function (tx, err) {
              console.log("err1:", err.message);
              $q.loading.hide();
            });
          }, function (err) {
            console.log("err2:", err.message);
            $q.loading.hide();
          });
        }
      },
      scanBarcode: function () {
        console.log("scanbarcode called");
        if (sbc.modulefunc.cLookupForm.scanitem !== "") {
          cfunc.showLoading();
          let brands = sbc.modulefunc.docForm.brands;
          brands = brands.split(",");
          brands = brands.map(waw => `"${waw}"`).join(",");
          if (sbc.modulefunc.docForm.trno !== 0 && sbc.modulefunc.docForm.trno !== "" && sbc.modulefunc.docForm.trno !== undefined) {
            sbc.db.transaction(function (tx) {
              // let qry = "select item.itemid, item.barcode, item.itemname, item.partno, item.brand, round(ifnull(cast(itembal.bal as float), 0), 2) as bal, clientitem.sku from item left join itembal on itembal.itemid=cast(item.itemid as integer) left join clientitem on clientitem.barcode=item.barcode where item.brand in (" + brands + ") and (lower(item.barcode)=? or lower(item.partno)=?) limit 1";
              // if (sbc.globalFunc.company === "mbs") {
              //   // qry = "select item.itemid, item.barcode, item.itemname, item.partno, item.brand, round(ifnull(cast(itembal.bal as float), 0), 2) as bal, clientitem.sku from item left join itembal on itembal.itemid=cast(item.itemid as integer) left join clientitem on clientitem.barcode=item.barcode where (lower(item.barcode)=? or lower(item.partno)=?) limit 1";
              //   qry = "select item.itemid, item.barcode, item.itemname, item.partno, item.brand, round(ifnull(cast(item.bal as float), 0), 2) as bal, clientitem.sku from item left join clientitem on clientitem.barcode=item.barcode where (lower(item.barcode)=? or lower(item.partno)=?) limit 1";
              // }
              let qry = "select item.itemid, item.barcode, item.itemname, item.partno, item.brand, round(ifnull(cast(item.bal as float), 0), 2) as bal, clientitem.sku from item left join clientitem on clientitem.barcode=item.barcode where (lower(item.barcode)=? or lower(item.partno)=?) limit 1";
              tx.executeSql(qry, [sbc.modulefunc.cLookupForm.scanitem.toLowerCase(), sbc.modulefunc.cLookupForm.scanitem.toLowerCase()], function (tx, res) {
                if (res.rows.length > 0) {
                  let seq = 0;
                  console.log("-----------------scanBarcode: ", res.rows.item(0));
                  tx.executeSql("select seq from stock where trno=? order by seq desc limit 1", [sbc.modulefunc.docForm.trno], function (tx, res1) {
                    if (res1.rows.length > 0) seq = res1.rows.item(0).seq;
                    tx.executeSql("select barcode from stock where trno=? and lower(barcode)=?", [sbc.modulefunc.docForm.trno, res.rows.item(0).barcode.toLowerCase()], function (tx, res2) {
                      if (res2.rows.length > 0) {
                        // sbc.globalFunc.showErrMsg(res.rows.item(0).bal);
                        tx.executeSql("update stock set qty=qty+1, seq=?, variance=syscount-(qty+1) where trno=? and lower(barcode)=?", [seq + 1, sbc.modulefunc.docForm.trno, res.rows.item(0).barcode.toLowerCase()], function (tx, res3) {
                          cfunc.showMsgBox("Stock updated", "positive");
                          sbc.modulefunc.cLookupForm.scanitem = "";
                          $q.loading.hide();
                          sbc.modulefunc.loadInvStock();
                        }, function (tx, err) {
                          // cfunc.showMsgBox(err.message, "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
                          sbc.globalFunc.playSound("error1");
                          sbc.globalFunc.showErrMsg(err.message);
                          $q.loading.hide();
                          console.log("err3: ", err.message);
                        });
                      } else {
                        // sbc.globalFunc.showErrMsg(res.rows.item(0).bal);
                        tx.executeSql("insert into stock(trno, barcode, sku, itemname, brand, syscount, qty, variance, seq, wh, dateid) values(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [sbc.modulefunc.docForm.trno, res.rows.item(0).barcode, res.rows.item(0).sku, res.rows.item(0).itemname, res.rows.item(0).brand, res.rows.item(0).bal, 1, (res.rows.item(0).bal - 1), seq + 1, sbc.modulefunc.docForm.wh, sbc.modulefunc.docForm.dateid], function (tx, res4) {
                          cfunc.showMsgBox("Item saved", "positive");
                          sbc.modulefunc.cLookupForm.scanitem = "";
                          $q.loading.hide();
                          sbc.modulefunc.loadInvStock();
                        }, function (tx, err) {
                          // cfunc.showMsgBox(err.message, "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
                          sbc.globalFunc.playSound("error1");
                          sbc.globalFunc.showErrMsg(err.message);
                          $q.loading.hide();
                          console.log("err1: ", err.message);
                        });
                      }
                    }, function (tx, err) {
                      // cfunc.showMsgBox(err.message, "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
                      sbc.globalFunc.playSound("error1");
                      sbc.globalFunc.showErrMsg(err.message);
                      $q.loading.hide();
                      console.log("error2: ", err.message);
                    });
                  }, function (tx, err) {
                    // cfunc.showMsgBox(err.message, "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
                    sbc.globalFunc.playSound("error1");
                    sbc.globalFunc.showErrMsg(err.message);
                    $q.loading.hide();
                    console.log("err3: ", err.message);
                  });
                } else {
                  sbc.modulefunc.cLookupForm.scanitem = "";
                  // cfunc.showMsgBox("Item not found.", "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
                  sbc.globalFunc.playSound("error1");
                  sbc.globalFunc.showErrMsg("Item not found");
                  $q.loading.hide();
                }
              }, function (tx, err) {
                // cfunc.showMsgBox(err.message, "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
                sbc.globalFunc.playSound("error1");
                sbc.globalFunc.showErrMsg(err.message);
                $q.loading.hide();
                console.log("err4: ", err.message);
              });
            });
          }
        } else {
          // cfunc.showMsgBox("Please enter/scan barcode", "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
          sbc.globalFunc.playSound("error1");
          sbc.globalFunc.showErrMsg("Please enter/scan barcode");
        }
      },
      selectItem: function (row) {
        console.log("selectItem called", row);
        $q.dialog({
          message: "Do you want to add this item? (" + row.syscount + ")",
          ok: { flat: true, color: "primary" },
          cancel: { flat: true, color: "negative" }
        }).onOk(() => {
          cfunc.showLoading();
          if (sbc.modulefunc.docForm.trno !== 0 && sbc.modulefunc.docForm.trno !== "" && sbc.modulefunc.docForm.trno !== undefined) {
            sbc.db.transaction(function (tx) {
              let seq = 0;
              tx.executeSql("select seq from stock where trno=? order by seq desc limit 1", [sbc.modulefunc.docForm.trno], function (tx, res1) {
                if (res1.rows.length > 0) seq = res1.rows.item(0).seq;
                tx.executeSql("select barcode from stock where trno=? and barcode=?", [sbc.modulefunc.docForm.trno, row.barcode], function (tx, res) {
                  if (res.rows.length > 0) {
                    tx.executeSql("update stock set qty=qty+1, seq=?, variance=syscount-(qty+1) where trno=? and barcode=?", [seq + 1, sbc.modulefunc.docForm.trno, row.barcode], function (tx, res) {
                      cfunc.showMsgBox("Stock updated", "positive");
                      $q.loading.hide();
                      sbc.modulefunc.loadInvStock();
                    }, function (tx, err) {
                      // cfunc.showMsgBox(err.message, "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
                      sbc.globalFunc.showErrMsg(err.message);
                      $q.loading.hide();
                      console.log("err3: ", err.message);
                    });
                  } else {
                    tx.executeSql("insert into stock(trno, barcode, sku, itemname, brand, syscount, qty, variance, seq, wh, dateid) values(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [sbc.modulefunc.docForm.trno, row.barcode, row.sku, row.itemname, row.brand, row.bal, 1, (row.bal - 1), seq + 1, sbc.modulefunc.docForm.wh, sbc.modulefunc.docForm.dateid], function (tx, res) {
                      cfunc.showMsgBox("Item saved", "positive");
                      $q.loading.hide();
                      sbc.modulefunc.loadInvStock();
                    }, function (tx, err) {
                      // cfunc.showMsgBox(err.message, "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
                      sbc.globalFunc.showErrMsg(err.message);
                      $q.loading.hide();
                      console.log("err1: ", err);
                    });
                  }
                });
              });
            }, function (err) {
              // cfunc.showMsgBox(err.message, "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
              sbc.globalFunc.showErrMsg(err.message);
              $q.loading.hide();
              console.log("err2: ", err);
            });
          } else {
            // cfunc.showMsgBox("Please save transcation first.", "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
            sbc.globalFunc.showErrMsg("Please save transaction first");
            $q.loading.hide();
          }
        });
      },
      selectStock: function (row, index) {
        if (sbc.modulefunc.docForm.docstat === 0) {
          sbc.modulefunc.inputLookupForm = { barcode: row.barcode, itemname: row.itemname, qty: row.qty, item: row };
          console.log("=================", sbc.inputlookupbuttons);
          sbc.selinputlookupfields = sbc.globalFunc.getLookupForm(sbc.inputlookupfields, "stockLookupFields", "inputFields");
          sbc.selinputlookupfieldsplot = sbc.globalFunc.getLookupForm(sbc.inputlookupfieldsplot, "stockLookupFields", "inputPlot");
          sbc.selinputlookupbuttons = sbc.globalFunc.getLookupForm(sbc.inputlookupbuttons, "stockLookupButtons", "buttons");
          sbc.isFormEdit = true;
          sbc.inputLookupTitle = "Edit Qty";
          sbc.showInputLookup = true;
        }
      },
      saveStockQty: function () {
        // sbc.globalFunc.showErrMsg("saveStockQty called: " + sbc.modulefunc.inputLookupForm.qty);
        let qty = sbc.modulefunc.inputLookupForm.qty;
        if (sbc.modulefunc.inputLookupForm.qty === null || sbc.modulefunc.inputLookupForm.qty === "") {
          // cfunc.showMsgBox("Please input a valid quantity", "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
          sbc.globalFunc.showErrMsg("Please input a valid quantity");
          return;
        }
        if (isNaN(qty)) {
          sbc.globalFunc.showErrMsg("Invalid quantity, Please try again.");
          return;
        }
        if (parseInt(qty) === 0) {
          $q.dialog({
            message: "Do you want to delete this item?",
            ok: { flat: true, color: "primary" },
            cancel: { flat: true, color: "negative" }
          }).onOk(() => {
            cfunc.showLoading();
            sbc.db.transaction(function (tx) {
              tx.executeSql("delete from stock where trno=? and line=?", [sbc.modulefunc.cLookupForm.trno, sbc.modulefunc.inputLookupForm.item.line], function (tx, res) {
                cfunc.showMsgBox("Item deleted", "positive");
                const index = sbc.modulefunc.lookupTableData.indexOf(sbc.modulefunc.inputLookupForm.item);
                if (index !== undefined) sbc.modulefunc.lookupTableData.splice(index, 1);
                sbc.showInputLookup = false;
                $q.loading.hide();
              }, function (tx, err) {
                sbc.globalFunc.showErrMsg(err.message);
                $q.loading.hide();
              });
            }, function (err) {
              sbc.globalFunc.showErrMsg(err.message);
              $q.loading.hide();
            });
          });
        } else {
          cfunc.showLoading();
          sbc.db.transaction(function (tx) {
            tx.executeSql("update stock set qty=?, variance=syscount-? where trno=? and line=?", [sbc.modulefunc.inputLookupForm.qty, sbc.modulefunc.inputLookupForm.qty, sbc.modulefunc.docForm.trno, sbc.modulefunc.inputLookupForm.item.line], function (tx, res) {
              cfunc.showMsgBox("Stock updated", "positive");
              sbc.modulefunc.loadInvStock();
              sbc.showInputLookup = false;
              $q.loading.hide();
            }, function (tx, err) {
              console.log("err1: ", err.message);
              sbc.globalFunc.showErrMsg(err.message);
              $q.loading.hide();
            });
          }, function (err) {
            console.log("err2: ", err.message);
            sbc.globalFunc.showErrMsg(err.message);
            $q.loading.hide();
          });
        }
      },
      cancelStockQty: function () {
        sbc.showInputLookup = false;
      },
      selectWhs: function (row) {
        console.log("selectWhs called: ", row);
        switch (sbc.modulefunc.whlookupType) {
          case "generate":
            if (row.uploaded === 1 || row.uploaded === true) {
              sbc.globalFunc.showErrMsg("Data for (" + row.client + ") is already uploaded");
            } else {
              sbc.showLookup = false;
              sbc.modulefunc.cLookupForm = { generatetype: "all", wh: row.client, generated: row.generated, filename: row.filename };
              sbc.modulefunc.generateReport();
            }
            break;
          case "upload":
            if (row.generated === null || row.generated === undefined || row.generated === "" || row.generated === 0) {
              // cfunc.showMsgBox("Please generate report first before uploading", "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
              sbc.globalFunc.showErrMsg("Please generate report first before uploading");
            } else {
              cfunc.showLoading();
              sbc.db.transaction(function (tx) {
                tx.executeSql("select uploaded from hhead where wh=?", [row.client], function (tx, res1) {
                  if (res1.rows.length > 0) {
                    let uploaded = false;
                    for (var x = 0; x < res1.rows.length; x++) {
                      if (res1.rows.item(x).uploaded !== null && res1.rows.item(x).uploaded !== undefined && res1.rows.item(x).uploaded !== false && res1.rows.item(x).uploaded !== 0) {
                        uploaded = true;
                      }
                      if (parseInt(x) + 1 === res1.rows.length) {
                        if (uploaded) {
                          // sbc.globalFunc.showErrMsg("Data for (" + row.client + ") is already uploaded");
                          $q.loading.hide();
                          $q.dialog({
                            message: "Data for (" + row.client + ") is already uploaded, Do you want to re-upload?",
                            ok: { flat: true, color: "primary" },
                            cancel: { flat: true, color: "negative" }
                          }).onOk(() => {
                            cfunc.showLoading();
                            sbc.modulefunc.reupload = true;
                            sbc.db.transaction(function (tx) {
                              tx.executeSql("select trno from head where wh=?", [row.client], function (tx, res) {
                                if (res.rows.length > 0) {
                                  sbc.globalFunc.showErrMsg("Cannot upload (" + row.client + "), all transactions must be posted");
                                } else {
                                  sbc.modulefunc.loadUpItems(row);
                                }
                              }, function (tx, err) {
                                sbc.globalFunc.showErrMsg(err.message);
                                $q.loading.hide();
                              });
                            }, function (err) {
                              sbc.globalFunc.showErrMsg(err.message);
                              $q.loading.hide();
                            });
                          });
                        } else {
                          tx.executeSql("select trno from head where wh=?", [row.client], function (tx, res) {
                            if (res.rows.length > 0) {
                              // cfunc.showMsgBox("Cannot upload (" + row.client + "), all transactions must be posted.", "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
                              sbc.globalFunc.showErrMsg("Cannot upload (" + row.client + "), all transactions must be posted");
                              $q.loading.hide();
                            } else {
                              $q.loading.hide();
                              $q.dialog({
                                message: "Are you sure you want to upload (" + row.client + ")?",
                                ok: { flat: true, color: "primary", label: "Yes" },
                                cancel: { flat: true, color: "negative", label: "No" }
                              }).onOk(() => {
                                sbc.modulefunc.loadUpItems(row);
                              });
                            }
                          });
                        }
                      }
                    }
                  } else {
                    // cfunc.showMsgBox("Nothing to upload", "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
                    sbc.globalFunc.showErrMsg("Nothing to upload");
                    $q.loading.hide();
                  }
                });
              });
            }
            break;
        }
      },
      loadUpItems: function (data) {
        cfunc.showLoading();
        cfunc.getMultiData("hhead", ["trno", "brand"], [{ field: "wh", value: data.client }]).then(head => {
          let brands = [];
          let trnos = [];
          head.map(waw => brands.push(waw.brand));
          head.map(waw => trnos.push(waw.trno));
          // brands = brands.map(waw => `"${waw}"`).join(",");
          let brands2 = [];
          brands.map(waw => {
            waw.split(",").map(wew => brands2.push(wew));
          });
          brands2 = brands2.map(waw => `"${waw}"`);
          brands2 = brands2.join(",");
          let items = [];
          sbc.db.transaction(function (tx) {
            tx.executeSql("select item.itemid, item.barcode, item.itemname, round(ifnull(cast(ib.bal as float), 0), 2) as syscount, ifnull(ci.sku, ?) as sku, ? as wh, item.brand,\
              ifnull((select sum(qty) as qty from (select qty from hstock where hstock.trno in (" + trnos.join(",") + ") and hstock.barcode=item.barcode) as t), 0) as qty,\
              ifnull(sitems.soldqty, 0) as sales, 0 as variance\
              from item\
              left join clientitem as ci on ci.barcode=item.barcode\
              left join itembal as ib on cast(ib.itemid as integer)=item.itemid\
              left join soldqtyitems as sitems on sitems.barcode=item.barcode\
              where item.brand in (" + brands2 + ") order by qty desc", ["", data.client], function (tx, res) {
              if (res.rows.length > 0) {
                let itemline = 1;
                for (var x = 0; x < res.rows.length; x++) {
                  if (res.rows.item(x).syscount !== 0 || res.rows.item(x).qty !== 0) {
                    items.push({
                      itemid: res.rows.item(x).itemid,
                      barcode: res.rows.item(x).barcode,
                      itemname: res.rows.item(x).itemname,
                      syscount: res.rows.item(x).syscount,
                      sku: res.rows.item(x).sku,
                      wh: res.rows.item(x).wh,
                      brand: res.rows.item(x).brand,
                      qty: res.rows.item(x).qty,
                      sales: res.rows.item(x).sales,
                      variance: res.rows.item(x).variance,
                      line: itemline
                    });
                    itemline++;
                  }
                  if (parseInt(x) + 1 === res.rows.length) {
                    sbc.modulefunc.contUpload(items, data);
                  }
                }
              }
            }, function (tx, err) {
              sbc.globalFunc.showErrMsg(err.message);
              $q.loading.hide();
            });
            // tx.executeSql("select item.barcode, item.itemid, item.itemname, ifnull(cast(ib.bal as integer), 0) as syscount, ci.sku, item.brand, ? as wh,\
            //   sum(stock.qty) as qty, sum(stock.variance) as variance, stock.dateid, ifnull(sitems.soldqty, 0) as sales\
            //   from item\
            //   left join hstock as stock on stock.barcode=item.barcode\
            //   left join hhead as head on head.trno=stock.barcode\
            //   left join clientitem as ci on ci.barcode=item.barcode\
            //   left join itembal as ib on cast(ib.itemid as integer)=item.itemid\
            //   left join soldqtyitems as sitems on sitems.barcode=item.barcode\
            //   where item.brand in (" + brands2 + ") and (qty > 0 or syscount > 0)\
            //   group by item.barcode, item.itemname, syscount, ci.sku, item.brand, ci.wh, sales, stock.dateid", [data.client], function (tx, res) {
            //     let ss = [];
            //     if (res.rows.length > 0) {
            //       for (var x = 0; x < res.rows.length; x++) {
            //         ss.push(res.rows.item(x));
            //         ss[x].line = parseInt(x) + 1;
            //         if (parseInt(x) + 1 === res.rows.length) {
            //           console.log("-----------------", ss);
            //           // sbc.modulefunc.contUpload(ss, data);
            //           $q.loading.hide();
            //         }
            //       }
            //     } else {
            //       // cfunc.showMsgBox("No items to upload", "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
            //       sbc.globalFunc.showErrMsg("No items to upload");
            //       $q.loading.hide();
            //     }
            //   }, function (tx, err) {
            //     console.log("error sample", err.message);
            //     $q.loading.hide();
            //   });

            // tx.executeSql("select item.barcode, item.itemname, ifnull(ib.bal, 0) as syscount, ci.sku, item.brand, ci.wh,\
            //   ifnull((select sum(qty) as qty from (select qty from hstock where hstock.trno in (" + trnos.join(",") + ") and hstock.barcode=item.barcode) as t), 0) as qty, 0 as sales,\
            //   ifnull((select sum(variance) as variance from (select variance from hstock where hstock.trno in (" + trnos.join(",") + ") and hstock.barcode=item.barcode) as t), 0) as variance\
            //   from item\
            //   left join clientitem as ci on ci.barcode=item.barcode\
            //   left join itembal as ib on ib.itemid=item.itemid\
            //   where item.brand in (" + brands + ") order by qty desc", [], function (tx, ires) {
            //     if (ires.rows.length) {
            //       for (var i = 0; i < ires.rows.length; i++) {
            //         items.push(ires.rows.item(i));
            //         if (parseInt(i) + 1 === ires.rows.length) {
            //           sbc.modulefunc.contUpload(items, data);
            //         }
            //       }
            //     } else {
            //       cfunc.showMsgBox("No items to upload", "negative", "warning");
            //       $q.loading.hide();
            //     }
            //   }, function (tx, err) {
            //     console.log("err2: ", err.message);
            //     $q.loading.hide();
            //   });
          }, function (err) {
            sbc.globalFunc.showErrMsg(err.message);
            $q.loading.hide();
          });
        });
      },
      contUpload (items, data) {
        console.log("contUpload called", items);
        cfunc.getTableData("config", ["serveraddr", "deviceid", "stationname"], true).then(configdata => {
          if (configdata.serveraddr === "" || configdata.serveraddr === null || configdata.serveraddr === undefined) {
            // cfunc.showMsgBox("Server Address not set", "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
            sbc.globalFunc.showErrMsg("Server Address not set");
            return;
          }
          cfunc.showLoading();
          const invPCDate = $q.localStorage.getItem("invPCDate");
          const params = { id: md5("uploadInventoryDoc"), data: items, wh: data.client, devid: configdata.stationname, dateid: invPCDate, reupload: sbc.modulefunc.reupload };
          if (invPCDate === null || invPCDate === "" || invPCDate === undefined) {
            // cfunc.showMsgBox("Physical Count Date not set.", "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
            sbc.globalFunc.showErrMsg("Physical Count Date not set.");
            $q.loading.hide();
          } else {
            api.post(configdata.serveraddr + "/sbcmobilev2/admin", params, { headers: sbc.reqheader }).then(res => {
              if (res.data.status) {
                sbc.db.transaction(function (tx) {
                  tx.executeSql("delete from soldqtyitems where wh=? and dateid=?", [data.client, invPCDate]);
                  tx.executeSql("update hhead set uploaded=1 where wh=?", [data.client], function (tx, res) {
                    console.log("hhead updated", res);
                  });
                  tx.executeSql("update wh set uploaded=1 where client=?", [data.client], function (tx, res) {
                    console.log("wh updated", res);
                  });
                  sbc.showLookup = false;
                  cfunc.showMsgBox("Items uploaded", "positive");
                });
              } else {
                // cfunc.showMsgBox("An error occurred; please try again.", "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
                sbc.globalFunc.showErrMsg("An error occurred; please try again." + res.data.msg);
              }
              $q.loading.hide();
            }).catch(err => {
              // cfunc.showMsgBox(err.message, "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
              sbc.globalFunc.showErrMsg(err.message);
              $q.loading.hide();
            });
          }
        });
      },
      selectGenType: function () {
        sbc.modulefunc.generateReport(true);
      },
      isConsUploaded: function () {
        return new Promise((resolve, reject) => {
          sbc.db.transaction(function (tx) {
            tx.executeSql("select * from mbssettings where name=?", ["UPLOAD"], function (tx, res) {
              if (res.rows.length > 0) {
                resolve(true)
              } else {
                resolve(false)
              }
            }, function (tx, err) {
              reject(err);
            });
            // tx.executeSql("select count(line) as ccount from consolidated where isuploaded=1", [], function (tx, res) {
            //   if (res.rows.item(0).ccount > 0) {
            //     resolve(true);
            //   } else {
            //     resolve(false);
            //   }
            // }, function (tx, err) {
            //   reject(err);
            // });
          }, function (err) {
            reject(err);
          });
        });
      },
      hasConsolidated: function () {
        return new Promise((resolve, reject) => {
          sbc.db.transaction(function (tx) {
            tx.executeSql("select line from consolidated where isuploaded=0 or isuploaded=null or isuploaded=?", [""], function (tx, res) {
              if (res.rows.length > 0) {
                resolve(true);
              } else {
                resolve(false);
              }
            }, function (tx, err) {
              reject(err);
            });
          }, function (err) {
            reject(err);
          });
        });
      },
      hasFConsolidated: function () {
        return new Promise((resolve, reject) => {
          sbc.db.transaction(function (tx) {
            tx.executeSql("select line from fconsolidated where isuploaded=0 or isuploaded=null or isuploaded=?", [""], function (tx, res) {
              if (res.rows.length > 0) {
                resolve(true);
              } else {
                resolve(false);
              }
            }, function (tx, err) {
              reject(err);
            });
          }, function (err) {
            reject(err);
          });
        });
      },
      generateReport: function (isreload = false) {
        cfunc.showLoading();
        const pcdate = $q.localStorage.getItem("invPCDate");
        if (sbc.globalFunc.company === "mbs" && sbc.modulefunc.gtype === "Final") {
          sbc.db.transaction(function (tx) {
            tx.executeSql("select * from mbssettings where name=?", ["FUPLOAD"], function (tx, fupload) {
              if (fupload.rows.length > 0 && !isreload) {
                $q.loading.hide();
                sbc.globalFunc.showErrMsg("Final physical count has already been uploaded, can no longer re-generate report");
              } else {
                sbc.modulefunc.hasFConsolidated().then(hc => {
                  if (!hc) {
                    $q.loading.hide();
                    sbc.globalFunc.showErrMsg("No data found, please download final count first.");
                  } else {
                    contFunc();
                  }
                });
              }
            });
          }, function (err) {
            console.log("-asd-asd-----", err);
            $q.loading.hide();
          });
        } else {
          sbc.db.transaction(function (tx) {
            tx.executeSql("select trno from head where wh=?", [sbc.modulefunc.cLookupForm.wh], function (tx, res) {
              if (res.rows.length > 0) {
                sbc.globalFunc.showErrMsg("Cannot generate (" + sbc.modulefunc.cLookupForm.wh + "), all transactions must be posted");
                $q.loading.hide();
              } else {
                if (sbc.globalFunc.company === "mbs") {
                  tx.executeSql("select * from mbssettings where name=?", ["UPLOAD"], function (tx, upload) {
                    if (upload.rows.length > 0) {
                      $q.loading.hide();
                      sbc.globalFunc.showErrMsg("Initial physical count has already been uploaded, can no longer re-generate report");
                    } else {
                      if (sbc.modulefunc.cLookupForm.generated !== 1) {
                        tx.executeSql("delete from consolidated");
                      }
                      contFunc();
                    }
                  });
                } else {
                  if (sbc.modulefunc.cLookupForm.generated !== 1) {
                    sbc.db.transaction(function (tx) {
                      tx.executeSql("delete from soldqtyitems where wh=?", [sbc.modulefunc.cLookupForm.wh]);
                    });
                  }
                  contFunc();
                }
              }
            }, function (tx, err) {
              console.log("err2: ", err.message);
              sbc.globalFunc.showErrMsg(err.message);
              $q.loading.hide();
            });
          }, function (err) {
            console.log("err3: ", err.message);
            sbc.globalFunc.showErrMsg(err.message);
            $q.loading.hide();
          });
        }

        function contFunc () {
          sbc.globalFunc.cLookupRowsPerPage = 20;
          sbc.selclookuptablecols = sbc.globalFunc.getLookupForm(sbc.clookuptablecols, "generateInvTableCols", "inputFields");
          sbc.selclookuptablecolsplot = sbc.globalFunc.getLookupForm(sbc.clookuptablecolsplot, "generateInvTableCols", "inputPlot");
          sbc.selclookupheadfields = sbc.globalFunc.getLookupForm(sbc.clookupheadfields, "generateInvHeadFields", "inputFields");
          sbc.selclookupheadfieldsplot = sbc.globalFunc.getLookupForm(sbc.clookupheadfieldsplot, "generateInvHeadFields", "inputPlot");
          sbc.selclookupbuttons = sbc.globalFunc.getLookupForm(sbc.clookupbuttons, "generateButtons", "buttons");
          sbc.selclookupfooterfields = sbc.globalFunc.getLookupForm(sbc.clookupfooterfields, "generateInvFooterFields", "inputFields");
          sbc.selclookupfooterfieldsplot = sbc.globalFunc.getLookupForm(sbc.clookupfooterfieldsplot, "generateInvFooterFields", "inputPlot");
          sbc.modulefunc.cLookupForm.itemcount = 0;
          sbc.modulefunc.cLookupTableFilter = { type: "searchItem", field: "txtSearchItem", label: "Search Items", func: "searchItem2", livesearch: true, debounce: "500" };
          sbc.globalFunc.cLookupTableClick = true;
          sbc.globalFunc.cLookupTableClickFunc = "selectGenItem";
          sbc.globalFunc.cLookupTableClickFunctype = "module";
          sbc.modulefunc.cLookupFooterButtonsFab = false;
          sbc.globalFunc.customLookupGrid = false;
          sbc.cLookupTitle = "Generate";
          sbc.isFormEdit = true;
          sbc.globalFunc.cLookupSelect = false;
          sbc.globalFunc.cLookupMaximized = true;
          sbc.modulefunc.lookupTableData = [];
          sbc.showCustomLookup = true;
          sbc.db.transaction(function (tx) {
            tx.executeSql("select trno, brand from hhead where wh=?", [sbc.modulefunc.cLookupForm.wh], function (tx, whres) {
              if (whres.rows.length > 0) {
                let brands = [];
                let trnos = [];
                for (var x = 0; x < whres.rows.length; x++) {
                  trnos.push(whres.rows.item(x).trno);
                  brands.push(whres.rows.item(x).brand);
                  if (parseInt(x) + 1 === whres.rows.length) {
                    sbc.globalFunc.cLookupTrnos = trnos;
                    sbc.globalFunc.cLookupBrands = brands;
                    loadItems(brands, trnos);
                  }
                }
              } else {
                sbc.globalFunc.showErrMsg("An error occurred; there were no items to generate.");
                $q.loading.hide();
              }
            });
          });
        }
        
        function loadItems (brands, trnos) {
          sbc.db.transaction(function (tx) {
            let items = [];
            let filter = "";
            let brands2 = [];
            brands.map(waw => {
              waw.split(",").map(wew => brands2.push(wew));
            });
            brands2 = brands2.map(waw => `"${waw}"`);
            brands2 = brands2.join(",");
            // switch (sbc.modulefunc.cLookupForm.generatetype) {
            //   case "1":
            //     filter = " and variance <> 0 ";
            //     break;
            //   case "2":
            //     filter = " and variance = 0 ";
            //     break;
            // }
            // (ifnull((select sum(variance) as variance from (select variance from hstock where hstock.trno in (" + trnos.join(",") + ") and hstock.barcode=item.barcode) as t), 0) + ifnull(sitems.soldqty, 0)) as variance\
            let qry = "select ? as wh, ? as dateid, item.barcode, item.partno, item.itemname, round(ifnull(cast(ib.bal as float), 0), 2) as syscount, ifnull(ci.sku, ?) as sku, item.brand,\
              ifnull((select sum(qty) as qty from (select qty from hstock where hstock.trno in (" + trnos.join(",") + ") and hstock.barcode=item.barcode) as t), 0) as qty,\
              ifnull(sitems.soldqty, 0) as sales, 0 as variance, item.amt\
              from item\
              left join clientitem as ci on ci.barcode=item.barcode\
              left join itembal as ib on cast(ib.itemid as integer)=item.itemid\
              left join soldqtyitems as sitems on sitems.barcode=item.barcode\
              where item.brand in (" + brands2 + ") " + filter + " order by qty desc";
            if (sbc.globalFunc.company === "mbs") {
              if (sbc.modulefunc.gtype === "Initial") {
                qry = "select ? as wh, ? as dateid, item.itemid, item.barcode, item.partno, item.itemname, round(ifnull(cast(item.bal as float), 0), 2) as syscount, ifnull(ci.sku, ?) as sku, item.brand,\
                  ifnull((select sum(qty) as qty from (select qty from hstock where hstock.trno in (" + trnos.join(",") + ") and hstock.barcode=item.barcode) as t), 0) as qty,\
                  0 as sales, 0 as variance, item.amt\
                  from item\
                  left join clientitem as ci on ci.barcode=item.barcode\
                  order by qty desc";
              } else {
                qry = "select ? as wh, ? as dateid, item.itemid, item.barcode, item.partno, item.itemname, round(ifnull(cast(item.bal as float), 0), 2) as syscount, ifnull(ci.sku, ?) as sku, item.brand,\
                  ifnull(f.qty, 0) as qty, 0 as sales, 0 as variance, item.amt\
                  from item\
                  left join fconsolidated as f on f.barcode=item.barcode\
                  left join clientitem as ci on ci.barcode=item.barcode\
                  order by qty desc";
              }
            }
            tx.executeSql(qry, [sbc.modulefunc.cLookupForm.wh, pcdate, ""], function (tx, ires) {
                if (ires.rows.length) {
                  let variance = 0;
                  // sbc.modulefunc.cLookupForm.itemcount = ires.rows.length;
                  for (var i = 0; i < ires.rows.length; i++) {
                    variance = parseInt(ires.rows.item(i).syscount) - (parseInt(ires.rows.item(i).qty) + parseInt(ires.rows.item(i).sales));
                    switch (sbc.modulefunc.cLookupForm.generatetype) {
                      case "1":
                        if (variance !== 0) {
                          items.push({
                            itemid: ires.rows.item(i).itemid,
                            wh: ires.rows.item(i).wh,
                            dateid: ires.rows.item(i).dateid,
                            barcode: ires.rows.item(i).barcode,
                            itemname: ires.rows.item(i).itemname,
                            syscount: ires.rows.item(i).syscount,
                            sku: ires.rows.item(i).sku,
                            brand: ires.rows.item(i).brand,
                            qty: ires.rows.item(i).qty,
                            variance: variance
                          });
                          sbc.modulefunc.cLookupForm.itemcount += 1;
                        }
                        break;
                      case "2":
                        if (variance === 0) {
                          items.push(ires.rows.item(i));
                          sbc.modulefunc.cLookupForm.itemcount += 1;
                        }
                        break;
                      default:
                        items.push(ires.rows.item(i));
                        items[i].variance = variance;
                        sbc.modulefunc.cLookupForm.itemcount += 1;
                        break;
                    }
                    if (parseInt(i) + 1 === ires.rows.length) {
                      sbc.modulefunc.lookupTableData = items;
                      $q.loading.hide();
                    }
                  }
                } else {
                  $q.loading.hide();
                }
              }, function (tx, err) {
                console.log("err2: ", err.message);
                $q.loading.hide();
              });
          }, function (err) {
            console.log("err1: ", err.message);
            $q.loading.hide();
          });
        }
      },
      generateDocument: function () {
        const generated = sbc.modulefunc.cLookupForm.generated;
        if (sbc.globalFunc.company === "mbs" && sbc.modulefunc.gtype === "Final") {
          sbc.db.transaction(function (tx) {
            tx.executeSql("select * from mbssettings where name=?", ["FGENERATED"], function (tx, fgen) {
              if (fgen.rows.length > 0) {
                contGen(false);
              } else {
                contGen(true);
              }
            });
          });
        } else {
          if (generated === null || generated === undefined || generated === "" || generated === false || generated === 0) {
            contGen(true);
          } else {
            contGen(false);
          }
        }
        function contGen (isnew) {
          sbc.modulefunc.newreport = isnew;
          let msg = isnew ? "Do you want to generate report?" : "Report already generated, Do you want to generate again?";
          $q.dialog({
            message: msg,
            ok: { flat: true, color: "primary" },
            cancel: { flat: true, color: "negative" }
          }).onOk(() => {
            if (sbc.globalFunc.company === "mbs" && sbc.modulefunc.gtype === "Initial") {
              sbc.modulefunc.consolidateItems();
            } else {
              sbc.modulefunc.signatureType = "first";
              sbc.globalFunc.signaturePadTitle = "";
              showSigPad.value = true;
            }
          });
        }
      },
      consolidateItems: function () {
        try {
          console.log("consolidateItems called");
          let cons = [];
          cfunc.showLoading("Consolidating data, Please wait...");
          for (var x in sbc.modulefunc.lookupTableData) {
            if (sbc.modulefunc.lookupTableData[x].qty > 0) {
              cons.push(sbc.modulefunc.lookupTableData[x]);
            }
            if (parseInt(x) + 1 === sbc.modulefunc.lookupTableData.length) {
              if (cons.length > 0) {
                sbc.db.transaction(function (tx) {
                  tx.executeSql("delete from consolidated");
                  sbc.modulefunc.saveCons(cons);
                }, function (err) {
                  console.log("errorsssss", err);
                });
              } else {
                sbc.globalFunc.showErrMsg("No data found.");
                $q.loading.hide();
              }
            }
          }
        } catch (e) {
          console.log("----------------consolidateitems error: ", e);
          $q.loading.hide();
        }
      },
      saveCons: function (data) {
        try {
          console.log("savecons called");
          let d = data;
          let dd = [];
          while (d.length) dd.push(d.splice(0, 100));
          save(dd);

          function save (cons, index = 0) {
            if (index === cons.length) {
              $q.loading.hide();
              console.log("----------------------consolidated rec saved");
              sbc.modulefunc.getConsolidated();
              sbc.modulefunc.signatureType = "first";
              sbc.globalFunc.signaturePadTitle = "";
              showSigPad.value = true;
            } else {
              sbc.db.transaction(function (tx) {
                for (var a in cons[index]) {
                  insertCons(cons[index][a]);
                  if (parseInt(a) + 1 === cons[index].length) save(cons, parseInt(index) + 1);
                }
              });
            }
          }

          function insertCons (consdata) {
            sbc.db.transaction(function (tx) {
              tx.executeSql("insert into consolidated(barcode, itemid, qty, isuploaded) values(?, ?, ?, ?)", [consdata.barcode, consdata.itemid, consdata.qty, 0]);
            });
          }
        } catch (e) {
          console.log("--------------saveCons error: ", e);
          $q.loading.hide();
        }
      },
      getConsolidated: function () {
        sbc.db.transaction(function (tx) {
          tx.executeSql("select * from consolidated", [], function (tx, res) {
            let cons = [];
            for (var x = 0; x < res.rows.length; x++) {
              cons.push(res.rows.item(0));
              if (parseInt(x) + 1 === res.rows.length) {
                console.log("consolidated recs: ", cons);
              }
            }
          });
        });
      },
      signatureDone: function (data) {
        console.log("signatureDone called");
        $q.dialog({
          message: "Do you want to proceed?",
          ok: { flat: true, color: "primary" },
          cancel: { flat: true, color: "negative" }
        }).onOk(() => {
          showSigPad.value = false;
          let signeeFields = "";
          switch (sbc.modulefunc.signatureType) {
            case "first":
              sbc.modulefunc.inputLookupForm = { signee: "", signee2: "", signee3: "", signature1: data, signature2: "", signature3: "", printdate: "", pcdate: "" };
              signeeFields = "signeeLookupFields";
              break;
            case "second":
              sbc.modulefunc.inputLookupForm.signature2 = data;
              signeeFields = "signee2LookupFields";
              break;
            case "third":
              sbc.modulefunc.inputLookupForm.signature3 = data;
              signeeFields = "signee3LookupFields";
              break;
          }
          sbc.selinputlookupfields = sbc.globalFunc.getLookupForm(sbc.inputlookupfields, signeeFields, "inputFields");
          sbc.selinputlookupfieldsplot = sbc.globalFunc.getLookupForm(sbc.inputlookupfieldsplot, signeeFields, "inputPlot");
          sbc.selinputlookupbuttons = sbc.globalFunc.getLookupForm(sbc.inputlookupbuttons, "signeeLookupButtons", "buttons");
          sbc.isFormEdit = true;
          sbc.inputLookupTitle = "";
          sbc.showInputLookup = true;
        });
      },
      saveSignee: function () {
        console.log("saveSignee called", sbc.modulefunc.signatureType);
        switch (sbc.modulefunc.signatureType) {
          case "first":
            sbc.showInputLookup = false;
            sbc.modulefunc.signatureType = "second";
            showSigPad.value = true;
            break;
          case "second":
            $q.dialog({
              message: "Do you want to add another signee?",
              ok: { flat: true, color: "primary" },
              cancel: { flat: true, color: "negative" }
            }).onOk(() => {
              sbc.modulefunc.signatureType = "third";
              sbc.showInputLookup = false;
              showSigPad.value = true;
            }).onCancel(() => {
              sbc.showInputLookup = false;
              cfunc.showLoading("Generating PDF File");
              sbc.modulefunc.contSavePDF();
            });
            break;
          case "third":
            sbc.showInputLookup = false;
            cfunc.showLoading("Generating PDF File");
            sbc.modulefunc.contSavePDF();
            break;
        }
      },
      cancelSignee: function () {
        sbc.showInputLookup = false;
      },
      generateHtml: function (tableData) {
        let title = "PHYSICAL COUNT";
        let headers = `<tr>\
          <td colspan=2>Warehouse: ` + sbc.modulefunc.cLookupForm.wh + `</td>\
          <td>Print Date: ` + sbc.modulefunc.inputLookupForm.printdate + `</td>\
        </tr>\
        <tr>\
          <td colspan=2></td>\
          <td>PC Date: ` + sbc.modulefunc.inputLookupForm.pcdate + `</td>\
        </tr>`;
        if (sbc.globalFunc.company === "mbs") {
          if (sbc.modulefunc.gtype === "Initial") {
            title = "INITIAL PHYSICAL COUNT";
          } else {
            title = "FINAL PHYSICAL COUNT";
          }
          headers = `<tr>\
            <td colspan=2>Branch: ` + sbc.modulefunc.cLookupForm.branch + `</td>\
            <td>Print Date: ` + sbc.modulefunc.inputLookupForm.printdate + `</td>\
          </tr>\
          <tr>\
            <td colspan=2>Warehouse: ` + sbc.modulefunc.cLookupForm.wh + `</td>\
            <td>PC Date: ` + sbc.modulefunc.inputLookupForm.pcdate + `</td>\
          </tr>`;
        }
        let htmldata = `<html>\
          <h1 style="width:100%;"><center>` + title + `</center></h1><br>\
          <table style="width:100%;">\
            ` + headers + `
            <tr>\
              <td colspan=8><hr /></td>\
            </tr>\
            <tr>\
              <th>Item Code</th>\
              <th>Cust. SKU</th>\
              <th>Item Name</th>\
              <th>Brand</th>\
              <th>Sys. Count</th>\
              <th>Actual</th>\
              <th>Sales</th>\
              <th>Variance</th>\
            </tr>\
            <tr>\
              <td colspan=8><hr /></td>\
            </tr>`;
        tableData.map(waw => {
          htmldata += `<tr>\
              <td colspan=2>` + waw.barcode + `</td>\
              <td colspan=6>` + waw.itemname + `</td>\
            </tr>`;
          htmldata += `<tr>\
            <td></td>\
            <td>` + waw.sku + `</td>\
            <td></td>\
            <td>` + waw.brand + `</td>\
            <td style="text-align:right;">` + waw.syscount + `</td>\
            <td style="text-align:right;">` + waw.qty + `</td>\
            <td style="text-align:right;">` + waw.sales + `</td>\
            <td style="text-align:right;">` + waw.variance + `</td>\
          </tr>`;
        });
        htmldata += `<tr>\
            <td colspan=8><hr /></td>\
          </tr>\
        </table>`;
        htmldata += `<table style="width:100%;">\
          <tr>\
            <td><hr /></td>\
            <td><hr /></td>`;
            if (sbc.modulefunc.inputLookupForm.signee3 !== "") htmldata += `<td><hr /></td>`;
          htmldata += `</tr>\
          <tr>\
            <td>\
              <center>\
                <img width=100 height=100 style="position:absolute;margin-top:-40px;" src="` + sbc.modulefunc.inputLookupForm.signature1 + `">\
                ` + sbc.modulefunc.inputLookupForm.signee + `\
              </center>\
            </td>\
            <td>\
              <center>
                <img width=100 height=100 style="position:absolute;margin-top:-40px;" src="` + sbc.modulefunc.inputLookupForm.signature2 + `">\
                ` + sbc.modulefunc.inputLookupForm.signee2 + `\
              </center>\
            </td>`;
            if (sbc.modulefunc.inputLookupForm.signee3 !== "") {
              htmldata += `<td>\
                <center>\
                  <img width=100 height=100 style="position:absolute;margin-top:-40px;" src="` + sbc.modulefunc.inputLookupForm.signature3 + `">\
                  ` + sbc.modulefunc.inputLookupForm.signee3 + `\
                </center>\
              </td>`;
            }
        htmldata += "</tr>\
        </table>";
        return htmldata;
      },
      contSavePDF: function () {
        // if (sbc.globalFunc.company === "mbs") {
        //   console.log("------------", sbc.modulefunc.lookupTableData);
        //   return;
        // }
        let options = { documentSize: "a4", type: "base64" };
        let pcdate = "";
        let encodedUri;
        let filename;
        let filePath;
        if ($q.localStorage.has("invPCDate")) pcdate = $q.localStorage.getItem("invPCDate");
        sbc.modulefunc.inputLookupForm.pcdate = pcdate;
        sbc.modulefunc.inputLookupForm.printdate = cfunc.getDateTime("date");
        const tableData = sbc.modulefunc.lookupTableData;
        let pdfData = [];
        tableData.map(waw => {
          if (waw.qty > 0 || waw.syscount > 0) pdfData.push(waw);
        });
        const htmldata = sbc.modulefunc.generateHtml(pdfData);
        cordova.plugins.pdf.htmlToPDF({
          data: htmldata,
          documentSize: "A4",
          landscape: "portrait",
          type: "base64"
        },
        function (data) {
          console.log("pdf generated data: ", data);
          const blob = b64toBlob(data, "", "");
          const b64 = "data:application/pdf;base64," + data;
          contSave();
          function contSave () {
            filename = "PC-" + sbc.modulefunc.cLookupForm.wh + "-" + sbc.modulefunc.inputLookupForm.pcdate;
            if (sbc.globalFunc.company === "mbs" && sbc.modulefunc.gtype === "Final") {
              filename = "FINAL PC-" + sbc.modulefunc.cLookupForm.wh + "-" + sbc.modulefunc.inputLookupForm.pcdate;
            }
            filePath = cordova.file.externalDataDirectory + "Download/pdfs/" + filename + ".pdf";
            const fileTransfer = new window.FileTransfer();

            window.resolveLocalFileSystemURL(filePath, function(dir) {
             dir.getFile(filename, {create:false}, function(fileEntry) {
               fileEntry.remove(function () {
                  console.log("Document deleted");
                },function (error) {
                  // cfunc.showMsgBox("Error deleting existing document, Please try again.", "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
                  sbc.globalFunc.showErrMsg("Error deleting existing document, Please try again.");
                  return;
                });
              });
            });

            fileTransfer.download(b64, filePath, (entry) => {
              console.log("Document successfully saved, full path: ", entry.fullPath,);
              sbc.db.transaction(function (tx) {
                tx.executeSql("update wh set generated=1, filename=? where client=?", [filename, sbc.modulefunc.cLookupForm.wh], function (tx, res) {
                  $q.loading.hide();
                  sbc.modulefunc.cLookupForm.generated = 1;
                  sbc.modulefunc.cLookupForm.filename = filename;
                  if ($q.localStorage.has("sbcInvAppPDFDoc")) $q.localStorage.removeItem("sbcInvAppPDFDoc");
                  $q.localStorage.set("sbcInvAppPDFDoc", filename + ".pdf");
                  cfunc.showMsgBox("Document successfully saved, full path: " + entry.fullPath, "positive");
                  generateSaveExcel(pdfData);
                });
              });
            }, (error) => {
              console.log("error saving document error: ", error);
              $q.loading.hide();
              sbc.globalFunc.showErrMsg("Error saving document err: ", error);
            });
          }
        },
        function (err) {
          console.log("error generating pdf err: ", err);
          $q.loading.hide();
          sbc.globalFunc.showErrMsg("Erorr generating PDF err: ", err);
        });

        function b64toBlob(b64Data, contentType, sliceSize) {
          contentType = contentType || "";
          sliceSize = sliceSize || 512;
          var byteCharacters = atob(b64Data);
          var byteArrays = [];
          for (var offset = 0; offset < byteCharacters.length; offset += sliceSize) {
            var slice = byteCharacters.slice(offset, offset + sliceSize);
            var byteNumbers = new Array(slice.length);
            for (var i = 0; i < slice.length; i++) {
              byteNumbers[i] = slice.charCodeAt(i);
            }
            var byteArray = new Uint8Array(byteNumbers);
            byteArrays.push(byteArray);
          }
          var blob = new Blob(byteArrays, {type: contentType});
          return blob;
        }

        function generateSaveExcel (data) {
          cfunc.showLoading("Generating Excel File");
          let datas = [];
          data.map((waw, i, rows) => {
            sbc.db.transaction(function (tx) {
              tx.executeSql("select head.loc from hhead as head left join hstock as stock on stock.trno=head.trno where stock.barcode=?", [waw.barcode], function (tx, res) {
                let loc1 = [];
                if (res.rows.length > 0) {
                  for (var x = 0; x < res.rows.length; x++) {
                    loc1.push(res.rows.item(x).loc);
                    if (parseInt(x) + 1 === res.rows.length) contFunc(loc1.join(","));
                  }
                } else {
                  contFunc("");
                }
              }, function (tx, err) {
                contFunc("");
              });
            }, function (err) {
              contFunc("");
            });

            function contFunc (locs) {
              datas.push({
                "Item Code": waw.barcode,
                "Barcode": waw.partno,
                "Customer SKU": waw.sku,
                "Item Description": waw.itemname,
                "Brand": waw.brand,
                "System Count": waw.syscount,
                "Actual Count": waw.qty,
                "Sales Count": waw.sales,
                "Variance": waw.variance,
                "SRP": waw.amt,
                "Locations": locs
              });
              if (i === data.length - 1) {
                contFunc2();
                console.log("---------contFunc2 called--", i, "--", data.length, "--------", datas.length);
              }
              // if ((i + 1) === data.length) {
              //   contFunc2();
              //   console.log("-------contFunc2 called----", i, "--", data.length);
              // }
            }
          });

          function contFunc2 () {
            let fields = Object.keys(datas[0]);
            let replacer = function (key, value) { return value === null ? "" : value }
            let csv = datas.map(function (row) {
              return fields.map(function (fieldName) {
                return JSON.stringify(row[fieldName], replacer);
              }).join(",");
            });
            csv.unshift(fields.join(","));
            csv = csv.join("\r\n");
            csv = "data:text/csv;charset=utf-8," + csv;
            encodedUri = encodeURI(csv);
            let errorCallback = function () {
              console.log("............... permission error");
              $q.loading.hide();
            }
            contSaveExcelFile();
          }
        }

        function contSaveExcelFile () {
          // const filePath = cordova.file.externalRootDirectory + "FAMS/" + data + ".csv";
          // const filePath = cordova.file.externalDataDirectory + "Download/excels/" + filename + ".csv";
          const filePath = cordova.file.externalDataDirectory + "Download/excels";
          const fileTransfer = new window.FileTransfer();

          fileTransfer.download(encodedUri, filePath + "/" + filename + ".csv",
            function (entry) {
              console.log("--------waw------", encodedUri);
              cfunc.showMsgBox("Successfully saved excel file, full path is " + entry.fullPath, "positive");
              $q.loading.hide();
              console.log("newreport: ", sbc.modulefunc.newreport);
              sbc.db.transaction(function (tx) {
                tx.executeSql("delete from soldqtyitems where wh=?", [sbc.modulefunc.cLookupForm.wh], function (tx, res) {
                  sbc.modulefunc.generateReport(true);
                });
                if (sbc.globalFunc.company === "mbs" && sbc.modulefunc.gtype === "Final") {
                  let datenow = cfunc.getDateTime("datetime");
                  tx.executeSql("insert into mbssettings values(?, ?)", ["FGENERATED", datenow]);
                } else {
                  tx.executeSql("update wh set generated=true where client=?", [sbc.modulefunc.cLookupForm.wh]);
                }
              }, function (err) {
                console.log("error generating excel file #1: ", err.message);
              });
            },
            function (error) {
              cfunc.showMsgBox("Error saving excel file", "negative", "warning");
              $q.loading.hide()
            },
            false
          );
        }
      },
      viewDocument: function () {
        console.log("viewDocument called", sbc.modulefunc.cLookupForm);
        if (sbc.modulefunc.cLookupForm.filename === "" || sbc.modulefunc.cLookupForm.filename === null || sbc.modulefunc.cLookupForm.filename === undefined) {
          sbc.globalFunc.showErrMsg("Please generate report first.");
        } else {
          $q.dialog({
            message: "Do you want to view document?",
            ok: { flat: true, color: "primary" },
            cancel: { flat: true, color: "negative" }
          }).onOk(() => {
            if ($q.localStorage.has("sbcInvAppPDFDoc")) {
              // const filename = $q.localStorage.getItem("sbcInvAppPDFDoc");
              const filename = sbc.modulefunc.cLookupForm.filename;
              const filePath = cordova.file.externalDataDirectory + "Download/pdfs/" + filename + ".pdf";
              cordova.plugins.fileOpener2.showOpenWithDialog(
                filePath,
                "application/pdf",
                {
                  error : function(e) {
                    console.log("Error status: " + e.status + " - Error message: " + e.message);
                    sbc.globalFunc.showErrMsg("Error status: " + e.status + " - Error message: " + e.message);
                  },
                  success : function () {
                    console.log("file opened successfully");
                  }
                }
              );
            } else {
              sbc.globalFunc.showErrMsg("Document not found.");
            }
          })
        }
      },
      sendDocument: function () {
        console.log("sendDocument called");
        if (sbc.modulefunc.cLookupForm.filename === "" || sbc.modulefunc.cLookupForm.filename === null || sbc.modulefunc.cLookupForm.filename === undefined) {
          sbc.globalFunc.showErrMsg("Please generate report first.");
        } else {
          sbc.modulefunc.inputLookupForm = { email: "" };
          sbc.selinputlookupfields = sbc.globalFunc.getLookupForm(sbc.inputlookupfields, "emailLookupFields", "inputFields");
          sbc.selinputlookupfieldsplot = sbc.globalFunc.getLookupForm(sbc.inputlookupfieldsplot, "emailLookupFields", "inputPlot");
          sbc.selinputlookupbuttons = sbc.globalFunc.getLookupForm(sbc.inputlookupbuttons, "emailLookupButtons", "buttons");
          sbc.isFormEdit = true;
          sbc.inputLookupTitle = "Send Email";
          sbc.showInputLookup = true;
        }
      },
      sendEmail: function () {
        console.log("sendEmail called");
        if (sbc.modulefunc.inputLookupForm.email !== "") {
          let files = [];
          const filename = sbc.modulefunc.cLookupForm.filename;
          const filePath = cordova.file.externalDataDirectory + "Download/pdfs/" + filename;
          files.push(cordova.file.externalDataDirectory + "Download/pdfs/" + filename + ".pdf");
          files.push(cordova.file.externalDataDirectory + "Download/excels/" + filename + ".csv");
          console.log("..........", files);
          cordova.plugins.email.open({
            to: sbc.modulefunc.inputLookupForm.email,
            subject: "Greetings",
            body: "Please see attached file for physical count",
            attachments: files
          });
          sbc.showInputLookup = false;
          // if ($q.localStorage.has("sbcInvAppPDFDoc")) {
          //   // const filename = $q.localStorage.getItem("sbcInvAppPDFDoc");
          // } else {
          //   // cfunc.showMsgBox("File not found, Please try again.", "negative", "warning", 0, "", [{ icon: "close", color: "white", round: true }]);
          //   sbc.globalFunc.showErrMsg("File not found, Please try again.");
          // }
        }
      },
      cancelEmail: function () {
        console.log("cancelEmail called");
        sbc.showInputLookup = false;
      },
      selectGenItem: function (row) {
        console.log("selectGenItem called", row);
        if (sbc.globalFunc.company !== "mbs") {
          if (row.syscount > 0 && row.syscount !== null && row.syscount !== undefined) {
            sbc.selinputlookupfields = sbc.globalFunc.getLookupForm(sbc.inputlookupfields, "generateInputQtyField", "inputFields");
            sbc.selinputlookupfieldsplot = sbc.globalFunc.getLookupForm(sbc.inputlookupfieldsplot, "generateInputQtyField", "inputPlot");
            sbc.selinputlookupbuttons = sbc.globalFunc.getLookupForm(sbc.inputlookupbuttons, "generateInputQtyBtns", "buttons");
            let qty = "";
            if (row.sales !== "" && row.sales !== 0 && row.sales !== undefined) qty = row.sales;
            sbc.modulefunc.inputLookupForm = { qty: qty, item: row };
            sbc.isFormEdit = true;
            sbc.inputLookupTitle = "Edit Sold Qty";
            sbc.showInputLookup = true;
          }
        }
      },
      saveSoldQty: function () {
        console.log("saveSoldQty called", sbc.modulefunc.inputLookupForm);
        const pcdate = $q.localStorage.getItem("invPCDate");
        const qty = sbc.modulefunc.inputLookupForm.qty;
        const item = sbc.modulefunc.inputLookupForm.item;
        const index = sbc.modulefunc.lookupTableData.indexOf(item);
        if (qty === "" || qty === null || qty === undefined) {
          sbc.globalFunc.showErrMsg("Please enter quantity");
          return;
        }
        if (item.syscount > 0) {
          if (qty > item.syscount) {
            sbc.globalFunc.showErrMsg("Entered quantity is greater than system count.");
            return;
          }
        }
        if (isNaN(qty)) {
          sbc.globalFunc.showErrMsg("Invalid quantity, please try again.");
          return;
        }
        cfunc.showLoading();
        sbc.db.transaction(function (tx) {
          tx.executeSql("select line from soldqtyitems where wh=? and substr(dateid,1,10)=? and barcode=?", [sbc.modulefunc.cLookupForm.wh, pcdate, item.barcode], function (tx, res) {
            if (res.rows.length > 0) {
              tx.executeSql("update soldqtyitems set soldqty=? where line=?", [qty, res.rows.item(0).line], function (tx, res2) {
                cfunc.showMsgBox("Sales updated", "positive");
                sbc.modulefunc.lookupTableData[index].sales = qty;
                sbc.modulefunc.lookupTableData[index].variance = parseInt(sbc.modulefunc.lookupTableData[index].syscount) - (parseInt(sbc.modulefunc.lookupTableData[index].qty) + parseInt(qty));
                sbc.showInputLookup = false;
                $q.loading.hide();
              }, function (tx, err) {
                sbc.globalFunc.showErrMsg("Error updating sales");
                $q.loading.hide();
              });
            } else {
              tx.executeSql("insert into soldqtyitems(wh, dateid, barcode, soldqty) values(?, ?, ?, ?)", [sbc.modulefunc.cLookupForm.wh, pcdate, item.barcode, qty], function (tx, res3) {
                cfunc.showMsgBox("Sales saved", "positive");
                sbc.modulefunc.lookupTableData[index].sales = qty;
                sbc.modulefunc.lookupTableData[index].variance = parseInt(sbc.modulefunc.lookupTableData[index].syscount) - (parseInt(sbc.modulefunc.lookupTableData[index].qty) + parseInt(qty));
                sbc.showInputLookup = false;
                $q.loading.hide();
              }, function (tx, err) {
                sbc.globalFunc.showErrMsg("Error saving sales");
                $q.loading.hide();
              });
            }
          }, function (tx, err) {
            sbc.globalFunc.showErrMsg(err.message);
            $q.loading.hide();
          });
        }, function (err) {
          sbc.globalFunc.showErrMsg(err.message);
          $q.loading.hide();
        });
      },
      cancelSoldQty: function () {
        sbc.showInputLookup = false;
      },
      searchItem: function (waw) {
        let brands = sbc.modulefunc.docForm.brands;
        brands = brands.split(",");
        brands = brands.map(waw => `"${waw}"`).join(",");
        let sql = "select item.itemid, item.barcode, item.itemname, item.brand, ifnull(cast(itembal.bal as float), 0) as bal, clientitem.sku\
          from item\
          left join itembal on itembal.itemid=cast(item.itemid as integer)\
          left join clientitem on clientitem.barcode=item.barcode\
          where item.brand in (" + brands + ")";
        if (sbc.globalFunc.company === "mbs") {
          sql = "select item.itemid, item.barcode, item.itemname, item.brand, ifnull(cast(item.bal as float), 0) as bal, clientitem.sku\
          from item\
          left join clientitem on clientitem.barcode=item.barcode\
          where 1=1";
        }
        let strs = [];
        let f = "";
        let d = [];
        if (waw !== "") strs = waw.split(",");
        if (strs.length > 0) {
          for (var s in strs) {
            strs[s] = strs[s].trim();
            if (strs[s] !== "") {
              if (f !== "") {
                f = f.concat(" and ((item.itemname like ?) or (item.brand like ?) or (clientitem.sku like ?) or (item.barcode like ?) or (item.partno like ?)) ");
              } else {
                f = f.concat(" ((item.itemname like ?) or (item.brand like ?) or (clientitem.sku like ?) or (item.barcode like ?) or (item.partno like ?)) ");
              }
              d.push(["%" + strs[s] + "%", "%" + strs[s] + "%", "%" + strs[s] + "%", "%" + strs[s] + "%", "%" + strs[s] + "%"]);
            }
          }
        }
        if (d.length === 0) {
          d = [];
          sql = sql.concat(" order by item.barcode asc ");
        } else {
          sql = sql.concat(" and (" + f + ") order by item.barcode asc");
        }
        var dd = [].concat.apply([], d);
        sbc.globalFunc.lookupData = [];
        sbc.db.transaction(function (tx) {
          tx.executeSql(sql, dd, function (tx, res) {
            if (res.rows.length > 0) {
              for (var x = 0; x < res.rows.length; x++) {
                sbc.globalFunc.lookupData.push(res.rows.item(x));
              }
            } else {
            }
          });
        }, function (err) {
          console.log("-------......------", err);
        });
      },
      searchItem2: function (waw) {
        sbc.db.transaction(function (tx) {
          const pcdate = $q.localStorage.getItem("invPCDate");
          let items = [];
          let filter = "";
          let brands2 = [];
          sbc.globalFunc.cLookupBrands.map(waw => {
            waw.split(",").map(wew => brands2.push(wew));
          });
          brands2 = brands2.map(waw => `"${waw}"`);
          brands2 = brands2.join(",");
          let sql = "select ? as wh, ? as dateid, item.barcode, item.partno, item.itemname, round(ifnull(cast(ib.bal as float), 0), 2) as syscount, ifnull(ci.sku, ?) as sku, item.brand,\
            ifnull((select sum(qty) as qty from (select qty from hstock where hstock.trno in (" + sbc.globalFunc.cLookupTrnos.join(",") + ") and hstock.barcode=item.barcode) as t), 0) as qty,\
            ifnull(sitems.soldqty, 0) as sales, 0 as variance, item.amt\
            from item\
            left join clientitem as ci on ci.barcode=item.barcode\
            left join itembal as ib on cast(ib.itemid as integer)=item.itemid\
            left join soldqtyitems as sitems on sitems.barcode=item.barcode\
            where item.brand in (" + brands2 + ") " + filter;
          if (sbc.globalFunc.company === "mbs") {
            let tbl = "consolidated";
            if (sbc.modulefunc.gtype === "Final") tbl = "fconsolidated";
            sql = "select ? as wh, ? as dateid, item.barcode, item.partno, item.itemname, round(ifnull(cast(item.bal as float), 0), 2) as syscount, ifnull(ci.sku, ?) as sku, item.brand,\
              ifnull((select sum(qty) as qty from (select qty from hstock where hstock.trno in (" + sbc.globalFunc.cLookupTrnos.join(",") + ") and hstock.barcode=item.barcode) as t), 0) as qty,\
              ifnull(cons.sales, 0) as sales, 0 as variance, item.amt\
              from item\
              left join clientitem as ci on ci.barcode=item.barcode\
              left join " + tbl + " as cons on cons.barcode=item.barcode\
              where 1=1 ";
          }
          let strs = [];
          let f = "";
          let d = [];
          if (waw !== "") strs = waw.split(",");
          if (strs.length > 0) {
            for (var s in strs) {
              strs[s] = strs[s].trim();
              if (strs[s] !== "") {
                if (f !== "") {
                  f = f.concat(" and ((item.itemname like ?) or (item.brand like ?) or (ci.sku like ?) or (item.barcode like ?) or (item.partno like ?)) ");
                } else {
                  f = f.concat(" ((item.itemname like ?) or (item.brand like ?) or (ci.sku like ?) or (item.barcode like ?) or (item.partno like ?)) ");
                }
                d.push(["%" + strs[s] + "%", "%" + strs[s] + "%", "%" + strs[s] + "%", "%" + strs[s] + "%", "%" + strs[s] + "%"]);
              }
            }
          }
          if (d.length === 0) {
            d = [];
            sql = sql.concat(" order by qty desc");
          } else {
            sql = sql.concat(" and (" + f + ") order by qty desc ");
          }
          let dd = [sbc.modulefunc.cLookupForm.wh, pcdate, ""].concat.apply([], d);
          let ddd = dd.slice();
          ddd.unshift("");
          ddd.unshift(pcdate);
          ddd.unshift(sbc.modulefunc.cLookupForm.wh);
          tx.executeSql(sql, ddd, function (tx, ires) {
            if (ires.rows.length) {
              let variance = 0;
              for (var i = 0; i < ires.rows.length; i++) {
                variance = parseInt(ires.rows.item(i).syscount) - (parseInt(ires.rows.item(i).qty) + parseInt(ires.rows.item(i).sales));
                switch (sbc.modulefunc.cLookupForm.generatetype) {
                  case "1":
                    if (variance !== 0) {
                      items.push({
                        wh: ires.rows.item(i).wh,
                        dateid: ires.rows.item(i).dateid,
                        barcode: ires.rows.item(i).barcode,
                        itemname: ires.rows.item(i).itemname,
                        syscount: ires.rows.item(i).syscount,
                        sku: ires.rows.item(i).sku,
                        brand: ires.rows.item(i).brand,
                        qty: ires.rows.item(i).qty,
                        variance: variance
                      });
                    }
                    break;
                  case "2":
                    if (variance === 0) items.push(ires.rows.item(i));
                    break;
                  default:
                    items.push(ires.rows.item(i));
                    items[i].variance = variance;
                    break;
                }
                if (parseInt(i) + 1 === ires.rows.length) {
                  sbc.modulefunc.lookupTableData = items;
                  $q.loading.hide();
                }
              }
            } else {
              sbc.modulefunc.lookupTableData = [];
              $q.loading.hide();
            }
          }, function (tx, err) {
            console.log("err2: ", err.message);
            $q.loading.hide();
          });
        }, function (err) {
          console.log("err1: ", err.message);
          $q.loading.hide();
        });
      },
      downloadInventoryCon: function () {
        const pcdate = $q.localStorage.getItem("invPCDate");
        // const branchaddr = $q.localStorage.getItem("mbsBranchAddr");
        cfunc.showLoading();
        sbc.modulefunc.hasFConsolidated().then(hc => {
          if (!hc) {
            cfunc.getTableData("config", "branchaddr").then(branchaddr => {
              if (branchaddr === null || branchaddr === undefined || branchaddr === "") {
                sbc.globalFunc.showErrMsg("Branch address not set, Please try again.");
                $q.loading.hide();
              } else {
                api.post(branchaddr + "/sbcmobilev2/download", { type: "consolidatedItems", dateid: pcdate }).then(res => {
                  if (res.data.items.length > 0) {
                    sbc.db.transaction(function (tx) {
                      tx.executeSql("delete from fconsolidated", [], function (tx, res2) {
                        let d = res.data.items;
                        let dd = [];
                        while(d.length) dd.push(d.splice(0, 100));
                        save(dd);
                      }, function (tx, err) {
                        $q.loading.hide();
                      });
                    });
                  } else {
                    sbc.globalFunc.showErrMsg("No items to download");
                    $q.loading.hide();
                  }
                }).catch(err => {
                  sbc.globalFunc.showErrMsg(err.message);
                  $q.loading.hide();
                });
              }
            });
          } else {
            sbc.globalFunc.showErrMsg("Downloading Final Consolidated Failed","Please upload final consolidated first, before downloading again.");
            $q.loading.hide();
          }
        });

        function save (fcons, index = 0) {
          if (index === fcons.length) {
            $q.loading.hide();
            cfunc.showMsgBox("Consolidated items saved", "positive");
          } else {
            sbc.db.transaction(function (tx) {
              for (var a in fcons[index]) {
                insertFCons(fcons[index][a]);
                if (parseInt(a) + 1 === fcons[index].length) save(fcons, parseInt(index) + 1);
              }
            });
          }
        }

        function insertFCons (fconsdata) {
          sbc.db.transaction(function (tx) {
            tx.executeSql("insert into fconsolidated(line, barcode, itemid, qty, isuploaded) values(?, ?, ?, ?, ?)", [fconsdata.line, fconsdata.barcode, fconsdata.itemid, fconsdata.qty, 0], null, function (tx, err) {
              console.log("-------------error saving fconsolidated: ", err.message);
            });
          }, function (err) {
            console.log("---==---error saving fconsolidated", err.message);
          });
        }
      },
      uploadMBSDoc: function () {
        cfunc.showLoading();
        const pcdate = $q.localStorage.getItem("invPCDate");
        if (sbc.modulefunc.gtype === "Initial") {
          sbc.modulefunc.hasConsolidated().then(hc => {
            if (!hc) {
              $q.loading.hide();
              sbc.globalFunc.showErrMsg("No data found.");
            } else {
              sbc.db.transaction(function (tx) {
                tx.executeSql("select generated from wh limit 1", [], function (tx, res) {
                  if (res.rows.length > 0) {
                    if (res.rows.item(0).generated === null || res.rows.item(0).generated === undefined || res.rows.item(0).generated === "" || res.rows.item(0).generated === 0) {
                      $q.loading.hide();
                      sbc.globalFunc.showErrMsg("Please generate report first before uploading");
                    } else {
                      tx.executeSql("select branch, client from wh limit 1", [], function (tx, res2) {
                        if (res2.rows.length > 0) {
                          sbc.modulefunc.getMBSConsolidated().then(items => {
                            cfunc.getTableData("config", ["serveraddr", "deviceid", "branchaddr"], true).then(configdata => {
                              if (configdata.serveraddr === "" || configdata.serveraddr === null || configdata.serveraddr === undefined) {
                                sbc.globalFunc.showErrMsg("Server Address not set");
                                $q.loading.hide();
                                return;
                              }
                              // const branchaddr = $q.localStorage.getItem("mbsBranchAddr");
                              if (configdata.branchaddr === null || configdata.branchaddr === "" || configdata.branchaddr === undefined) {
                                sbc.globalFunc.showErrMsg("Branch Address not set, Please try again.");
                                $q.loading.hide();
                              } else {
                                const params = { id: md5("uploadInvMBSDoc"), branch: res2.rows.item(0).branch, wh: res2.rows.item(0).client, dateid: pcdate, items: items, gtype: "initial", devid: configdata.deviceid };
                                api.post(configdata.branchaddr + "/sbcmobilev2/admin", params, { headers: sbc.reqheader }).then(res3 => {
                                  if (res3.data.status) {
                                    $q.loading.hide();
                                    cfunc.showMsgBox(res3.data.msg, "positive");
                                    sbc.showLookup = false;
                                    sbc.modulefunc.setConsUploaded();
                                  } else {
                                    $q.loading.hide();
                                    sbc.globalFunc.showErrMsg(res3.data.msg);
                                  }
                                }).catch (err => {
                                  sbc.globalFunc.showErrMsg("err4: Error uploading items, Please try again. ", err.message);
                                });
                              }
                            });
                          }).catch(err => {
                            $q.loading.hide();
                            sbc.globalFunc.showErrMsg("err3: Error loading items, Please try again. " + err.message);
                          });
                        } else {
                          $q.loading.hide();
                          sbc.globalFunc.showErrMsg("err1: An error occurred; please try again.");
                        }
                      });
                    }
                  } else {
                    $q.loading.hide();
                    sbc.globalFunc.showErrMsg("err2: An error occurred; please try again.");
                  }
                });
              });
            }
          });
        } else {
          sbc.modulefunc.hasFConsolidated().then(hfc => {
            if (!hfc) {
              $q.loading.hide();
              sbc.globalFunc.showErrMsg("No data found.");
            } else {
              sbc.db.transaction(function (tx) {
                tx.executeSql("select * from mbssettings where name=?", ["FGENERATED"], function (tx, fgen) {
                  if (fgen.rows.length > 0) {
                    cfunc.getTableData("wh", ["branch", "client"], true).then(res => {
                      sbc.modulefunc.getMBSFConsolidated().then(items => {
                        cfunc.getTableData("config", ["serveraddr", "deviceid"], true).then(configdata => {
                          if (configdata.serveraddr === "" || configdata.serveraddr === null || configdata.serveraddr === undefined) {
                            sbc.globalFunc.showErrMsg("Server Address not set.");
                            $q.loading.hide();
                            return;
                          }
                          const params = { id: md5("uploadInvMBSDoc"), branch: res.branch, wh: res.client, dateid: pcdate, items: items, gtype: "final", devid: configdata.deviceid };
                          api.post(configdata.serveraddr + "/sbcmobilev2/admin", params, { headers: sbc.reqheader }).then(res2 => {
                            if (res2.data.status) {
                              $q.loading.hide();
                              cfunc.showMsgBox(res2.data.msg, "positive");
                              sbc.showLookup = false;
                              sbc.modulefunc.setConsFUploaded();
                            } else {
                              $q.loading.hide();
                              sbc.globalFunc.showErrMsg(res2.data.msg);
                            }
                          }).catch(err => {
                            $q.loading.hide();
                            sbc.globalFunc.showErrMsg("err1: Error Uploading Final count, Please try again." + err.message);
                          });
                        });
                      }).catch(err => {
                        $q.loading.hide();
                        sbc.globalFunc.showErrMsg("err2: Error loading final items, Please try again." + err.message);
                      });
                    });
                  } else {
                    $q.loading.hide();
                    sbc.globalFunc.showErrMsg("Please generate report first, before uploading.");
                  }
                });
              });
            }
          });
        }
      },
      setConsUploaded: function () {
        sbc.db.transaction(function (tx) {
          tx.executeSql("update consolidated set isuploaded=1");
          let datenow = cfunc.getDateTime("datetime");
          tx.executeSql("insert into mbssettings values(?, ?)", ["UPLOAD", datenow]);
          tx.executeSql("update hhead set uploaded=1");
        });
      },
      setConsFUploaded: function () {
        sbc.db.transaction(function (tx) {
          tx.executeSql("update fconsolidated set isuploaded=1");
          let datenow = cfunc.getDateTime("datetime");
          tx.executeSql("insert into mbssettings values(?, ?)", ["FUPLOAD", datenow]);
        });
      },
      getMBSFConsolidated: function () {
        return new Promise((resolve, reject) => {
          let items = [];
          sbc.db.transaction(function (tx) {
            tx.executeSql("select f.*, item.bal from fconsolidated as f left join item on item.itemid=f.itemid where f.isuploaded=0 or f.isuploaded=null or f.isuploaded=?", [""], function (tx, res) {
              if (res.rows.length > 0) {
                for (var x = 0; x < res.rows.length; x++) {
                  items.push(res.rows.item(x));
                  if (parseInt(x) + 1 === res.rows.length) {
                    resolve(items);
                  }
                }
              } else {
                resolve([]);
              }
            }, function (tx, err) {
              reject(err);
            });
          }, function (err) {
            reject(err);
          });
        });
      },
      getMBSConsolidated: function () {
        return new Promise((resolve, reject) => {
          let items = [];
          sbc.db.transaction(function (tx) {
            tx.executeSql("select * from consolidated where isuploaded=0 or isuploaded=null or isuploaded=?", [""], function (tx, res) {
              if (res.rows.length > 0) {
                for (var x = 0; x < res.rows.length; x++) {
                  items.push(res.rows.item(x));
                  if (parseInt(x) + 1 === res.rows.length) {
                    resolve(items);
                  }
                }
              } else {
                resolve([]);
              }
            }, function (tx, err) {
              reject(err);
            });
          }, function (err) {
            reject(err);
          });
        });
      }
    })';
  }
}
