<?php
namespace App\Http\Classes\mobile\modules\collection;

use App\Http\Classes\builder\mobiletxtFieldClass;
use App\Http\Classes\builder\mobileButtonClass;

class coladmin {
  private $fieldClass;
  private $buttonClass;
  private $company;

  public function __construct() {
    $this->fieldClass = new mobiletxtFieldClass;
    $this->buttonClass = new mobileButtonClass;
    $this->company = env('COMPANY', 'sbc');
  }
  public function getLayout() {
    // $fields = ['changeprinter', 'closecollection', 'cleartrans', 'collectionreport', 'download', 'operationtype', 'printtype', 'reprintreading', 'reprinttrans', 'upload', 'viewtables', 'logout'];
    $fields = ['changeprinter', 'closecollection', 'cleartrans', 'collectionreport', 'download', 'operationtype', 'printtype', 'reprintreading', 'reprinttrans', 'upload', 'logout'];
    $cfHeadFields = $this->fieldClass->create($fields);

    $inputLookupFields = [];
    $inputLookupButtons = [];
    $fields = ['startdate', 'enddate'];
    $clearTransLookup = $this->fieldClass->create($fields);
    array_push($inputLookupFields, ['form'=>'clearTransLookupFields', 'fields'=>$clearTransLookup]);

    $btns = ['saverecord', 'cancelrecord'];
    $clearTransButtons = $this->buttonClass->create($btns);
    data_set($clearTransButtons, 'saverecord.form', 'clearTransButtons');
    data_set($clearTransButtons, 'saverecord.func', 'saveClearTrans');
    data_set($clearTransButtons, 'cancelrecord.form', 'clearTransButtons');
    data_set($clearTransButtons, 'cancelrecord.func', 'cancelClearTrans');
    array_push($inputLookupButtons, ['form'=>'clearTransButtons', 'btns'=>$clearTransButtons]);

    $fields = ['transtype', 'tenant'];
    $reprintTransReadLookup = $this->fieldClass->create($fields);
    data_set($reprintTransReadLookup, 'transtype.type', 'lookup');
    data_set($reprintTransReadLookup, 'transtype.action', 'transtypeslookup2');
    data_set($reprintTransReadLookup, 'transtype.readonly', 'true');
    data_set($reprintTransReadLookup, 'transtype.fields', 'transtype');
    array_push($inputLookupFields, ['form'=>'reprintTransReadLookup', 'fields'=>$reprintTransReadLookup]);

    $btns = ['saverecord', 'cancelrecord'];
    $reprintTransReadLookupBtns = $this->buttonClass->create($btns);
    data_set($reprintTransReadLookupBtns, 'saverecord.form', 'reprintTransReadLookupBtns');
    data_set($reprintTransReadLookupBtns, 'saverecord.func', 'submitReprintTransRead');
    data_set($reprintTransReadLookupBtns, 'saverecord.functype', 'module');
    data_set($reprintTransReadLookupBtns, 'cancelrecord.form', 'reprintTransReadLookupBtns');
    data_set($reprintTransReadLookupBtns, 'cancelrecord.func', 'cancelreprintTransRead');
    data_set($reprintTransReadLookupBtns, 'cancelrecord.functype', 'module');
    array_push($inputLookupButtons, ['form'=>'reprintTransReadLookupBtns', 'btns'=>$reprintTransReadLookupBtns]);

    return ['cfHeadFields'=>$cfHeadFields, 'inputLookupFields'=>$inputLookupFields, 'inputLookupButtons'=>$inputLookupButtons];
  }

