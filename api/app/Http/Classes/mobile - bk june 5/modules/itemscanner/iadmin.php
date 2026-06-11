<?php
namespace App\Http\Classes\mobile\modules\itemscanner;

use App\Http\Classes\builder\mobiletxtFieldClass;
use App\Http\Classes\builder\mobileButtonClass;

class iadmin {
  private $fieldClass;
  private $buttonClass;
  private $company;

  public function __construct() {
    $this->fieldClass = new mobiletxtFieldClass;
    $this->buttonClass = new mobileButtonClass;
    $this->company = env('COMPANY', 'sbc');
  }
  public function getLayout() {
    $fields = ['uploadcsv', 'showcode', 'loaditems', 'downloadcsv'];
    $cfHeadFields = $this->fieldClass->create($fields);

    $inputLookupFields = [];
    $fields = ['barcode'];
    $codeInputLookupFields = $this->fieldClass->create($fields);
    data_set($codeInputLookupFields, 'barcode.label', 'Enter Barcode');
    array_push($inputLookupFields, ['form'=>'codeInputLookupFields', 'fields'=>$codeInputLookupFields]);

    $inputLookupButtons = [];
    $btns = ['saverecord', 'cancelrecord'];
    $codeInputLookupButtons = $this->buttonClass->create($btns);
    data_set($codeInputLookupButtons, 'saverecord.label', 'verify');
    data_set($codeInputLookupButtons, 'saverecord.func', 'verifycode');
    data_set($codeInputLookupButtons, 'saverecord.functype', 'module');
    data_set($codeInputLookupButtons, 'cancelrecord.label', 'close');
    data_set($codeInputLookupButtons, 'cancelrecord.func', 'cancelverifycode');
    data_set($codeInputLookupButtons, 'cancelrecord.functype', 'module');
    array_push($inputLookupButtons, ['form'=>'codeInputLookupButtons', 'btns'=>$codeInputLookupButtons]);

    $cLookupHeadFields = [];
    $fields = ['assettype', 'assetno', 'subaccount', 'description', 'assignee', 'serial', 'location', 'division', 'remarks'];
    $itemInfoLookupFields = $this->fieldClass->create($fields);
    data_set($itemInfoLookupFields, 'remarks.readonly', false);
    array_push($cLookupHeadFields, ['form'=>'itemInfoLookupFields', 'fields'=>$itemInfoLookupFields]);

    $cLookupButtons = [];
    $btns = ['saverecord', 'cancelrecord'];
    $itemInfoLookupButtons = $this->buttonClass->create($btns);
    data_set($itemInfoLookupButtons, 'saverecord.label', 'Save');
    data_set($itemInfoLookupButtons, 'saverecord.func', 'saveItemDetail');
    data_set($itemInfoLookupButtons, 'saverecord.functype', 'module');
    data_set($itemInfoLookupButtons, 'cancelrecord.label', 'Close & Scan');
    data_set($itemInfoLookupButtons, 'cancelrecord.func', 'closeScan');
    data_set($itemInfoLookupButtons, 'cancelrecord.functype', 'module');
    array_push($cLookupButtons, ['form'=>'itemInfoLookupButtons', 'btns'=>$itemInfoLookupButtons]);

    $cLookupTableCols = [];
    $fields = ['recordid', 'assettype', 'assetno', 'subaccount', 'barcode', 'description', 'shortdesc', 'assignee', 'serial', 'location', 'division', 'cappdp', 'usefullife', 'capcost', 'accddepn', 'nbv', 'dateofbarcoding', 'time', 'user', 'remarks', 'scanneddate'];
    $iLookupTableCols = $this->fieldClass->create($fields);
    data_set($iLookupTableCols, 'assettype.type', 'label');
    data_set($iLookupTableCols, 'assettype.field', 'assettype');
    data_set($iLookupTableCols, 'assetno.type', 'label');
    data_set($iLookupTableCols, 'assetno.field', 'assetno');
    data_set($iLookupTableCols, 'subaccount.type', 'label');
    data_set($iLookupTableCols, 'subaccount.field', 'subaccount');
    data_set($iLookupTableCols, 'barcode.type', 'label');
    data_set($iLookupTableCols, 'barcode.field', 'barcode');
    data_set($iLookupTableCols, 'description.type', 'label');
    data_set($iLookupTableCols, 'description.field', 'description');
    data_set($iLookupTableCols, 'assignee.type', 'label');
    data_set($iLookupTableCols, 'assignee.field', 'assignee');
    data_set($iLookupTableCols, 'serial.type', 'label');
    data_set($iLookupTableCols, 'serial.field', 'serial');
    data_set($iLookupTableCols, 'location.type', 'label');
    data_set($iLookupTableCols, 'location.field', 'location');
    data_set($iLookupTableCols, 'division.type', 'label');
    data_set($iLookupTableCols, 'division.field', 'division');
    data_set($iLookupTableCols, 'remarks.type', 'label');
    data_set($iLookupTableCols, 'remarks.field', 'remarks');
    array_push($cLookupTableCols, ['form'=>'iLookupTableCols', 'fields'=>$iLookupTableCols]);

    $fields = ['ifilter', 'refresh'];
    $iLookupHeadFields = $this->fieldClass->create($fields);
    data_set($iLookupHeadFields, 'refresh.func', 'loadItems2');
    data_set($iLookupHeadFields, 'refresh.functype', 'module');
    array_push($cLookupHeadFields, ['form'=>'iLookupHeadFields', 'fields'=>$iLookupHeadFields]);

    return ['cfHeadFields'=>$cfHeadFields, 'inputLookupFields'=>$inputLookupFields, 'inputLookupButtons'=>$inputLookupButtons, 'cLookupHeadFields'=>$cLookupHeadFields, 'cLookupButtons'=>$cLookupButtons, 'cLookupTableCols'=>$cLookupTableCols];
  }

