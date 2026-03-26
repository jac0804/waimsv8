<?php
namespace App\Http\Classes\mobile\modules\ordering;

use App\Http\Classes\builder\mobiletxtFieldClass;
use App\Http\Classes\builder\mobileButtonClass;

class orders {
  private $fieldClass;
  private $buttonClass;
  private $company;

  public function __construct() {
    $this->fieldClass = new mobiletxtFieldClass;
    $this->buttonClass = new mobileButtonClass;
    $this->company = env('COMPANY', 'sbc');
  }
  public function getLayout() {
    $fields = ['orderno', 'clientname', 'addr', 'dateid', 'itemcount', 'total'];
    if($this->company == 'sbc') array_push($fields, 'rem', 'paytype', 'payment', 'change');
    $tableCols = $this->fieldClass->create($fields);
    if($this->company == 'sbc') {
      data_set($tableCols, 'rem.type', 'label');
      data_set($tableCols, 'rem.label', 'Notes');
      data_set($tableCols, 'rem.field', 'rem');
      data_set($tableCols, 'rem.align', 'left');
      data_set($tableCols, 'rem.sortable', 'false');
      data_set($tableCols, 'paytype.type', 'label');
      data_set($tableCols, 'paytype.field', 'transtype');
      data_set($tableCols, 'paytype.align', 'left');
      data_set($tableCols, 'paytype.sortable', 'false');
      data_set($tableCols, 'payment.type', 'label');
      data_set($tableCols, 'payment.field', 'tendered');
      data_set($tableCols, 'payment.align', 'left');
      data_set($tableCols, 'payment.sortable', 'false');
      data_set($tableCols, 'change.type', 'label');
      data_set($tableCols, 'change.field', 'change');
      data_set($tableCols, 'change.align', 'left');
      data_set($tableCols, 'change.sortable', 'false');
    }
    data_set($tableCols, 'clientname.label', 'Customer');
    data_set($tableCols, 'clientname.type', 'label');
    data_set($tableCols, 'clientname.field', 'clientname');
    data_set($tableCols, 'addr.type', 'label');
    data_set($tableCols, 'addr.field', 'addr');
    data_set($tableCols, 'dateid.type', 'label');
    data_set($tableCols, 'dateid.field', 'dateid');
    data_set($tableCols, 'itemcount.label', 'Item Count');
    data_set($tableCols, 'total.type', 'label');
    data_set($tableCols, 'total.field', 'total');

    $btns = ['editorder', 'deleteorder', 'vieworder', 'printorder'];
    $cfTableButtons = $this->buttonClass->create($btns);

    $cLookupTableCols = [];
    $fields = ['itemname', 'qty', 'uom', 'amt', 'disc', 'total', 'rem', 'barcode'];
    $orderInfoTableCols = $this->fieldClass->create($fields);
    data_set($orderInfoTableCols, 'barcode.type', 'label');
    data_set($orderInfoTableCols, 'barcode.field', 'barcode');
    data_set($orderInfoTableCols, 'itemname.type', 'label');
    data_set($orderInfoTableCols, 'itemname.field', 'itemname');
    data_set($orderInfoTableCols, 'amt.type', 'label');
    data_set($orderInfoTableCols, 'amt.field', 'amt');
    data_set($orderInfoTableCols, 'total.type', 'label');
    data_set($orderInfoTableCols, 'total.field', 'total');
    data_set($orderInfoTableCols, 'uom.type', 'label');
    data_set($orderInfoTableCols, 'uom.field', 'uom');
    data_set($orderInfoTableCols, 'uom.align', 'left');
    data_set($orderInfoTableCols, 'uom.sortable', 'false');
    array_push($cLookupTableCols, ['form'=>'orderInfoTableCols', 'fields'=>$orderInfoTableCols]);

    $cLookupFooterFields = [];
    $fields = ['itemcount', 'total'];
    $orderInfoFooterFields = $this->fieldClass->create($fields);
    data_set($orderInfoFooterFields, 'total.type', 'label');
    data_set($orderInfoFooterFields, 'total.label', 'Total: ');
    array_push($cLookupFooterFields, ['form'=>'orderInfoFooterFields', 'fields'=>$orderInfoFooterFields]);

    $cLookupButtons = [];
    $btns = ['close'];
    $orderInfoButtons = $this->buttonClass->create($btns);
    array_push($cLookupButtons, ['form'=>'orderInfoButtons', 'btns'=>$orderInfoButtons]);

    return ['cfTableCols'=>$tableCols, 'cfTableButtons'=>$cfTableButtons, 'cLookupTableCols'=>$cLookupTableCols, 'cLookupButtons'=>$cLookupButtons, 'cLookupFooterFields'=>$cLookupFooterFields];
  }