  public function getFunc() {
    return '({
      docForm: { transtype: "", selTenant: [], tenant: "" },
      loadTableData: function () {},
      selectPrinter: function (row) {
        $q.dialog({
            message: "Do you want to change Printer?",
            ok: { flat: true, color: "primary" },
            cancel: { flat: true, color: "negative" }
          }).onOk(() => {
            cfunc.showLoading();
            sbc.db.transaction(function (tx) {
              tx.executeSql("update config set printer=?, printerlen=?", [row.name, row.len], function (tx, res) {
                cfunc.showMsgBox("Printer changed", "positive");
                sbc.showLookup = false;
                $q.loading.hide();
              }, function (tx, err) {
                cfunc.showMsgBox(err.message, "negative", "warning");
                $q.loading.hide();
              });
            });
          });
      },
      changePrinter: function () {
        console.log("changePrinter called");
        cfunc.showLoading();
        cfunc.getTableData("config", "serveraddr").then(serveraddr => {
          if (serveraddr === "" || serveraddr === null || typeof(serveraddr) === "undefined") {
            cfunc.showMsgBox("Server Address not set", "negative", "warning");
            $q.loading.hide();
          } else {
            api.post(serveraddr + "/sbcmobilev2/admin", { id: md5("loadPrinters") }, { headers: sbc.reqheader })
              .then(res => {
                sbc.globalFunc.lookupTableSelect = false;
                sbc.globalFunc.lookupCols = [
                  { name: "name", label: "Name", align: "left", field: "name", sortable: true },
                  { name: "len", label: "Length", align: "left", field: "len", sortable: true }
                ];
                sbc.modulefunc.lookupTableFilter = { type: "filter", field: "", label: "Search Printer", func: "" };
                sbc.lookupTitle = "Select Printer";
                sbc.showLookup = true;
                sbc.globalFunc.lookupAction = "printerLookup";
                sbc.globalFunc.lookupData = res.data;
                $q.loading.hide();
              });
          }
        });
      },
      closeCollection: function () {
        console.log("closeCollection called");
        $q.dialog({
          message: "Are you sure you want to close transaction?",
          ok: { flat: true, color: "primary" },
          cancel: { flat: true, color: "negative" }
        }).onOk(() => {
          cfunc.showLoading();
          sbc.modulefunc.getTransCount().then(tCount => {
            if (tCount > 0) {
              cfunc.showMsgBox("Collections/Readings must be uploaded before closing", "negative", "warning");
              $q.loading.hide();
            } else {
              sbc.db.transaction(function (tx) {
                tx.executeSql("update config set collectiondate=null", [], function (tx, res) {
                  cfunc.showMsgBox("Transaction closed", "positive");
                  $q.loading.hide();
                }, function (tx, err) {
                  cfunc.showMsgBox(err.message, "negative", "warning");
                  $q.loading.hide();
                });
              });
            }
          });
        });
      },
      clearTrans: function () {
        console.log("clearTrans called");
        sbc.modulefunc.inputLookupForm = { startdate: cfunc.getDateTime("date"), enddate: cfunc.getDateTime("date") };
        sbc.selinputlookupfields = sbc.globalFunc.getLookupForm(sbc.inputlookupfields, "clearTransLookupFields", "inputFields");
        sbc.selinputlookupfieldsplot = sbc.globalFunc.getLookupForm(sbc.inputlookupfieldsplot, "clearTransLookupFields", "inputPlot");
        sbc.selinputlookupbuttons = sbc.globalFunc.getLookupForm(sbc.inputlookupbuttons, "clearTransButtons", "buttons");
        sbc.isFormEdit = true;
        sbc.inputLookupTitle = "Clear Transaction";
        sbc.showInputLookup = true;
      },
      saveClearTrans: function () {
        if (sbc.modulefunc.inputLookupForm.startdate === "") {
          cfunc.showMsgBox("Please select start date", "negative", "warning");
          return;
        }
        if (sbc.modulefunc.inputLookupForm.enddate === "") {
          cfunc.showMsgBox("Please select end date", "negative", "warning");
          return;
        }
        if (sbc.modulefunc.inputLookupForm.startdate > sbc.modulefunc.inputLookupForm.enddate) {
          cfunc.showMsgBox("Invalid date range", "negative", "warning");
          return;
        }
        const date1 = sbc.modulefunc.inputLookupForm.startdate.replace(/\//g, "-");
        const date2 = sbc.modulefunc.inputLookupForm.enddate.replace(/\//g, "-");
        cfunc.showLoading();
        sbc.db.transaction(function (tx) {
          tx.executeSql("delete from hdailycollection where dateid>=? and dateid<=?", [date1, date2], function (tx, res) {
            if (res.rowsAffected > 0) {
              cfunc.showMsgBox(res.rowsAffected + " Transaction(s) removed", "positive");
              $q.loading.hide();
            } else {
              cfunc.showMsgBox("No transaction found", "negative", "warning");
              $q.loading.hide();
            }
          }, function (tx, err) {
            cfunc.showMsgBox(err.message, "negative", "warning");
            $q.loading.hide();
          });
        });
      },
      cancelClearTrans: function () {
        sbc.showInputLookup = false;
      },
      collectionReport: function () {
        sbc.db.transaction(function (tx) {
          tx.executeSql("select d.line, d.clientid, c.clientname, count(d.line) as counts, sum(replace(d.amount,?,?)) as amount, d.status, c.loc, case(d.type) when ? then ? when ? then ? when ? then ? when ? then ? when ? then ? end as type from dailycollection as d left join client as c on c.clientid=d.clientid group by d.status, d.type, d.clientid order by d.line", [",", "", "R", "Rent", "C", "CUSA", "O", "Others", "E", "Electricity", "W", "Water"], function (tx, res) {
            let data = [];
            for (var x = 0; x < res.rows.length; x++) {
              data.push({
                line: res.rows.item(x).line,
                clientid: res.rows.item(x).clientid,
                clientname: res.rows.item(x).clientname,
                loc: res.rows.item(x).loc,
                amount: sbc.numeral(res.rows.item(x).amount).format("0,0.00"),
                status: res.rows.item(x).status,
                type: res.rows.item(x).type,
                counts: res.rows.item(x).counts
              });
            }
            const datenow = cfunc.getDateTime("datetime");
            cfunc.getTableData("config", ["collectorid", "username"], true).then(config => {
              const params = {
                data: data,
                collectorid: config.collectorid,
                collectorname: config.username,
                transtime: datenow,
                receiptTitle: "Collection Report",
                receiptType: "collectionreport",
                reprint: true
              };
              sbc.globalFunc.printCollectionReceipt(params, "collectionreport");
            });
          }, function (tx, err) {
            console.log("aaaaaaaaaaaaaaaa", err.message);
          });
        });
      },
      downloadAdmin: function () {
        console.log("downloadAdmin called");
        cfunc.showLoading();
        sbc.modulefunc.getTransCount().then(tCount => {
          if (tCount > 0) {
            cfunc.showMsgBox("Please upload all collections and readings before downloading", "negative", "warning");
            $q.loading.hide();
          } else {
            cfunc.getTableData("config", "serveraddr").then(serveraddr => {
              if (serveraddr === "" || serveraddr === null || typeof(serveraddr) === "undefined") {
                cfunc.showMsgBox("Server Address not set", "negative", "warning");
                $q.loading.hide();
              } else {
                api.post(serveraddr + "/sbcmobilev2/admin", { id: md5("getDailyCollectionCount") }, { headers: sbc.reqheader })
                  .then(dccount => {
                    if (dccount.data.dccount > 0) {
                      cfunc.showMsgBox("The server still has entries for generation", "negative", "warning");
                      $q.loading.hide();
                    } else {
                      sbc.modulefunc.getCenter().then(center => {
                        api.post(serveraddr + "/sbcmobilev2/admin", { id: md5("getCollectionDate"), center: center }, { headers: sbc.reqheader })
                          .then(res => {
                            if (res.data.date !== "") {
                              sbc.modulefunc.getLocalCollectionDate().then(localColDate => {
                                if (localColDate !== "" && localColDate !== null && typeof(localColDate) !== "undefined") {
                                  $q.loading.hide();
                                  $q.dialog({
                                    message: "Your current Collection date is " + localColDate + ". Want to change it to " + res.data.date + "?",
                                    ok: { flat: true, color: "primary", label: "Yes" },
                                    cancel: { flat: true, color: "negative", label: "No" }
                                  }).onOk(() => {
                                    sbc.modulefunc.setLocalCollectionDate(res.data.date).then(res => {
                                      sbc.modulefunc.continueDownload(serveraddr);
                                      $q.loading.hide();
                                    }).catch(err => {
                                      cfunc.showMsgBox(err.message, "negative", "warning");
                                      $q.loading.hide()
                                    });
                                  });
                                } else {
                                  sbc.modulefunc.setLocalCollectionDate(res.data.date).then(res => {
                                    sbc.modulefunc.continueDownload(serveraddr);
                                    $q.loading.hide();
                                  }).catch(err => {
                                    cfunc.showMsgBox(err.message, "negative", "warning");
                                    $q.loading.hide();
                                  });
                                }
                              });
                            } else {
                              cfunc.showMsgBox("Collection date not set on server", "negative", "warning");
                              $q.loading.hide();
                            }
                          })
                          .catch(err => {
                            cfunc.showMsgBox(err, "negative", "warning");
                            $q.loading.hide();
                          });
                      }).catch(err => {
                        cfunc.showMsgBox(err, "negative", "warning");
                        $q.loading.hide();
                      });
                    }
                  })
              }
            });
          }
        });
      },
      operationType: function () {
        console.log("operationType called");
        cfunc.getTableData("config", "operationtype").then(operationtype => {
          sbc.globalFunc.lookupTableSelect = false;
          sbc.globalFunc.lookupCols = [
            { name: "type", label: "Type", align: "left", field: "type", sortable: true },
            { name: "active", label: "Active", align: "left", field: "active" }
          ];
          sbc.modulefunc.lookupTableFilter = { type: "filter", field: "", label: "Search Operation Type", func: "" };
          sbc.lookupTitle = "Select Operation Type";
          sbc.showLookup = true;
          sbc.globalFunc.lookupAction = "operationTypeLookup";
          sbc.globalFunc.lookupData = [
            { type: "Both", active: operationtype === "Both" ? "Active" : "" },
            { type: "Collecting", active: operationtype === "Collecting" ? "Active" : "" },
            { type: "Reading", active: operationtype === "Reading" ? "Active" : "" }
          ];
        });
      },
      printType: function () {
        console.log("printType called");
        cfunc.getTableData("config", "printtype").then(printtype => {
          sbc.globalFunc.lookupTableSelect = false;
          sbc.globalFunc.lookupCols = [
            { name: "type", label: "Type", align: "left", field: "type", sortable: true },
            { name: "active", label: "Active", align: "left", field: "active" }
          ];
          sbc.modulefunc.lookupTableFilter = { type: "filter", field: "", label: "Search Print Type", func: "" };
          sbc.lookupTitle = "Select Print Type";
          sbc.showLookup = true;
          sbc.globalFunc.lookupAction = "printTypeLookup";
          sbc.globalFunc.lookupData = [
            { type: "Auto Print", data: "auto", active: printtype === "auto" ? "Active" : "" },
            { type: "Manual Print", data: "manual", active: printtype === "manual" ? "Active" : "" }
          ];
        });
      },
      reprintReading: function () {
        console.log("reprintReading called");
        sbc.modulefunc.reprintTransRead("Reading");
        sbc.modulefunc.docForm.reprintType = "read";
      },
      reprintTrans: function () {
        console.log("reprintTrans called");
        sbc.modulefunc.reprintTransRead("Transaction");
        sbc.modulefunc.docForm.reprintType = "trans";
      },
      reprintTransRead: function (type) {
        sbc.modulefunc.inputLookupForm = { transtype: "", tenant: "" };
        sbc.modulefunc.lookupTableFilter = { type: "filter", field: "", label: "Search " + type, func: "" };
        sbc.selinputlookupfields = sbc.globalFunc.getLookupForm(sbc.inputlookupfields, "reprintTransReadLookup", "inputFields");
        sbc.selinputlookupfieldsplot = sbc.globalFunc.getLookupForm(sbc.inputlookupfieldsplot, "reprintTransReadLookup", "inputPlot");
        sbc.selinputlookupbuttons = sbc.globalFunc.getLookupForm(sbc.inputlookupbuttons, "reprintTransReadLookupBtns", "buttons");
        console.log("selInputLookupFields: ", sbc.selinputlookupfields);
        sbc.isFormEdit = true;
        sbc.inputLookupTitle = "Reprint " + type;
        sbc.showInputLookup = true;
      },
      submitReprintTransRead: function () {
        if (sbc.modulefunc.inputLookupForm.selTenant.clientid === "" || typeof (sbc.modulefunc.inputLookupForm.selTenant.clientid) === "undefined") {
          cfunc.showMsgBox("Please select Tenant", "negative", "warning");
          return;
        }
        let transtype = "";
        let outbal = "";
        switch (sbc.modulefunc.inputLookupForm.transtype) {
          case "Rent":
            transtype = "R";
            outbal = sbc.modulefunc.inputLookupForm.selTenant.outar;
            break;
          case "CUSA":
            transtype = "C";
            outbal = sbc.modulefunc.inputLookupForm.selTenant.outcusa;
            break;
          case "Electricity":
            transtype = "E";
            outbal = sbc.modulefunc.inputLookupForm.selTenant.outelec;
            break;
          case "Water":
            transtype = "W";
            outbal = sbc.modulefunc.inputLookupForm.selTenant.outwater;
            break;
          case "Others": transtype = "O"; break;
        }
        cfunc.getTableData("config", "collectiondate", false).then(collDate => {
          switch (sbc.modulefunc.docForm.reprintType) {
            case "trans":
              sbc.db.transaction(function (tx) {
                tx.executeSql("select t.line, t.amount, t.status, t.dateid, t.type, t.remarks, t.transtime, t.collectorid from (select line, amount, status, dateid, type, remarks, transtime, collectorid from dailycollection where clientid=? and type=? and dateid=? union all select line, amount, status, dateid, type, remarks, transtime, collectorid from hdailycollection where clientid=? and type=? and dateid=?) as t", [sbc.modulefunc.inputLookupForm.selTenant.clientid, transtype, collDate, sbc.modulefunc.inputLookupForm.selTenant.clientid, transtype, collDate], function (tx, res) {
                  if (res.rows.length > 0) {
                    sbc.modulefunc.contPrint(res.rows.item(0), "payment", transtype, outbal);
                  } else {
                    cfunc.showMsgBox("No record found.", "negative", "warning");
                  }
                }, function (tx, err) {
                  console.log("error: ", err.message);
                  cfunc.showMsgBox("Error getting data.", "negative", "warning");
                });
              });
              break;
            case "read":
              sbc.db.transaction(function (tx) {
                tx.executeSql("select t.line, t.beginning, t.ending, t.consumption, t.rate, t.clientid, t.dateid, t.remarks, t.type, t.collectorid from (select line, beginning, ending, consumption, rate, clientid, dateid, remarks, type, collectorid from reading where clientid=? and type=? and dateid=? union all select line, beginning, ending, consumption, rate, clientid, dateid, remarks, type, collectorid from hreading where clientid=? and type=? and dateid=?) as t", [sbc.modulefunc.inputLookupForm.selTenant.clientid, transtype, collDate, sbc.modulefunc.inputLookupForm.selTenant.clientid, transtype, collDate], function (tx, res) {
                  if (res.rows.length > 0) {
                    sbc.modulefunc.contPrint(res.rows.item(0), "reading", transtype, outbal);
                  } else {
                    cfunc.showMsgBox("No record found.", "negative", "warning");
                  }
                }, function (tx, err) {
                  console.log("error: ", err.message);
                  cfunc.showMsgBox("Error getting data.", "negative", "warning");
                });
              });
              break;
          }
        });
      },
      contPrint: function (res, type, transtype, outbal) {
        cfunc.getTableData("config", "username", false).then(configusername => {
          let params;
          switch (type) {
            case "payment":
              params = {
                clientid: sbc.modulefunc.inputLookupForm.selTenant.clientid,
                clientname: sbc.modulefunc.inputLookupForm.selTenant.clientname,
                amount: sbc.numeral(res.amount).format("0,0.00"),
                remarks: res.remarks,
                transtime: res.transtime,
                outstandingbal: sbc.numeral(outbal).format("0,0.00"),
                receiptTitle: "Acknowledgement Receipt",
                receiptType: "payment",
                rent: sbc.numeral(sbc.modulefunc.inputLookupForm.selTenant.dailyrent).format("0,0.00"),
                cusa: sbc.numeral(sbc.modulefunc.inputLookupForm.selTenant.dcusa).format("0,0.00"),
                center: sbc.modulefunc.inputLookupForm.selTenant.center,
                section: sbc.modulefunc.inputLookupForm.selTenant.section,
                stallnum: sbc.modulefunc.inputLookupForm.selTenant.loc,
                dateid: res.dateid,
                status: "OP",
                type: transtype,
                collectorid: res.collectorid,
                collectorname: configusername,
                transtype: sbc.modulefunc.inputLookupForm.transtype,
                line: res.line,
                reprint: true
              };
              sbc.globalFunc.printCollectionReceipt(params, "payment");
              break;
            default:
              params = {
                receiptTitle: "Acknowledgement Receipt",
                receiptType: "reading",
                clientid: sbc.modulefunc.inputLookupForm.selTenant.clientid,
                clientname: sbc.modulefunc.inputLookupForm.selTenant.clientname,
                stallnum: sbc.modulefunc.inputLookupForm.selTenant.loc,
                section: sbc.modulefunc.inputLookupForm.selTenant.section,
                dateid: res.dateid,
                collectorname: configusername,
                beginning: sbc.numeral(res.beginning).format("0,0.00"),
                ending: sbc.numeral(res.ending).format("0,0.00"),
                consumption: sbc.nuemral(res.consumption).format("0,0.00"),
                rate: sbc.nuemral(res.rate).format("0,0.00"),
                amtdue: sbc.numeral(sbc.numeral(res.rate).value() * sbc.numeral(res.consumption).value()).format("0,0.00"),
                type: res.type,
                reprint: true
              };
              sbc.globalFunc.printCollectionReceipt(params, "reading");
              break;
          }
        });
      },
      uploadAdmin: function () {
        console.log("uploadAdmin called");
        sbc.globalFunc.lookupTableSelect = false;
        sbc.globalFunc.lookupCols = [
          { name: "type", label: "Type", align: "left", field: "type", sortable: true }
        ];
        sbc.modulefunc.lookupTableFilter = { type: "filter", field: "", label: "Search Transaction Type", func: "" };
        sbc.lookupTitle = "Select Transaction Type";
        sbc.showLookup = true;
        sbc.globalFunc.lookupAction = "transTypeLookup";
        sbc.globalFunc.lookupData = [
          { type: "Rent" },
          { type: "CUSA" },
          { type: "Electricity" },
          { type: "Water" },
          { type: "Others" },
          { type: "Electric Reading" },
          { type: "Water Reading" }
        ];
      },
      viewTables: function () {
        console.log("viewTables called");
      },
      logoutAdmin: function () {
        console.log("logoutAdmin called");
        $q.dialog({
          message: "Do you want to logout?",
          ok: { flat: true, color: "primary" },
          cancel: { flat: true, color: "negative" }
        }).onOk(() => {
          $q.localStorage.remove("sbcmobilev2Data");
          $q.localStorage.remove("sbcmobilev2SelDoc");
          sbc.selDoc = [];
          sbc.doc = "";
          router.push({ path: "" });
        });
      },
      getTransCount: function () {
        return new Promise((resolve, reject) => {
          sbc.db.transaction(function (tx) {
            tx.executeSql("select sum(t.counts) as counts from (select count(line) as counts from dailycollection union all select count(line) as counts from reading) as t", [], function (tx, res) {
              resolve(res.rows.item(0).counts);
            }, function (tx, err) {
              reject(err);
              });
          });
        });
      },
      getCenter: function () {
        return new Promise((resolve, reject) => {
          cfunc.getTableData("config", "center").then(center => {
            if (center === "" || center === null || typeof(center) === "undefined") {
              reject("No center found.");
            } else {
              resolve(center);
            }
          });
        });
      },
      getLocalCollectionDate: function () {
        return new Promise((resolve) => {
          cfunc.getTableData("config", "collectiondate").then(colldate => {
            resolve(colldate);
          });
        });
      },
      setLocalCollectionDate: function (date) {
        return new Promise((resolve, reject) => {
          sbc.db.transaction(function (tx) {
            tx.executeSql("update config set collectiondate=?", [date], function (tx, res) {
              resolve(true);
            }, function (tx, err) {
              reject(err);
            });
          }, function (err) {
            reject(err);
          });
        });
      },
      continueDownload: function (serveraddr) {
        api.post(serveraddr + "/sbcmobilev2/admin", { id: md5("getCollectorsList") }, { headers: sbc.reqheader })
          .then(res => {
            if (res.data.collectors.length > 0) {
              sbc.globalFunc.lookupTableSelect = false;
              sbc.globalFunc.lookupCols = [
                { name: "clientname", label: "Name", align: "left", field: "clientname", sortable: true }
              ];
              sbc.modulefunc.lookupTableFilter = { type: "filter", field: "", label: "Search Collector", func: "" };
              sbc.lookupTitle = "Select Collector";
              sbc.showLookup = true;
              sbc.globalFunc.lookupAction = "collectorsLookup";
              sbc.globalFunc.lookupData = res.data.collectors;
              $q.loading.hide();
            } else {
              cfunc.showMsgBox("Collectors list empty", "negative", "warning");
              $q.loading.hide();
            }
          })
          .catch(err => {
            cfunc.showMsgBox(err.message, "negative", "warning");
            $q.loading.hide();
          });
      },
      selectCollector: function (collector) {
        sbc.db.transaction(function (tx) {
          cfunc.getTableData("config", "collectorid").then(collectorid => {
            if (collectorid !== "" && collectorid !== null && typeof(collectorid) !== "undefined") {
              if (collectorid === collector.clientid) {
                $q.dialog({
                  message: "Data for this collector already downloaded do you want to overwrite?",
                  ok: { flat: true, color: "primary", label: "Yes" },
                  cancel: { flat: true, color: "negative", label: "No" }
                }).onOk(() => {
                  sbc.modulefunc.clearCollectorsTables().then(res => {
                    sbc.modulefunc.saveCollectorInfo(collector);
                    sbc.modulefunc.downloadCollectorsInfo(collector);
                  }).catch(err => {
                    cfunc.showMsgBox(err.message, "negative", "warning");
                    $q.loading.hide();
                  });
                });
              } else {
                cfunc.modulefunc.clearCollectorsTables().then(res => {
                  sbc.modulefunc.saveCollectorInfo(collector);
                  sbc.modulefunc.downloadCollectorsInfo(collector);
                }).catch(err => {
                  cfunc.showMsgBox(err.message, "negative", "warning");
                  $q.loading.hide();
                });
              }
            } else {
              sbc.modulefunc.clearCollectorsTables().then(res => {
                sbc.modulefunc.saveCollectorInfo(collector);
                sbc.modulefunc.downloadCollectorsInfo(collector);
              }).catch(err => {
                cfunc.showMsgBox(err.message, "negative", "warning");
                $q.loading.hide();
              });
            }
          });
        });
      },
      clearCollectorsTables: function () {
        return new Promise((resolve, reject) => {
          sbc.db.transaction(function (tx) {
            tx.executeSql("delete from clientarea");
            tx.executeSql("delete from client");
            resolve(true);
          }, function (err) {
            reject(err);
          });
        });
      },
      saveCollectorInfo: function (collector) {
        sbc.db.transaction(function (tx) {
          tx.executeSql("update config set collectorid=?, username=?, password=?", [collector.clientid, collector.username, collector.password]);
        });
      },
      downloadCollectorsInfo: function (collector) {
        cfunc.showLoading();
        sbc.modulefunc.getLocalCollectionDate().then(localColDate => {
          sbc.modulefunc.getCenter().then(center => {
            cfunc.getTableData("config", "serveraddr").then(serveraddr => {
              if (serveraddr === "" || serveraddr === null || typeof(serveraddr) === "undefined") {
                cfunc.showMsgBox("Server address not set", "negative", "warning");
                $q.loading.hide();
              } else {
                cfunc.showLoading("Downloading Collectors data...");
                api.post(serveraddr + "/sbcmobilev2/admin", { id: md5("getCollectorsInfo"), clientid: collector.clientid, coldate: localColDate, center: center }, { headers: sbc.reqheader })
                  .then(res => {
                    sbc.modulefunc.saveCollectorArea(res.data.colarea).then(res2 => {
                      sbc.modulefunc.saveCollectorTenants(res.data.tenants).then(res3 => {
                        sbc.modulefunc.getAdvanceRent().then(rres => {
                          sbc.modulefunc.getAdvanceCusa().then(cres => {
                            cfunc.showLoading("Successfully imported Tenants");
                            setTimeout(function () {
                              cfunc.showMsgBox("Collectors data successfully downloaded", "positive");
                              sbc.showLookup = false;
                              $q.loading.hide();
                            }, 1500);
                          });
                        });
                      }).catch(err => {
                        cfunc.showMsgBox(err, "negative", "warning");
                        $q.loading.hide();
                      });
                    }).catch(err => {
                      cfunc.showMsgBox(err, "negative", "warning");
                      $q.loading.hide();
                    });
                  })
                  .catch(err => {
                    cfunc.showMsgBox(err.message, "negative", "warning");
                    $q.loading.hide();
                  });
              }
            });
          });
        });
      },
      saveCollectorArea: function (data) {
        return new Promise((resolve, reject) => {
          if (data.length > 0) {
            var d = data;
            var dd = [];
            sbc.modulefunc.colAreaCount = data.length;
            while (d.length) dd.push(d.splice(0, 100));
            cfunc.showLoading();
            save(dd, 0);
          } else {
            reject("No Collector Area to save");
          }

          function save(colarea, index) {
            cfunc.showLoading("Saving Collector Area (Batch " + index + " of " + colarea.length + ")");
            if (index === 0) $q.loading.hide();
            if (index === colarea.length) {
              cfunc.showLoading("Successfully imported " + sbc.modulefunc.colAreaCount + " Collection Areas");
              setTimeout(function () {
                resolve("done");
                $q.loading.hide();
              }, 1500);
            } else {
              sbc.db.transaction(function (tx) {
                for (var a in colarea[index]) {
                  insertColArea(colarea[index][a]);
                  if (parseInt(a) + 1 === colarea[index].length) save(colarea, parseInt(index) + 1);
                }
              });
            }
          }
        });

        function insertColArea (data) {
          sbc.db.transaction(function (tx) {
            tx.executeSql("insert into clientarea(clientid, phase, section, sectionname, center) values(?, ?, ?, ?, ?)", [data.clientid, data.phase, data.section, data.sectionname, data.center], function (tx, res) {
              console.log("insertColData: ", data);
            }, function (tx, err) {
              cfunc.saveErrLog("insert into clientarea(clientid, phase, section, sectionname, center) values(?, ?, ?, ?, ?)", [data.clientid, data.phase, data.section, data.sectionname, data.center], err.message);
            });
          });
        }
      },
      saveCollectorTenants: function (data) {
        return new Promise((resolve, reject) => {
          if (data.length > 0) {
            var d = data;
            var dd = [];
            sbc.modulefunc.colTenantsCount = data.length;
            while (d.length) dd.push(d.splice(0, 100));
            save(dd, 0);
          } else {
            reject("No Tenants to save");
          }

          function save(tenants, index) {
            cfunc.showLoading("Saving Tenants (Batch " + index + " of " + tenants.length + ")");
            if (index === 0) $q.loading.hide();
            if (index === tenants.length) {
              resolve("done");
            } else {
              sbc.db.transaction(function (tx) {
                for (var a in tenants[index]) {
                  insertTenant(tenants[index][a]);
                  if (parseInt(a) + 1 === tenants[index].length) save(tenants, parseInt(index) + 1);
                }
              });
            }
          }
        });

        function insertTenant(data) {
          sbc.db.transaction(function (tx) {
            tx.executeSql("insert into client(clientid, client, clientname, loc, dailyrent, dcusa, rentdue, outar, outcusa, cusadue, center, outelec, outwater, phase, section, erate, wrate, ebeginning, wbeginning, last_ebeginning, last_eending, last_erate, last_wbeginning, last_wending, last_wrate, noRent, noCusa) values(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [data.clientid, data.client, data.clientname, data.loc, data.dailyrent, data.dailycusa, "rentdue", data.outarrent, data.outcusa, "cusadue", data.center, data.outarelec, data.outarwater, data.phase, data.section, data.erate, data.wrate, data.ebeginning, data.wbeginning, data.last_ebeginning, data.last_eending, data.last_erate, data.last_wbeginning, data.last_wending, data.last_wrate, data.norent, data.nocusa], function (tx, res) {
            }, function (tx, err) {
              cfunc.saveErrLog("insert into client(clientid, client, clientname, loc, dailyrent, dcusa, rentdue, outar, outcusa, cusadue, center, outelec, outwater, phase, section, erate, wrate, ebeginning, wbeginning, last_ebeginning, last_eending, last_erate, last_wbeginning, last_wending, last_wrate, noRent, noCusa) values(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [data.clientid, data.client, data.clientname, data.loc, data.dailyrent, data.dailycusa, "rentdue", data.outarrent, data.outcusa, "cusadue", data.center, data.outarelec, data.outarwater, data.phase, data.section, data.erate, data.wrate, data.ebeginning, data.wbeginning, data.last_ebeginning, data.last_eending, data.last_erate, data.last_wbeginning, data.last_wending, data.last_wrate, data.norent, data.nocusa], err.message);
            });
          });
        }
      },
      getAdvanceRent: function () {
        return new Promise((resolve) => {
          let collectiondate = "";
          let collectorid = "";
          const datenow = cfunc.getDateTime("datetime");
          cfunc.getTableData("config", ["collectiondate", "collectorid"], true).then(res1 => {
            collectiondate = res1.collectiondate;
            collectorid = res1.collectorid;
            sbc.db.transaction(function (tx) {
              tx.executeSql("select clientid, dailyrent, center, phase, section, outar from client where outar<0", [], function (tx, res) {
                if (res.rows.length > 0) {
                  let rdata = [];
                  let isnegative = 0;
                  for (var x = 0; x < res.rows.length; x++) {
                    if ((Math.abs(res.rows.item(x).outar) - res.rows.item(x).dailyrent) > 0) rdata.push(res.rows.item(x));
                    if ((parseInt(x) + 1) === res.rows.length) {
                      let d = rdata;
                      let dd = [];
                      while (d.length) dd.push(d.splice(0, 100));
                      save(dd, 0);
                    }
                  }
                } else {
                  resolve("done");
                }
              });
            });
          });

          function save (rd, index) {
            cfunc.showLoading("Saving advance rent payments (Batch " + index + " of " + rd.length + ")");
            if (index === 0) $q.loading.hide();
            if (index === rd.length) {
              resolve("done");
            } else {
              sbc.db.transaction(function (tx) {
                for (var a in rd[index]) {
                  insertRent(rd[index][a]);
                  if (parseInt(a) + 1 === rd[index].length) save(rd, parseInt(index) + 1);
                }
              });
            }
          }
        });

        function insertRent (data) {
          sbc.db.transaction(function (tx) {
            const qry = "insert into dailycollection(clientid, amount, status, dateid, center, type, collectorid, transtime, isNegative, phase, section) values(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            const qrydata = [data.clientid, data.dailyrent, "OP", collectiondate, data.center, "R", collectorid, datenow, 1, data.phase, data.section];
            tx.executeSql(qry, qrydata, function (tx, res) {
              console.log("advance rent payment saved: ", data.clientid);
            }, function (tx, err) {
              cfunc.saveErrLog(qry, qrydata, err.message);
            });
          });
        }
      },
      getAdvanceCusa: function () {
        return new Promise((resolve) => {
          let collectiondate = "";
          let collectorid = "";
          const datenow = cfunc.getDateTime("datetime");
          cfunc.getTableData("config", ["collectiondate", "collectorid"], true).then(res1 => {
            collectiondate = res1.collectiondate;
            collectorid = res1.collectorid;
            sbc.db.transaction(function (tx) {
              tx.executeSql("select clientid, dcusa, center, phase, section, outcusa from client where outcusa<0", [], function (tx, res) {
                if (res.rows.length > 0) {
                  let cdata = [];
                  for (var x = 0; x < res.rows.length; x++) {
                    if ((Math.abs(res.rows.item(x).outcusa) - res.rows.item(x).dcusa) > 0) cdata.push(res.rows.item(x));
                    if (parseInt(x) + 1 === res.rows.length) {
                      let d = cdata;
                      let dd = [];
                      while (d.length) dd.push(d.splice(0, 100));
                      save(dd, 0);
                    }
                  }
                } else {
                  resolve("done");
                }
              });
            });
          });

          function save (cd, index) {
            cfunc.showLoading("Saving advance cusa payments (Batch " + index + " of " + cd.length + ")");
            if (index === 0) $q.loading.hide();
            if (index === cd.length) {
              resolve("done");
            } else {
              sbc.db.transaction(function (tx) {
                for (var a in cd[index]) {
                  insertCusa(cd[index][a]);
                  if (parseInt(a) + 1 === cd[index].length) save(cd, parseInt(index) + 1);
                }
              });
            }
          }
        });
        function insertCusa(data) {
          sbc.db.transaction(function (tx) {
            const qry = "insert into dailycollection(clientid, amount, status, dateid, center, type, collectorid, transtime, isNegative, phase, section) values(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
            const qrydata = [data.clientid, data.dcusa, "OP", collectiondate, data.center, "C", collectorid, datenow, 1, data.phase, data.section];
            tx.executeSql(qry, qrydata, function (tx, res) {
              console.log("advance cusa payment saved: ", data.clientid);
            }, function (tx, err) {
              cfunc.saveErrLog(qry, qrydata, err.message);
            });
          });
        }
      },
      selectOperationType: function (data) {
        cfunc.showLoading();
        sbc.db.transaction(function (tx) {
          tx.executeSql("update config set operationtype=?", [data.type], function (tx, res) {
            cfunc.showMsgBox("Operation type updated", "positive");
            sbc.showLookup = false;
            $q.loading.hide();
          }, function (tx, err) {
            cfunc.showMsgBox(err.message, "negative", "warning");
            $q.loading.hide();
          });
        });
      },
      selectPrintType: function (data) {
        cfunc.showLoading();
        sbc.db.transaction(function (tx) {
          tx.executeSql("update config set printtype=?", [data.data], function (tx, res) {
            cfunc.showMsgBox("Print Type updated.", "positive");
            sbc.showLookup = false;
            $q.loading.hide();
          }, function (tx, err) {
            cfunc.showMsgBox(err.message, "negative", "warning");
            $q.loading.hide();
          });
        });
      },
      selectTransType: function (data) {
        cfunc.showLoading();
        let clientAreas = [];
        sbc.db.transaction(function (tx) {
          tx.executeSql("select * from clientarea", [], function (tx, resss) {
            let waw = [];
            for (var w = 0; w < resss.rows.length; w++) {
              waw.push(resss.rows.item(w));
            }
            console.log("wawssssssss: ", waw);
          })
          tx.executeSql("select clientid, phase, section from clientarea", [], function (tx, areaRes) {
            if (areaRes.rows.length > 0) {
              for (var x = 0; x < areaRes.rows.length; x++) {
                sbc.modulefunc.getCollectionStatus(areaRes.rows.item(x).phase, areaRes.rows.item(x).section).then(csRes => {
                  clientAreas.push(csRes);
                });
                if (parseInt(x) + 1 === areaRes.rows.length) {
                  let msg = "";
                  if (clientAreas.length > 0) {
                    for (var c in clientAreas) {
                      msg += "<span>" + clientAreas[c].area + "</span>";
                      switch (data.type) {
                        case "Rent": msg += "Rent (" + clientAreas[c].rent + "/" + clientAreas[c].totalRCount + ")"; break;
                        case "CUSA": msg += "CUSA (" + clientAreas[c].cusa + "/" + clientAreas[c].totalCCount + ")"; break;
                        case "Electricity": msg += "Electricity (" + clientAreas[c].elec + ")"; break;
                        case "Water": msg += "Water (" + clientAreas[c].water + ")"; break;
                        case "Others": msg += "Others (" + clientAreas[c].others + ")"; break;
                        case "Ambulant": msg += "Ambulant (" + clientAreas[c].amb + ")"; break;
                        case "Electric Reading": msg += "Electric Reading (" + clientAreas[c].readelec + ")"; break;
                        case "Water Reading": msg += "Water Reading (" + clientAreas[c].readwater + ")"; break;
                      }
                      if (parseInt(c) + 1 === clientAreas.length) {
                        $q.loading.hide();
                        $q.dialog({
                          title: "Collection Status",
                          message: msg,
                          html: true,
                          ok: { flat: true, color: "primary" },
                          cancel: { flat: true, color: "negative" }
                        }).onOk(() => {
                          sbc.modulefunc.uploadTrans(data.type, clientAreas);
                        });
                      }
                    }
                  } else {
                    cfunc.showMsgBox("Client Area list empty", "negative", "warning");
                    $q.loading.hide();
                  }
                }
              }
            } else {
              cfunc.showMsgBox("Client Area list empty", "negative", "warning");
              $q.loading.hide();
            }
          }, function (tx, err) {
            cfunc.showMsgBox(err.message, "negative", "warning");
            console.log("selectTranstype error: ", err.message);
            $q.loading.hide();
          });
        });
      },
      getCollectionStatus: function (phase, section) {
        return new Promise((resolve, reject) => {
          sbc.db.transaction(function (tx) {
            let data = { area: phase + "-" + section, totalRCount: 0, totalCCount: 0, rent: 0, cusa: 0, elec: 0, water: 0, others: 0, amb: 0, readelec: 0, readwater: 0 };
            tx.executeSql("select clientid, dailyrent, dcusa from client where phase=? and section=?", [phase, section], function (tx, res) {
              if (res.rows.length > 0) {
                data.rent = 0;
                for (var x = 0; x < res.rows.length; x++) {
                  // Rent
                  if (res.rows.item(x).dailyrent > 0) data.totalRCount += 1;
                  tx.executeSql("select line from dailycollection where type=? and clientid=?", ["R", res.rows.item(x).clientid], function (tx, rentRes) {
                    if (rentRes.rows.length > 0) data.rent += 1;
                  });
                  // CUSA
                  if (res.rows.item(x).dcusa > 0) data.totalCCount += 1;
                  tx.executeSql("select line from dailycollection where type=? and clientid=?", ["C", res.rows.item(x).clientid], function (tx, cusaRes) {
                    if (cusaRes.rows.length > 0) data.cusa += 1;
                  });
                  // Elec
                  tx.executeSql("select line from dailycollection where type=? and clientid=?", ["E", res.rows.item(x).clientid], function (tx, elecRes) {
                    if (elecRes.rows.length > 0) data.elec += 1;
                  });
                  // Water
                  tx.executeSql("select line from dailycollection where type=? and clientid=?", ["W", res.rows.item(x).clientid], function (tx, waterRes) {
                    if (waterRes.rows.length > 0) data.water += 1;
                  });
                  // Others
                  tx.executeSql("select line from dailycollection where type=? and clientid=?", ["O", res.rows.item(x).clientid], function (tx, othersRes) {
                    if (othersRes.rows.length > 0) data.others += 1;
                  });
                  // AMB
                  tx.executeSql("select line from dailycollection where type=? and clientid=?", ["AMB", res.rows.item(x).clientid], function (tx, ambRes) {
                    if (ambRes.rows.length > 0) data.amb += 1;
                  });
                  // Electric Reading
                  tx.executeSql("select line from reading where type=? and clientid=?", ["E", res.rows.item(x).clientid], function (tx, readElecRes) {
                    if (readElecRes.rows.length > 0) data.readelec += 1;
                  });
                  // Water Reading
                  tx.executeSql("select line from reading where type=? and clientid=?", ["W", res.rows.item(x).clientid], function (Tx, readWaterRes) {
                    if (readWaterRes.rows.length > 0) data.readwater += 1;
                  });
                }
                resolve(data);
              } else {
                resolve(data);
              }
            }, function (tx, err) {
              reject(err);
            });
          }, function (err) {
            reject(err);
          });
        });
      },
      uploadTrans: function (selTranstype, clientAreas) {
        cfunc.showLoading();
        let a = 0;
        for (a in clientAreas) {
          switch (selTranstype) {
            case "Rent":
              if (clientAreas[a].rent < clientAreas[a].totalRCount) {
                cfunc.showMsgBox("Collection not yet done", "negative", "warning");
                $q.loading.hide();
              }
              break;
            case "CUSA":
              if (clientAreas[a].cusa < clientAreas[a].totalCCount) {
                cfunc.showMsgBox("Collection not yet done", "negative", "warning");
                $q.loading.hide();
              }
              break;
          }
        }
        let transtype;
        switch (selTranstype) {
          case "Rent": transtype = "R"; break;
          case "CUSA": transtype = "C"; break;
          case "Electricity": transtype = "E"; break;
          case "Water": transtype = "W"; break;
          case "Others": transtype = "O"; break;
          case "Ambulant": transtype = "AMB"; break;
          case "Electric Reading": transtype = "E"; break;
          case "Water Reading": transtype = "W"; break;
        }
        sbc.db.transaction(function (tx) {
          if (selTranstype === "Electric Reading" || selTranstype === "Water Reading") {
            tx.executeSql("select r.line, r.clientid, r.beginning, r.ending, r.consumption, r.rate, c.phase, c.section, c.center, r.dateid, r.remarks, r.collectorid, r.type from reading as r left join client as c on c.clientid=r.clientid where r.type=?", [transtype], function (tx, res) {
              if (res.rows.length > 0) {
                let readings = [];
                for (var y = 0; y < res.rows.length; y++) {
                  readings.push(res.rows.item(y));
                  if ((parseInt(y) + 1) === res.rows.length) {
                    cfunc.getTableData("config", ["username", "stationname", "serveraddr"], true).then(configdata => {
                      api.post(configdata.serveraddr + "/sbcmobilev2/admin", { id: md5("uploadDailyReading"), data: readings, collectorname: configdata.username, stationname: configdata.stationname }, { headers: sbc.reqheader })
                        .then(upRes => {
                          if (upRes.data.status) {
                            sbc.modulefunc.saveHReading(readings);
                            cfunc.showMsgBox(upRes.data.msg, "positive");
                          } else {
                            cfunc.showMsgBox(upRes.data.msg, "negative", "warning");
                            $q.loading.hide();
                          }
                        })
                        .catch(err => {
                          cfunc.showMsgBox(err.message, "negative", "warning");
                          $q.loading.hide();
                        });
                    });
                  }
                }
              } else {
                cfunc.showMsgBox("Nothing to upload", "negative", "warning");
                $q.loading.hide();
              }
            }, function (tx, err) {
              cfunc.showMsgBox(err.message, "negative", "warning");
              $q.loading.hide();
            });
          } else {
            tx.executeSql("select d.line, d.clientid, d.amount, d.status, d.dateid, d.center, d.type, d.remarks, d.collectorid, d.isNegative, d.transtime, d.phase, d.section, c.outar, c.outcusa, c.outelec, c.outwater from dailycollection as d left join client as c on c.clientid=d.clientid where d.type=?", [transtype], function (tx, res) {
              if (res.rows.length > 0) {
                let collections = [];
                for (var x = 0; x < res.rows.length; x++) {
                  collections.push(res.rows.item(x));
                  if (parseInt(x) + 1 === res.rows.length) {
                    cfunc.getTableData("config", ["username", "stationname", "serveraddr"], true).then(configdata => {
                      api.post(configdata.serveraddr + "/sbcmobilev2/admin", { id: md5("uploadDailyCollection"), data: collections, collectorname: configdata.username, stationname: configdata.stationname }, { headers: sbc.reqheader })
                        .then(upRes => {
                          if (upRes.data.status) {
                            sbc.modulefunc.saveHDailyCollection(collections);
                            cfunc.showMsgBox(upRes.data.msg, "positive");
                          } else {
                            cfunc.showMsgBox(upRes.data.msg, "negative", "warning");
                            $q.loading.hide();
                          }
                        })
                        .catch(err => {
                          cfunc.showMsgBox(err.message, "negative", "warning");
                          $q.loading.hide();
                        });
                    });
                  }
                }
              } else {
                cfunc.showMsgBox("Nothing to upload", "negative", "warning");
                $q.loading.hide();
              }
            }, function (tx, err) {
              cfunc.showMsgBox(err.message, "negative", "warning");
              $q.loading.hide();
            });
          }
        }, function (err) {
          cfunc.showMsgBox(err.message, "negative", "warning");
          $q.loading.hide();
        });
      },
      saveHReading: function (data) {
        if (data.length > 0) {
          let qry = "insert into hreading(line, beginning, ending, consumption, rate, clientid, dateid, remarks, type, collectorid) values(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
          let qrydata = [];
          for (var d in data) {
            qrydata = [data[d].line, data[d].beginning, data[d].ending, data[d].consumption, data[d].rate, data[d].clientid, data[d].dateid, data[d].remarks, data[d].type, data[d].collectorid];
            saveData(qry, qrydata);
            if (parseInt(d) + 1 === data.length) {
              $q.loading.hide();
            }
          }

          function saveData (qry, qrydata) {
            sbc.db.transaction(function (tx) {
              tx.executeSql(qry, qrydata, function (tx, res) {
                console.log("hreading saved");
                tx.executeSql("delete from reading where line=?", [qrydata[0]]);
              })
            }, function (tx, err) {
              cfunc.saveErrLog(qry, qrydata, err.message);
            });
          }
        }
      },
      saveHDailyCollection: function (data) {
        if (data.length > 0) {
          let qry = "insert into hdailycollection(line, clientid, amount, status, dateid, center, type, remarks, collectorid, isNegative, transtime, phase, section) values(?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
          let qrydata = [];
          for (var d in data) {
            qrydata = [data[d].line, data[d].clientid, data[d].amount, data[d].status, data[d].dateid, data[d].center, data[d].type, data[d].remarks, data[d].collectorid, data[d].isNegative, data[d].transtime, data[d].phase, data[d].section];
            saveData(qry, qrydata);
            if (parseInt(d) + 1 === data.length) {
              $q.loading.hide();
            }
          }
          function saveData(qry, qrydata) {
            sbc.db.transaction(function (tx) {
              tx.executeSql(qry, qrydata, function (tx, res) {
                console.log("hdailycollection saved");
                tx.executeSql("delete from dailycollection where line=?", [qrydata[0]]);
              }, function (tx, err) {
                cfunc.saveErrLog(qry, qrydata, err.message);
              });
            });
          }
        }
      },
      reprintTransread: function () {},
      cancelreprintTransRead: function () {
        sbc.showInputLookup = false;
      }
    })';
  }
}