  public function getFunc() {
    return '({
      docForm: [],
      loadTableData: function () {},
      uploadcsv: function () {
        console.log("uploadCSV called");
        const input = document.createElement("input");
        input.type = "file";
        input.onchange = _ => {
          const files = Array.from(input.files);
          let f = files[0];
          let reader = new FileReader;
          reader.onload = function (e) {
            let data = new Uint8Array(e.target.result);
            let workbook = XLSX.read(data, { type: "array" });
            const sheetName = workbook.SheetNames[0];
            const worksheet = workbook.Sheets[sheetName];
            let datas = XLSX.utils.sheet_to_json(worksheet, { defval: "" });
            if (datas.length > 0) {
              sbc.modulefunc.clearItemTable();
            }
            for (var d in datas) {
              if (typeof (datas[d].scanneddate) !== "undefined") {
                if (datas[d].scanneddate !== "") {
                  datas[d].scanneddate = sbc.modulefunc.formatDate(datas[d].scanneddate);
                }
              }
            }
            sbc.modulefunc.saveCSVtoDB(datas);
          }
          reader.readAsArrayBuffer(f);
        }
        input.click();
      },
      clearItemTable: function () {
        sbc.db.transaction(function (tx) {
          tx.executeSql("delete from items")
        });
      },
      formatDate: function (serial) {
        let utcdays = Math.floor(serial - 25569);
        let utcvalue = utcdays * 86400;
        let dateinfo = new Date(utcvalue * 1000);
        let fractionalday = serial - Math.floor(serial) + 0.0000001;
        let totalseconds = Math.floor(86400 * fractionalday);
        let seconds = totalseconds % 60;
        totalseconds -= seconds;
        let hours = Math.floor(totalseconds / (60 * 60));
        let minutes = Math.floor(totalseconds / 60) % 60;
        let datess = new Date(dateinfo.getFullYear(), dateinfo.getMonth(), dateinfo.getDate(), hours, minutes, seconds);
        return date.formatDate(datess, "YYYY-MM-DD h:mm:ss A");
      },
      saveCSVtoDB: function (datas) {
        let itemData = { data: { inserts: { items: datas } } }
          cordova.plugins.sqlitePorter.importJsonToDb(sbc.db, itemData, {
            successFn: function (count) {
              cfunc.showMsgBox("Successfully imported " + count + " items");
              $q.loading.hide();
            },
            errorFn: function (error) {
              cfunc.showMsgBox("Import Error: " + error.message, "negative", "warning");
              console.log(error.message);
              $q.loading.hide();
            },
            progressFn: function (current, total) {
              cfunc.showLoading("Saving Items (Batch " + current + " of " + total + ")");
            }
          })
      },
      showcode: function () {
        console.log("showcode called");
        sbc.modulefunc.inputLookupForm = { barcode: "" };
        sbc.selinputlookupfields = sbc.globalFunc.getLookupForm(sbc.inputlookupfields, "codeInputLookupFields", "inputFields");
        sbc.selinputlookupfieldsplot = sbc.globalFunc.getLookupForm(sbc.inputlookupfieldsplot, "codeInputLookupFields", "inputPlot");
        sbc.selinputlookupbuttons = sbc.globalFunc.getLookupForm(sbc.inputlookupbuttons, "codeInputLookupButtons", "buttons");
        sbc.isFormEdit = true;
        sbc.inputLookupTitle = "";
        sbc.showInputLookup = true;
      },
      loaditems: function () {
        cfunc.showLoading();
        sbc.cLookupTitle = "Item List";
        sbc.selclookupheadfields = sbc.globalFunc.getLookupForm(sbc.clookupheadfields, "iLookupHeadFields", "inputFields");
        sbc.selclookupheadfieldsplot = sbc.globalFunc.getLookupForm(sbc.clookupheadfieldsplot, "iLookupHeadFields", "inputPlot");
        sbc.selclookuptablecols = sbc.globalFunc.getLookupForm(sbc.clookuptablecols, "iLookupTableCols", "inputFields");
        sbc.selclookuptablecolsplot = sbc.globalFunc.getLookupForm(sbc.clookuptablecolsplot, "iLookupTableCols", "inputPlot");
        sbc.modulefunc.cLookupForm = { ifilter: "all" };
        sbc.isFormEdit = true;
        sbc.modulefunc.loadItems2();
      },
      loadItems2: function () {
        sbc.db.transaction(function (tx) {
          let sql = "";
          let data = [];
          switch (sbc.modulefunc.cLookupForm.ifilter) {
            case "scanned":
              sql = "select * from items where scanneddate <> ? order by barcode asc";
              data = [""];
              break;
            case "unscanned":
              sql = "select * from items where (scanneddate is null or scanneddate=?) order by barcode asc";
              data = [""];
              break;
            default:
              sql = "select * from items order by barcode asc";
              break;
          }
          tx.executeSql(sql, data, function (tx, res) {
            sbc.modulefunc.lookupTableData = [];
            if (res.rows.length > 0) {
              for (var x = 0; x < res.rows.length; x++) {
                sbc.modulefunc.lookupTableData.push(res.rows.item(x));
              }
            }
            $q.loading.hide();
          }, function (tx, err) {
            console.log(err.message);
            $q.loading.hide();
          });
          sbc.showCustomLookup = true;
        });
      },
      downloadcsv: function () {
        console.log("downloadcsv called");
        let encodedUri;
        sbc.db.transaction(function (tx) {
          tx.executeSql("select assettype, assetno, subaccount, barcode, description, shortdesc, assignee, serial, location, division, cappdp, usefullife, capcost, accddepn, nbv, dateofbarcoding, time, user, remarks, scanneddate from items", [], function (tx, res) {
            let datas = [];
            for (var x = 0; x < res.rows.length; x++) {
              datas.push(res.rows.item(x));
            }
            console.log("1111", datas);
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
            downloadFile();
          }, function (tx, err) {
            console.log("error2: ", err);
          });
        }, function (err) {
          console.log("error1: ", err);
        });
        const permissions = cordova.plugins.permissions;
        function downloadFile () {
          permissions.checkPermission(permissions.WRITE_EXTERNAL_STORAGE, checkPermissionCallback, null);
        }
        function checkPermissionCallback (status) {
          let errorCallback = function () {
            alertt("error permission");
          }
          if (!status.hasPermission) {
            permissions.requestPermission(
              permissions.WRITE_EXTERNAL_STORAGE,
              function (status) {
                if (!status.hasPermission) {
                  errorCallback();
                } else {
                  continueDownloadFile();
                }
              },
              errorCallback);
          } else {
            continueDownloadFile();
          }
        }
        function continueDownloadFile () {
          $q.dialog({
            title: "Download CSV",
            message: "Enter file name",
            prompt: {
              model: "",
              type: "text"
            },
            cancel: true,
            persistent: true
          }).onOk(data => {
            if (data !== "") {
              const filePath = cordova.file.externalRootDirectory + "FAMS/" + data + ".csv";
              const fileTransfer = new window.FileTransfer();

              // downloading the file
              fileTransfer.download(encodedUri, filePath,
                function (entry) {
                  cfunc.showMsgBox("Successfully downloaded file, full path is " + entry.fullPath, "positive");
                  $q.loading.hide();
                },
                function (error) {
                  cfunc.showMsgBox("Error downloading file", "negative", "warning");
                  $q.loading.hide()
                },
                false
              );
            } else {
              cfunc.showMsgBox("Invalid file name", "negative", "warning");
              $q.loading.hide();
            }
          });
        }
      },
      verifycode: function () {
        sbc.modulefunc.getItemDetail(sbc.modulefunc.inputLookupForm.barcode, "manual");
      },
      getItemDetail: function (barcode, gettype) {
        cfunc.showLoading();
        sbc.db.transaction(function (tx) {
          tx.executeSql("select * from items where barcode=?", [barcode], function (tx, res) {
            if (res.rows.length > 0) {
              sbc.modulefunc.cLookupForm = res.rows.item(0);
              sbc.cLookupTitle = "Item Detail";
              sbc.selclookupheadfields = sbc.globalFunc.getLookupForm(sbc.clookupheadfields, "itemInfoLookupFields", "inputFields");
              sbc.selclookupheadfieldsplot = sbc.globalFunc.getLookupForm(sbc.clookupheadfieldsplot, "itemInfoLookupFields", "inputPlot");
              sbc.selclookupbuttons = sbc.globalFunc.getLookupForm(sbc.clookupbuttons, "itemInfoLookupButtons", "buttons");
              sbc.showCustomLookup = true;
              sbc.isFormEdit = true;
              $q.loading.hide();
            } else {
              cfunc.showMsgBox("No item record found", "negative", "warning");
              $q.loading.hide();
            }
          }, function (tx, err) {
            cfunc.showMsgBox(err.message, "negative", "warning");
            $q.loading.hide();
          });
        });
      },
      cancelverifycode: function () {
        sbc.showInputLookup = false;
      },
      saveItemDetail: function () {
        console.log("saveItemDetail called");
        let datenow = cfunc.getDateTime("datetime");
        cfunc.showLoading();
        sbc.db.transaction(function (tx) {
          tx.executeSql("update items set location=?, division=?, scanneddate=?, remarks=? where barcode=?", [sbc.modulefunc.cLookupForm.location, sbc.modulefunc.cLookupForm.division, datenow, sbc.modulefunc.cLookupForm.remarks, sbc.modulefunc.cLookupForm.barcode], function (tx, res) {
            cfunc.showMsgBox("Item list updated", "positive");
            $q.loading.hide();
          }, function (tx, err) {
            cfunc.showMsgBox(err.message, "negative", "warning");
            $q.loading.hide();
          });
        });
      },
      closeScan: function () {
        sbc.showCustomLookup = false;
      }
    })';
  }
}