  public function getFunc() {
    return '({
      docForm: { searchitem: "", itemcount: "", total: "" },
      tableData: [],
      tableGrid: true,
      lookupTableData: [],
      cLookupForm: [],
      orders: [],
      orderStocks: [],
      hasTablePagination: false,
      paginationData: { label: "Items", pageNum: 1, totalPage: 0, maxPages: 3, color: "primary", page: 1, rowsPerPage: 0, lastItem: 0 },
      loadTableData: function () {
        console.log("loadTableData called");
        const storage = $q.localStorage.getItem("sbcmobilev2Data");
        console.log("waw");
        cfunc.showLoading();
        sbc.modulefunc.tableData = [];
        sbc.db.transaction(function (tx) {
          tx.executeSql("select transhead.*, customers.clientname as clientname, customers.addr, customers.tel as tel from transhead left join customers on customers.client=transhead.client where transhead.userid=?", [storage.user.userid], function (tx, res) {
            if (res.rows.length > 0) {
              for (var x = 0; x < res.rows.length; x++) {
                sbc.modulefunc.tableData.push(res.rows.item(x));
                sbc.modulefunc.tableData[x].total = sbc.numeral(sbc.modulefunc.tableData[x].total).format("0,0.00");
                sbc.modulefunc.tableData[x].tendered = sbc.numeral(sbc.modulefunc.tableData[x].tendered).format("0,0.00");
                sbc.modulefunc.tableData[x].change = sbc.numeral(sbc.modulefunc.tableData[x].change).format("0,0.00");
              }
            }
            for (var b in sbc.footerbuttons) {
              sbc.footerbuttons[b].show = "true";
              if (sbc.footerbuttons[b].name === "cart") sbc.footerbuttons[b].show = "false";
            }
            $q.loading.hide();
          }, function (tx, err) {
            cfunc.showMsgBox(err.message, "negative", "warning");
            $q.loading.hide();
          });
        });
      },
      editOrder: function (order, index) {
        console.log("editOrder");
        const thiss = this;
        $q.dialog({
          message: "Do you want to edit this order?",
          ok: { flat: true, color: "primary" },
          cancel: { flat: true, color: "negative" }
        }).onOk(() => {
          sbc.db.transaction(function (tx) {
            if (sbc.globalFunc.company === "sbc") {
              tx.executeSql("select count(*) as count from cart", [], function (tx, res) {
                if (res.rows.item(0).count > 0) {
                  $q.dialog({
                    message: "Cart is not empty, Do you want to continue?",
                    ok: { flat: true, color: "primary" },
                    cancel: { flat: true, color: "negative" }
                  }).onOk(() => {
                    thiss.transferOrder(order);
                  });
                } else {
                  thiss.transferOrder(order);
                }
              }, function (tx, err) {
                cfunc.showMsgBox(err.message, "negative", "warning");
              });
            } else {
              tx.executeSql("select * from item where (qty <> 0 and qty <> ?)", ["0"], function (tx, res) {
                if (res.rows.length > 0) {
                  $q.dialog({
                    message: "Cart is not empty, Do you want to continue?",
                    ok: { flat: true, color: "primary" },
                    cancel: { flat: true, color: "negative" }
                  }).onOk(() => {
                    thiss.transferOrder(order);
                  });
                } else {
                  thiss.transferOrder(order);
                }
              }, function (tx, err) {
                cfunc.showMsgBox(err.message, "negative", "warning");
              });
            }
          });
        });
      },
      deleteOrder: function (order, index) {
        console.log("deleteOrder");
        const thiss = this;
        $q.dialog({
          message: "Do you want to delete this order?",
          ok: { flat: true, color: "primary" },
          cancel: { flat: true, color: "negative" }
        }).onOk(() => {
          thiss.deleteOrder2(order);
        });
      },
      deleteOrder2: function (order) {
        const thiss = this;
        cfunc.showLoading();
        sbc.db.transaction(function (tx) {
          tx.executeSql("delete from transhead where orderno=?", [order.orderno], function (tx, res) {
            if (sbc.globalFunc.company === "sbc") {
              tx.executeSql("select ifnull(itemstat.qty,0) as itembal, transstock.iss, item.itemid from transstock left join item on item.barcode=transstock.barcode left join itemstat on itemstat.itemid=item.itemid where transstock.orderno=?", [order.orderno], function (tx, res) {
                if (res.rows.length > 0) {
                  let itembal;
                  for (var x = 0; x < res.rows.length; x++) {
                    itembal = sbc.numeral(sbc.numeral(res.rows.item(x).itembal).value() + sbc.numeral(res.rows.item(x).iss).value()).value();
                    tx.executeSql("update itemstat set qty=" + itembal + " where itemid=" + res.rows.item(x).itemid, [], function (tx, res2) {
                      console.log("itembal updated");
                      if (parseInt(x) === res.rows.length) {
                        tx.executeSql("delete from transstock where orderno=?", [order.orderno]);
                        sbc.modulefunc.resetOrderno(order.orderno);
                        cfunc.showMsgBox("Order deleted", "positive");
                        $q.loading.hide();
                        sbc.modulefunc.loadTableData();
                      }
                    }, function (tx, err) {
                      console.log("error updating itembal: ", err.message);
                      if (parseInt(x) + 1 === res.rows.length) {
                        tx.executeSql("delete from transstock where orderno=?", [order.orderno]);
                        sbc.modulefunc.resetOrderno(order.orderno);
                        cfunc.showMsgBox("Order deleted", "positive");
                        $q.loading.hide();
                        sbc.modulefunc.loadTableData();
                      }
                    });
                  }
                } else {
                  cfunc.showMsgBox("Order deleted", "positive");
                  tx.executeSql("delete from transstock where orderno=?", [order.orderno]);
                  $q.loading.hide();
                  sbc.modulefunc.loadTableData();
                }
              });
            } else {
              tx.executeSql("delete from transstock where orderno=?", [order.orderno], function (tx, res) {
                cfunc.showMsgBox("Order deleted", "positive");
                $q.loading.hide();
                thiss.loadTableData();
              }, function (tx, err) {
                cfunc.showMsgBox(err.message, "negative", "warning");
                $q.loading.hide();
              });
            }
          }, function (tx, err) {
            cfunc.showMsgBox(err.message, "negative", "warning");
            $q.loading.hide();
          });
        }, function (err) {
          console.log("deleteOrder error: ", err.message);
        });
      },
      closeDialog: function () {
        sbc.showCustomLookup = false;
      },
      viewOrder: function (order, index) {
        console.log("viewOrder");
        const thiss = this;
        sbc.selclookupheadfields = [];
        sbc.selclookupheadfieldsplot = [];
        sbc.selclookuptablecols = sbc.globalFunc.getLookupForm(sbc.clookuptablecols, "orderInfoTableCols", "inputFields");
        sbc.selclookuptablecolsplot = sbc.globalFunc.getLookupForm(sbc.clookuptablecolsplot, "orderInfoTableCols", "inputPlot");
        sbc.selclookupfooterfields = sbc.globalFunc.getLookupForm(sbc.clookupfooterfields, "orderInfoFooterFields", "inputFields");
        sbc.selclookupfooterfieldsplot = sbc.globalFunc.getLookupForm(sbc.clookupfooterfieldsplot, "orderInfoFooterFields", "inputPlot");
        sbc.selclookuptablebuttons = [];
        // sbc.selclookupbuttons = sbc.globalFunc.getLookupForm(sbc.clookupbuttons, "orderInfoButtons", "buttons");
        sbc.selclookupbuttons = [];
        thiss.cLookupForm = thiss.docForm;
        sbc.cLookupTitle = order.orderno;
        thiss.lookupTableData = [];
        sbc.showCustomLookup = true;
        cfunc.showLoading();
        console.log("order: ", order);
        sbc.db.transaction(function (tx) {
          tx.executeSql("select * from transstock where orderno=?", [order.orderno], function (tx, res) {
            let gtotal = 0;
            thiss.docForm.itemcount = res.rows.length;
            if (res.rows.length > 0) {
              for (var x = 0; x < res.rows.length; x++) {
                gtotal += sbc.numeral(res.rows.item(x).total).value();
                thiss.lookupTableData.push(res.rows.item(x));
                thiss.lookupTableData[x].qty = sbc.numeral(thiss.lookupTableData[x].isqty).format("0,000");
                thiss.lookupTableData[x].amt = sbc.numeral(thiss.lookupTableData[x].amt).format("0,0.00");
                thiss.lookupTableData[x].total = sbc.numeral(thiss.lookupTableData[x].total).format("0,0.00");
              }
              $q.loading.hide();
              thiss.docForm.total = sbc.numeral(gtotal).format("0,0.00");
            }
          }, function (tx, err) {
            cfunc.showMsgBox(err.message, "negative", "warning");
            $q.loading.hide();
          });
        });
      },
      transferOrder: function (order) {
        const thiss = this;
        thiss.transferItems(order).then(res => {
          thiss.transferCustomer(order.client).then(res => {
            if (res) {
              sbc.db.transaction(function (tx) {
                tx.executeSql("select shipto from transhead where orderno=?", [order.orderno], function (tx, res) {
                  if (res.rows.length > 0) {
                    $q.localStorage.remove("orderShipto");
                    $q.localStorage.set("orderShipto", res.rows.item(0).shipto);
                  }
                });
              });
              thiss.removeOrder(order);
              sbc.globalFunc.refreshCart();
            }
          }).catch(err => {
            cfunc.showMsgBox(err.message, "negative", "warning");
          });
        }).catch(err => {
          cfunc.showMsgBox(err.message, "negative", "warning");
        });
      },
      transferItems: function (order) {
        return new Promise((resolve, reject) => {
          sbc.db.transaction(function (tx) {
            if (sbc.globalFunc.company === "sbc") {
              tx.executeSql("select transstock.*, ifnull(itemstat.qty,0) as itembal, item.itemid from transstock left join item on item.barcode=transstock.barcode left join itemstat on itemstat.itemid=item.itemid where transstock.orderno=? order by transstock.line", [order.orderno], function (tx, res) {
                if (res.rows.length > 0) {
                  for (var x = 0; x < res.rows.length; x++) {
                    tx.executeSql("insert into cart(itemid, isamt, amt, isqty, iss, ext, disc, uom, factor, rem) values(?, ?, ?, ?, ?, ?, ?, ?, ?, ?)", [res.rows.item(x).itemid, res.rows.item(x).isamt, res.rows.item(x).amt, res.rows.item(x).isqty, res.rows.item(x).iss, res.rows.item(x).total, res.rows.item(x).disc, res.rows.item(x).uom, res.rows.item(x).factor, res.rows.item(x).rem]);
                    tx.executeSql("update itemstat set qty=" + sbc.numeral(sbc.numeral(res.rows.item(x).itembal).value() + sbc.numeral(res.rows.item(x).iss).value()).value() + " where itemid=" + res.rows.item(x).itemid);
                    if (parseInt(x) + 1 === res.rows.length) resolve(true);
                  }
                } else {
                  resolve(false);
                }
              }, function (tx, err) {
                reject(err);
              });
            } else {
              tx.executeSql("update item set qty=?, newamt=amt, newuom=uom, newfactor=factor, rem=?, newdisc=disc", ["0", ""]);
              tx.executeSql("select transstock.*, item.itemid from transstock left join item on item.barcode=transstock.barcode where transstock.orderno=? order by transstock.line", [order.orderno], function (tx, res) {
                if (res.rows.length > 0) {
                  for (var x = 0; x < res.rows.length; x++) {
                    tx.executeSql("update item set newamt=?, qty=?, newuom=?, newfactor=?, rem=?, seq=?, newdisc=? where barcode=?", [res.rows.item(x).amt, res.rows.item(x).qty, res.rows.item(x).uom, res.rows.item(x).factor, res.rows.item(x).rem, parseInt(x) + 1, res.rows.item(x).disc, res.rows.item(x).barcode]);
                    tx.executeSql("update itemstat set qty=qty+" + res.rows.item(x).qty + " where itemid=" + res.rows.item(x).itemid);
                  }
                  resolve(true);
                } else {
                  resolve(false);
                }
              }, function (tx, err) {
                reject(err);
              });
            }
          });
        });
      },
      transferCustomer: function (client) {
        return new Promise((resolve, reject) => {
          sbc.db.transaction(function (tx) {
            tx.executeSql("select * from customers where client=?", [client], function (tx, res) {
              if (res.rows.length > 0) {
                $q.localStorage.remove("selCustomer");
                $q.localStorage.set("selCustomer", res.rows.item(0));
                resolve(true);
              } else {
                $q.localStorage.remove("selCustomer");
                cfunc.showMsgBox("Customer not found.", "negative", "warning");
                resolve(false);
              }
            }, function (tx, err) {
              reject(err);
            });
          });
        });
      },
      resetOrderno: function (orderno) {
        sbc.db.transaction(function (tx) {
          tx.executeSql("select lastorderno from config", [], function (tx, res) {
            if (res.rows.length > 0) {
              if (parseInt(res.rows.item(0).lastorderno) <= parseInt(orderno)) {
                orderno = parseInt(order.orderno) - 1;
                orderno = orderno.toString().padStart(10, "0");
                tx.executeSql("update config set lastorderno=?", [orderno]);
              }
            }
          });
        });
      },
      removeOrder: function (order) {
        sbc.db.transaction(function (tx) {
          sbc.modulefunc.resetOrderno(order.orderno);
          tx.executeSql("delete from transhead where orderno=?", [order.orderno])
          tx.executeSql("delete from transstock where orderno=?", [order.orderno])
          sbc.modulefunc.loadTableData();
          cfunc.showMsgBox("Check your cart to edit. Your orders are back to cart", "positive");
        });
      },
      printOrder: function (order) {
        console.log("printOrder called: ", order);
        $q.dialog({
          message: "Do you want to reprint order receipt?",
          ok: { flat: true, color: "primary" },
          cancel: { flat: true, color: "negative" }
        }).onOk(() => {
          // cfunc.showLoading();
          sbc.db.transaction(function (tx) {
            tx.executeSql("select * from transstock where orderno=?", [order.orderno], function (tx, res) {
              // let str = [];
              // let printerLen = 32;
              // str.push(sbc.globalFunc.mrow(["DATE:"], [order.dateid, "", "R"]));
              // str.push(sbc.globalFunc.mrow(["ORDER NO:"], [order.orderno, "", "R"]));
              // str.push(sbc.globalFunc.mrow(["CUSTOMER:"], [order.clientname, "", "R"]));
              // str.push(sbc.globalFunc.mrow(["ITEM DESC QTY UNIT PRICE TOTAL"]));
              // for (var x = 0; x < res.rows.length; x++) {
              //   if(sbc.globalFunc.company === "sbc") {
              //     str.push(sbc.globalFunc.mrow([res.rows.item(x).itemname + " " + parseInt(res.rows.item(x).isqty) + " " + res.rows.item(x).uom + " " + sbc.numeral(res.rows.item(x).isamt).format("0,0.00") + " " + sbc.numeral(res.rows.item(x).ext).format("0,0.00")]));
              //   } else {
              //     str.push(sbc.globalFunc.mrow([res.rows.item(x).itemname + " " + parseInt(res.rows.item(x).qty) + " " + res.rows.item(x).uom + " " + sbc.numeral(res.rows.item(x).newamt).format("0,0.00") + " " + sbc.numeral(res.rows.item(x).total).format("0,0.00")]))
              //   }
              // }
              // str.push(sbc.globalFunc.mrow(["GRAND TOTAL:" + " " + order.total, "", "C"]));
              // str.push(sbc.globalFunc.mrow(["PAYMENT DETAILS:", "", "C"]));
              // str.push(sbc.globalFunc.mrow(["PAYMENT TYPE:", "", "C"], [order.transtype]));
              // str.push(sbc.globalFunc.mrow(["PAYMENT:", "", "C"], [order.tendered]));
              // str.push(sbc.globalFunc.mrow(["CHANGE:", "", "C"], [order.change]));

              let str = [];
              let printerLen = 32;
              str.push(sbc.globalFunc.mrow([storage.user.name, "0", "C"]));
              str.push(sbc.globalFunc.mrow(["-".repeat(printerLen), "1"]));
              str.push(sbc.globalFunc.mrow(["ORDER NO.: " + order.orderno, "1"]));
              str.push(sbc.globalFunc.mrow(["DATE: " + order.date, "1"]));
              str.push(sbc.globalFunc.mrow(["-".repeat(printerLen), "1"]));
              str.push(sbc.globalFunc.mrow(["CUSTOMER INFORMATION", "1", "C"]));
              str.push(sbc.globalFunc.mrow(["NAME: ", "1"], [order.clientname, "1", "R"]));
              str.push(sbc.globalFunc.mrow(["ADDRESS: ", "1"], [order.addr, "1", "R"]));
              str.push(sbc.globalFunc.mrow(["-".repeat(printerLen), "1"]));
              str.push(sbc.globalFunc.mrow(["NOTE: " + order.rem, "1"]));
              str.push(sbc.globalFunc.mrow(["-".repeat(printerLen), "1"]));
              for (var x = 0; x < res.rows.length; x++) {
                str.push(sbc.globalFunc.mrow([res.rows.item(x).itemname]));
                let col1 = sbc.numeral(res.rows.item(x).isqty).format("0,0.0");
                let col2 = sbc.numeral(res.rows.item(x).isamt).format("0,0.00");
                let col3 = sbc.numeral(res.rows.item(x).ext).format("0,0.00");
                let col11 = col1 + "&nbsp;&nbsp;x" + "&nbsp;".repeat((Math.floor(printerLen / 3) - col1.length) - 3);
                let col21 = "&nbsp;".repeat(Math.floor((Math.floor(printerLen / 3) - col2.length) / 2)) + col2 + "&nbsp;".repeat(Math.floor((Math.floor(printerLen / 3) - col2.length) / 2));
                let col31 = "&nbsp;".repeat(Math.floor(printerLen / 3) - col3.length) + col3;
                let col12 = col1 + "  x" + " ".repeat((Math.floor(printerLen / 3) - col1.length) - 3);
                let col22 = " ".repeat(Math.floor((Math.floor(printerLen / 3) - col2.length) / 2)) + col2 + " ".repeat(Math.floor((Math.floor(printerLen / 3) - col2.length) / 2));
                let col32 = " ".repeat(Math.floor(printerLen / 3) - col3.length) + col3;
                let cols1 = col11 + "" + col21 + "" + col31;
                let cols2 = col12 + "" + col22 + "" + col32;
                str.push(sbc.globalFunc.mrow([cols1, "1", "", "", true], [cols2]));
              }
              str.push(sbc.globalFunc.mrow(["-".repeat(printerLen), "1"]));
              str.push(sbc.globalFunc.mrow([order.itemcount, "1"], ["Items(s)", "1", "C"]));
              str.push(sbc.globalFunc.mrow(["=".repeat(printerLen), "1"]));
              str.push(sbc.globalFunc.mrow(["SUBTOTAL", "1"], [sbc.numeral(order.total).format("0,0.00"), "1", "R"]));
              str.push(sbc.globalFunc.mrow(["TOTAL", "0"], [sbc.numeral(order.total).format("0,0.00"), "0", "R"]));
              str.push(sbc.globalFunc.mrow(["-".repeat(printerLen), "1"]));
              if (order.transtype == "CASH") {
                str.push(sbc.globalFunc.mrow(["PAYMENT RECEIVED:", "1"], [order.tendered, "1", "R"]));
                str.push(sbc.globalFunc.mrow([order.transtype, "1"]));
                str.push(sbc.globalFunc.mrow(["CHANGE AMOUNT:", "1"], [order.change, "1", "R"]));
              } else {
                str.push(sbc.globalFunc.mrow(["PAYMENT RECEIVED:", "1"], ["0.00", "1", "R"]));
                str.push(sbc.globalFunc.mrow([order.transtype, "1"]));
                str.push(sbc.globalFunc.mrow(["CHANGE AMOUNT:", "1"], ["0.00", "1", "R"]));
              }
              str.push(sbc.globalFunc.mrow(["Acknowledgement Receipt", "1", "C"]));
              str.push(sbc.globalFunc.mrow(["Thank You!", "1", "C"]));

              sbc.globalFunc.generateReport(str, printerLen).then(res => {
                sbc.modulefunc.lookupTableFilter = { type: "filter", field: "", label: "", func: "" };
                sbc.globalFunc.printLayout = res.view;
                sbc.globalFunc.printData = res.print;
                sbc.globalFunc.mprint();
              });
            }, function (tx, err) {
              cfunc.showMsgBox(err.message, "negative", "warning");
            });
          });
        });
      }
    })';
  }
}