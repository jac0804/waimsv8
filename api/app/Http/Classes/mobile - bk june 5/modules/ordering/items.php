<?php
namespace App\Http\Classes\mobile\modules\ordering;

use App\Http\Classes\builder\mobiletxtFieldClass;
use App\Http\Classes\builder\mobileButtonClass;

class items {
  private $fieldClass;
  private $buttonClass;
  private $company;

  public function __construct() {
    $this->fieldClass = new mobiletxtFieldClass;
    $this->buttonClass = new mobileButtonClass;
    $this->company = env('appcompany', 'sbc');
  }
  public function getLayout() {
    switch($this->company) {
      case 'marswin':
        $fields = ['clientname', 'addr', 'tel', 'rem', 'brand', 'part', 'searchitem', 'btnsearch'];
        break;
      case 'sbc':
        $fields = ['clientname', 'brgy', 'area', 'province', 'rem', 'searchitem'];
        break;
      default:
        $fields = ['clientname', 'addr', 'tel', 'rem', 'searchitem'];
        break;
    }
    $cfHeadFields = $this->fieldClass->create($fields);
      if($this->company != 'marswin') data_set($cfHeadFields, 'searchitem.enterfunc', 'searchItem');
      if($this->company == 'sbc') {
        data_set($cfHeadFields, 'clientname.fields', 'clientid,clientname,client,brgy,area,province');
      } else {
        data_set($cfHeadFields, 'clientname.fields', 'clientid,clientname,addr,client,tel');
      }
      data_set($cfHeadFields, 'rem.type', 'input');
      data_set($cfHeadFields, 'rem.label', 'Notes');
      data_set($cfHeadFields, 'clientname.label', 'Customer');
      data_set($cfHeadFields, 'clientname.action', 'clientlookup');

    switch($this->company) {
      case 'marswin':
        $itemFields = ['qty', 'itembal', 'amt', 'disc', 'itemname', 'brand', 'barcode', 'newuom', 'part', 'plgrp'];
        break;
      case 'shinzen':
        $itemFields = ['qty', 'itemname', 'amt', 'disc', 'newuom', 'groupid', 'category', 'brand', 'model', 'part', 'size', 'country', 'barcode'];
        break;
      case 'sbc':
        $itemFields = ['itemname', 'itembal', 'newuom', 'iamt', 'istaxable', 'barcode'];
        break;
      default:
        $itemFields = ['qty', 'itembal', 'amt', 'itemname', 'barcode', 'newuom', 'istaxable'];
        break;
    }
    $cfTableCols = $this->fieldClass->create($itemFields);
      if($this->company == 'sbc') {
        data_set($cfTableCols, 'isamt.type', 'label');
        data_set($cfTableCols, 'isamt.field', 'isamt');
        data_set($cfTableCols, 'isamt.align', 'left');
      } else {
        data_set($cfTableCols, 'amt.type', 'label');
        data_set($cfTableCols, 'amt.field', 'amt');
        data_set($cfTableCols, 'amt.align', 'left');
      }
      data_set($cfTableCols, 'itemname.type', 'label');
      data_set($cfTableCols, 'itemname.field', 'itemname');
      data_set($cfTableCols, 'itemname.align', 'left');
      data_set($cfTableCols, 'barcode.type', 'label');
      data_set($cfTableCols, 'barcode.field', 'barcode');
      data_set($cfTableCols, 'barcode.align', 'left');
      data_set($cfTableCols, 'qty.label', 'Ordered Qty');
      switch($this->company) {
        case 'marswin':
          data_set($cfTableCols, 'brand.label', 'Brand');
          data_set($cfTableCols, 'part.label', 'Part');
          break;
      }

    $btns = ['addtocart'];
    $cfTableButtons = $this->buttonClass->create($btns);

    switch($this->company) {
      case 'sbc':
        $fields = ['isamt', 'disc', 'total', 'uom', 'rem', 'newitembal'];
        break;
      case 'marswin':
        $fields = ['newamt', 'newdisc', 'total', 'newuom', 'rem', 'newfactor'];
        break;
      default:
        $fields = ['newamt', 'newdisc', 'total', 'newuom', 'rem'];
        break;
    }
    if($this->company == 'marswin') array_push($fields, 'newfactor');
    $editItemFields = $this->fieldClass->create($fields);
      data_set($editItemFields, 'rem.type', 'lookup');
      data_set($editItemFields, 'rem.action', 'remlookup');
      data_set($editItemFields, 'rem.fields', 'rem');
      data_set($editItemFields, 'rem.readonly', 'true');
      switch($this->company) {
        case 'fastrax':
          data_set($editItemFields, 'newdisc.readonly', 'true');
          data_set($editItemFields, 'newamt.readonly', true);
          data_set($editItemFields, 'newuom.type', 'input');
          data_set($editItemFields, 'newuom.readonly', 'true');
          break;
        case 'sbc':
          data_set($editItemFields, 'disc.type', 'lookup');
          data_set($editItemFields, 'disc.action', 'disclookup');
          data_set($editItemFields, 'disc.fields', 'disc');
          data_set($editItemFields, 'disc.readonly', 'true');
          data_set($editItemFields, 'uom.type', 'select');
          data_set($editItemFields, 'uom.options', 'uoms');
          data_set($editItemFields, 'uom.enterfunc', 'computeItem');
          data_set($editItemFields, 'uom.readonly', 'false');
          break;
        default:
          data_set($editItemFields, 'newdisc.readonly', 'true');
          data_set($editItemFields, 'newamt.readonly', true);
          data_set($editItemFields, 'newuom.type', 'input');
          data_set($editItemFields, 'newuom.readonly', 'true');
          data_set($editItemFields, 'newdisc.type', 'input');
          break;
      }

    $btns = ['ok'];
    $editItemButtons = $this->buttonClass->create($btns);

    $fields = ['disc'];
    $discLookupFields = $this->fieldClass->create($fields);
    data_set($discLookupFields, 'disc.type', 'input');
    data_set($discLookupFields, 'disc.enterfunc', 'saveDisc');
    data_set($discLookupFields, 'disc.functype', 'module');
    $inputLookupFields = [];
    array_push($inputLookupFields, ['form'=>'discLookupFields', 'fields'=>$discLookupFields]);

    $fields = ['rem'];
    $remLookupFields = $this->fieldClass->create($fields);
    data_set($remLookupFields, 'rem.type', 'input');
    data_set($remLookupFields, 'rem.readonly', 'false');
    data_set($remLookupFields, 'rem.enterfunc', 'saveRemarks');
    array_push($inputLookupFields, ['form'=>'remLookupFields', 'fields'=>$remLookupFields]);

    $inputLookupButtons = [];
    $btns = ['saverecord', 'cancelrecord'];
    $discLookupButtons = $this->buttonClass->create($btns);
    data_set($discLookupButtons, 'saverecord.form', 'discLookupButtons');
    data_set($discLookupButtons, 'saverecord.func', 'saveDisc');
    data_set($discLookupButtons, 'cancelrecord.form', 'discLookupButtons');
    data_set($discLookupButtons, 'cancelrecord.func', 'cancelDisc');
    array_push($inputLookupButtons, ['form'=>'discLookupButtons', 'btns'=>$discLookupButtons]);

    $btns = ['saverecord', 'cancelrecord'];
    $remLookupButtons = $this->buttonClass->create($btns);
    data_set($remLookupButtons, 'saverecord.form', 'remLookupButtons');
    data_set($remLookupButtons, 'saverecord.func', 'saveRemarks');
    data_set($remLookupButtons, 'cancelrecord.form', 'remLookupButtons');
    array_push($inputLookupButtons, ['form'=>'remLookupButtons', 'btns'=>$remLookupButtons]);

    $cLookupHeadFields = [];
    switch($this->company) {
      case "sbc":
        $fields = ['clientname', 'brgy', 'area', 'province', 'paytype'];
        break;
      default:
        $fields = ['clientname', 'addr', 'tel', 'paytype'];
        break;
    }
    $cartHeadFields = $this->fieldClass->create($fields);
      data_set($cartHeadFields, 'clientname.label', 'Customer');
      data_set($cartHeadFields, 'clientname.action', 'clientlookup');
      if($this->company == 'sbc') {
        data_set($cartHeadFields, 'clientname.fields', 'clientid,clientname,client,brgy,area,province');
      } else {
        data_set($cartHeadFields, 'clientname.fields', 'clientid,clientname,addr,client,tel');
      }
    array_push($cLookupHeadFields, ['form'=>'cartHeadFields', 'fields'=>$cartHeadFields]);

    $cLookupTableCols = [];
    if($this->company == 'sbc') {
      $itemFields = ['qty', 'ext', 'itemname', 'barcode', 'uom', 'istaxable', 'rem'];
    } else {
      $itemFields = ['qty', 'itembal', 'amt', 'itemname', 'barcode', 'uom', 'istaxable', 'rem'];
    }
    $cartTableCols = $this->fieldClass->create($itemFields);
      if ($this->company == 'sbc') {
        data_set($cartTableCols, 'uom.type', 'label');
        data_set($cartTableCols, 'uom.field', 'uom');
        data_set($cartTableCols, 'uom.sortable', 'false');
      } else {
        data_set($cartTableCols, 'amt.type', 'label');
        data_set($cartTableCols, 'amt.field', 'amt');
        data_set($cartTableCols, 'amt.align', 'left');
      }
      data_set($cartTableCols, 'itemname.type', 'label');
      data_set($cartTableCols, 'itemname.field', 'itemname');
      data_set($cartTableCols, 'itemname.align', 'left');
      data_set($cartTableCols, 'barcode.type', 'label');
      data_set($cartTableCols, 'barcode.field', 'barcode');
      data_set($cartTableCols, 'barcode.align', 'left');
      data_set($cartTableCols, 'qty.label', 'Ordered Qty');
      data_set($cartTableCols, 'rem.type', 'label');
      data_set($cartTableCols, 'rem.field', 'rem');
      data_set($cartTableCols, 'rem.align', 'left');
    array_push($cLookupTableCols, ['form'=>'cartTableCols', 'fields'=>$cartTableCols]);

    $cLookupFooterFields = [];
    $fields = ['itemcount', 'total'];
    $cartFooterFields = $this->fieldClass->create($fields);
      data_set($cartFooterFields, 'total.type', 'label');
      data_set($cartFooterFields, 'total.label', 'Total: ');
    array_push($cLookupFooterFields, ['form'=>'cartFooterFields', 'fields'=>$cartFooterFields]);

    $cLookupTableButtons = [];
    $btns = ['editcart', 'deletecart'];
    $cartTableButtons = $this->buttonClass->create($btns);
    array_push($cLookupTableButtons, ['form'=>'cartTableButtons', 'btns'=>$cartTableButtons]);

    $cLookupButtons = [];
    $btns = ['checkout'];
    $cartButtons = $this->buttonClass->create($btns);
    array_push($cLookupButtons, ['form'=>'cartButtons', 'btns'=>$cartButtons]);

    $fields = ['total', 'payment', 'change', 'compute'];
    $checkoutFields = $this->fieldClass->create($fields);
    data_set($checkoutFields, 'total.type', 'label');
    data_set($checkoutFields, 'total.label', 'Total: ');
    data_set($checkoutFields, 'total.style', 'font-size:140%;');

    $btns = ['close', 'ok'];
    $checkoutButtons = $this->buttonClass->create($btns);
    data_set($checkoutButtons, 'close.func', 'closeCheckout');
    data_set($checkoutButtons, 'close.functype', 'module');
    data_set($checkoutButtons, 'ok.func', 'checkoutOk');
    data_set($checkoutButtons, 'ok.functype', 'module');

    return ['cfHeadFields'=>$cfHeadFields, 'cfTableCols'=>$cfTableCols, 'cfTableButtons'=>$cfTableButtons, 'editItemFields'=>$editItemFields, 'editItemButtons'=>$editItemButtons, 'inputLookupFields'=>$inputLookupFields, 'inputLookupButtons'=>$inputLookupButtons, 'cLookupHeadFields'=>$cLookupHeadFields, 'cLookupTableCols'=>$cLookupTableCols, 'cLookupTableButtons'=>$cLookupTableButtons, 'cLookupFooterFields'=>$cLookupFooterFields, 'cLookupButtons'=>$cLookupButtons, 'checkoutFields'=>$checkoutFields, 'checkoutButtons'=>$checkoutButtons];
  }

  public function getFunc() {
    return '({
      hasTablePagination: true,
      txtSearchCustomer: "",
      paginationData: { label: "Items", pageNum: 1, totalPage: 0, maxPages: 3, color: "primary", page: 1, rowsPerPage: 0, lastItem: 0 },
      paginationLabel: "Items",
      searchitem: "",
      docForm: { searchitem: "", client: "", clientname: "", clientid: "", addr: "", tel: "", itemcount: 0, total: "", doctype: "", terms: "", brand: "", part: "", docterms: [], rem: "", paytype: "CASH", brgy: "", area: "", province: "" },
      tableData: [],
      qtyCol: "qty",
      inputLookupForm: [],
      lookupTableData: [],
      cLookupForm: [],
      checkoutForm: { total: "", payment: "", payment2: "", change: "" },
      paytypechange: function () {
        console.log("paytypechange called");
      },
      searchCustomer: function () {
        console.log("searchCustomer called");
        sbc.globalFunc.loadLookup(sbc.globalFunc.lookupAction, sbc.globalFunc.lookupFields);
      },
      paginationChanged: function () {
        console.log("Page: ", sbc.modulefunc.paginationData.page);
        sbc.modulefunc.loadTableData();
      },
      tablePagination: { page: 0, rowsPerPage: 0 },
      getItemCount: function () {
        sbc.db.transaction(function (tx) {
          let sql = "select count(*) as icount from item where 1=1";
          let strs = [];
          let f = "";
          let d = ["0", "*"];
          switch (sbc.globalFunc.company) {
            case "marswin":
              if (sbc.modulefunc.docForm.part !== "") {
                if (f !== "") { f = f.concat(" and item.part like ?"); }
                else { f = f.concat(" item.part like ?"); }
                d.push(["%" + sbc.modulefunc.docForm.part + "%"]);
              }
              if (sbc.modulefunc.docForm.brand !== "") {
                if (f !== "") { f = f.concat(" and item.brand=?"); }
                else { f = f.concat(" item.brand=?"); }
                d.push([sbc.modulefunc.docForm.brand]);
              }
              if (sbc.modulefunc.docForm.searchitem !== "") {
                if (f !== "") { f = f.concat(" and item.itemname like ?"); }
                else { f = f.concat(" item.itemname like ?"); }
                d.push(["%" + sbc.modulefunc.docForm.searchitem + "%"]);
              }
              break;
            default:
              cfunc.showLoading("Loading Items, Please wait...");
              if (sbc.modulefunc.docForm.searchitem !== "") strs = sbc.modulefunc.docForm.searchitem.split(",");
              if (strs.length > 0) {
                for (var s in strs) {
                  strs[s] = strs[s].trim();
                  if (strs[s] !== "") {
                    if (f !== "") {
                      f = f.concat(" and ((item.itemname like ?) or (item.barcode like ?) or (item.uom like ?) or (item.brand like ?) or (item.part like ?) or (item.groupid like ?) or (item.category like ?) or (item.model like ?) or (item.sizeid like ?) or (item.country like ?)) ");
                    } else {
                      f = f.concat(" ((item.itemname like ?) or (item.barcode like ?) or (item.uom like ?) or (item.brand like ?) or (item.part like ?) or (item.groupid like ?) or (item.category like ?) or (item.model like ?) or (item.sizeid like ?) or (item.country like ?)) ");
                    }
                    d.push(["%" + strs[s] + "%", "%" + strs[s] + "%", "%" + strs[s] + "%", "%" + strs[s] + "%", "%" + strs[s] + "%", "%" + strs[s] + "%", "%" + strs[s] + "%", "%" + strs[s] + "%", "%" + strs[s] + "%", "%" + strs[s] + "%"]);
                  }
                }
              }
              break;
          }
          if (f !== "") {
            sql = sql.concat(" and (item.isinactive = 0 or item.isinactive = ?) and item.barcode <> ? and (" + f + ") order by item.itemid asc");
          } else {
            sql = sql.concat(" and (item.isinactive = 0 or item.isinactive = ?) and item.barcode <> ? order by item.itemid asc");
          }
          let dd = [].concat.apply([], d);

          sbc.modulefunc.paginationData.label = "0 Items";
          tx.executeSql(sql, dd, function (tx, res) {
            sbc.modulefunc.paginationData.label = sbc.numeral(res.rows.item(0).icount).format("0,0") + " Items";
            sbc.modulefunc.paginationData.totalPage = Math.ceil(res.rows.item(0).icount / 100);
          });
        });
      },
      searchItem: function () {
        sbc.modulefunc.loadTableData("search");
      },
      loadTableData: function (type = "") {
        console.log("loadTableData Called");
        sbc.isFormEdit = true;
        const thiss = this;
        sbc.modulefunc.getItemCount();
        if ($q.localStorage.has("selCustomer")) {
          let selCustomer = $q.localStorage.getItem("selCustomer");
          sbc.modulefunc.docForm.clientid = selCustomer.clientid;
          sbc.modulefunc.docForm.client = selCustomer.client;
          sbc.modulefunc.docForm.clientname = selCustomer.clientname;
          sbc.modulefunc.docForm.addr = selCustomer.addr;
          sbc.modulefunc.docForm.tel = selCustomer.tel;
          sbc.modulefunc.docForm.brgy = selCustomer.brgy;
          sbc.modulefunc.docForm.area = selCustomer.area;
          sbc.modulefunc.docForm.province = selCustomer.province;
        }
        if (type === "search") sbc.modulefunc.paginationData.page = 1;
        if (sbc.modulefunc.paginationData.page <= 0) sbc.modulefunc.paginationData.page = 1;
        let offset = (sbc.modulefunc.paginationData.page - 1) * 100;
        let sql = "select * from item where 1=1 and item.itemid > " + sbc.modulefunc.paginationData.lastItem;
        if (sbc.globalFunc.company === "sbc") sql = "select item.itemid, item.barcode, item.itemname, item.brand, item.part, item.plgrp, item.isinactive, item.groupid, item.category, item.model, item.sizeid, item.country, item.seq, item.istaxable, item.class, item.amt as iamt, cart.isamt, cart.amt, item.hasitem, cart.disc, cart.uom, item.newuom, ifnull(cart.isqty,0) as qty, cart.iss, cart.factor, item.newfactor, cart.rem, ifnull(itemstat.qty,0) as itembal, itemstat.qty as newitembal from item left join cart on cart.itemid=item.itemid left join itemstat on itemstat.itemid=item.itemid where 1=1 and itemstat.qty > 0 and item.itemid > " + sbc.modulefunc.paginationData.lastItem;
        let strs = [];
        let f = "";
        let d = ["0", "*"];
        switch (sbc.globalFunc.company) {
          case "marswin":
            if (sbc.modulefunc.docForm.part !== "") {
              if (f !== "") {
                f = f.concat(" and item.part like ?");
              } else {
                f = f.concat(" item.part like ?");
              }
              d.push(["%" + sbc.modulefunc.docForm.part + "%"]);
            }
            if (sbc.modulefunc.docForm.brand !== "") {
              if (f !== "") {
                f = f.concat(" and item.brand=?");
              } else {
                f = f.concat(" item.brand=?");
              }
              d.push([sbc.modulefunc.docForm.brand]);
            }
            if (sbc.modulefunc.docForm.searchitem !== "") {
              if (f !== "") {
                f = f.concat(" and item.itemname like ?");
              } else {
                f = f.concat(" item.itemname like ?");
              }
              d.push(["%" + sbc.modulefunc.docForm.searchitem + "%"]);
            }
            break;
          default:
            cfunc.showLoading("Loading Items, Please wait...");
            if (sbc.modulefunc.docForm.searchitem !== "") strs = sbc.modulefunc.docForm.searchitem.split(",");
            if (strs.length > 0) {
              for (var s in strs) {
                strs[s] = strs[s].trim();
                if (strs[s] !== "") {
                  if (f !== "") {
                    f = f.concat(" and ((item.itemname like ?) or (item.barcode like ?) or (item.uom like ?) or (item.brand like ?) or (item.part like ?) or (item.groupid like ?) or (item.category like ?) or (item.model like ?) or (item.sizeid like ?) or (item.country like ?)) ");
                  } else {
                    f = f.concat(" ((item.itemname like ?) or (item.barcode like ?) or (item.uom like ?) or (item.brand like ?) or (item.part like ?) or (item.groupid like ?) or (item.category like ?) or (item.model like ?) or (item.sizeid like ?) or (item.country like ?)) ");
                  }
                  d.push(["%" + strs[s] + "%", "%" + strs[s] + "%", "%" + strs[s] + "%", "%" + strs[s] + "%", "%" + strs[s] + "%", "%" + strs[s] + "%", "%" + strs[s] + "%", "%" + strs[s] + "%", "%" + strs[s] + "%", "%" + strs[s] + "%"]);
                }
              }
            }
            break;
        }
        if (f !== "") {
          sql = sql.concat(" and (item.isinactive = 0 or item.isinactive = ?) and item.barcode <> ? and (" + f + ") order by item.itemid asc limit " + offset + ", 100 ");
        } else {
          sql = sql.concat(" and (item.isinactive = 0 or item.isinactive = ?) and item.barcode <> ? order by item.itemid asc limit " + offset + ", 100 ");
        }
        let dd = [].concat.apply([], d);
        console.log("zzzzzzzzzssasdasdas", sql, "---", dd);
        sbc.modulefunc.tableData = [];
        sbc.db.transaction(function (tx) {
          tx.executeSql(sql, dd, function (tx, res) {
            if (res.rows.length > 0) {
              for (var x = 0; x < res.rows.length; x++) {
                sbc.modulefunc.tableData.push(res.rows.item(x));
                if (sbc.globalFunc.company === "sbc") {
                  sbc.modulefunc.tableData[x].iamt = sbc.numeral(sbc.modulefunc.tableData[x].iamt).format("0,0.00");
                  sbc.modulefunc.tableData[x].itembal = sbc.numeral(sbc.modulefunc.tableData[x].itembal).format("0,0.00");
                } else {
                  sbc.modulefunc.tableData[x].amt = sbc.numeral(sbc.modulefunc.tableData[x].amt).format("0,0.00");
                }
                if (sbc.modulefunc.tableData[x].qty !== 0 && sbc.modulefunc.tableData[x].qty !== "0") {
                  sbc.modulefunc.tableData[x].hasqty = true;
                  sbc.modulefunc.tableData[x].bgColor = "bg-blue-2";
                } else {
                  sbc.modulefunc.tableData[x].hasqty = false;
                  sbc.modulefunc.tableData[x].bgColor = "";
                }
                if ((parseInt(x) + 1) === res.rows.length) {
                  // sbc.modulefunc.paginationData.lastItem = res.rows.item(x).itemid;
                  switch (sbc.globalFunc.company) {
                    case "marswin": sbc.modulefunc.updateItemBal(); break;
                    case "sbc":
                      sbc.globalFunc.refreshCart();
                      $q.loading.hide();
                      break;
                    default:
                      sbc.globalFunc.refreshCart();
                      $q.loading.hide();
                      break;
                  }
                  console.log("itemswaw: ", sbc.modulefunc.tableData);
                }
              }
            } else {
              $q.loading.hide();
              console.log("no items to show");
            }
            for (var b in sbc.footerbuttons) {
              if (sbc.footerbuttons[b].name === "sync") sbc.footerbuttons[b].show = "false";
            }
          }, function (tx, err) {
            cfunc.showMsgBox(err.message, "negative", "warning");
            $q.loading.hide();
          });
        }, function (err) {
          console.log("loadTableData error: ", err.message);
        });
      },
      updateItemBal: function () {
        const thiss = this;
        let items = sbc.modulefunc.tableData;
        let itemids = [];
        for (var i in items) itemids.push(items[i].itemid);
        itemids = itemids.join(",");
        sbc.modulefunc.loadItemBal(itemids).then(res => {
          if (res.length > 0) {
            for (var ii in sbc.modulefunc.tableData) {
              for (var b in res) {
                if (parseInt(res[b].itemid) === sbc.modulefunc.tableData[ii].itemid) {
                  sbc.modulefunc.tableData[ii].itembal = res[b].qty;
                }
              }
            }
            for (var iii in sbc.modulefunc.lookupTableData) {
              for (var bb in res) {
                if (parseInt(res[bb].itemid) === sbc.modulefunc.lookupTableData[iii].itemid) sbc.modulefunc.lookupTableData[iii].itembal = sbc.numeral(res[bb].qty).format("0,0.00");
              }
            }
            $q.loading.hide();
          } else {
            $q.loading.hide();
          }
        });
      },
      loadItemBal: function (itemids) {
        return new Promise((resolve, reject) => {
          let itembals = [];
          sbc.db.transaction(function (tx) {
            tx.executeSql("select * from itemstat where itemid in (" + itemids + ")", [], function (tx, res) {
              if (res.rows.length > 0) {
                itembals = [];
                for (var x = 0; x < res.rows.length; x++) {
                  itembals.push({ itemid: res.rows.item(x).itemid, qty: sbc.numeral(res.rows.item(x).qty).format("0,0.00") });
                  if (parseInt(x) + 1 === res.rows.length) {
                    resolve(itembals);
                  }
                }
              } else {
                resolve([]);
              }
            }, function (tx, err) {
              reject(err);
            });
          });
        });
      },
      cancelRecord: function () {
        sbc.showInputLookup = false;
      },
      saveRemarks: function () {
        console.log("saveRemarks called");
        let index = sbc.globalFunc.getIndex(sbc.modulefunc.tableData, sbc.selItem, "itemid");
        let index2 = sbc.globalFunc.getIndex(sbc.modulefunc.lookupTableData, sbc.selItem, "itemid");
        const thiss = this;
        if (thiss.inputLookupForm.rem === "" || thiss.inputLookupForm.rem === null || typeof(thiss.inputLookupForm.rem) === "undefined") {
          thiss.inputLookupForm.rem = "";
          cfunc.showMsgBox("Please enter remarks", "negative", "warning");
          return;
        }
        switch (sbc.globalFunc.company) {
          case "shinzen":
            if (sbc.selItem.qty > 0) {
              contUpdateRem();
            } else {
              clearRem();
              sbc.selItem.rem = "";
              thiss.inputLookupForm.rem = "";
            }
            break;
          case "sbc":
            if (sbc.selItem.qty > 0) {
              contUpdateRem();
            } else {
              cfunc.showMsgBox("Please enter quantity first.", "negative", "warning");
            }
            break;
          default:
            contUpdateRem();
            break;
        }
        function clearRem () {
          console.log("clearRem");
          let index = sbc.globalFunc.getIndex(sbc.modulefunc.tableData, sbc.selItem, "itemid");
          cfunc.showLoading();
          let sql;
          sbc.db.transaction(function (tx) {
            if (sbc.globalFunc.company === "sbc") {
              sql = "update cart set rem=? where itemid=" + sbc.selItem.itemid;
            } else {
              sql = "update item set rem=? where itemid=" + sbc.selItem.itemid;
            }
            tx.executeSql(sql, [""], function (tx, res) {
              $q.loading.hide();
              thiss.tableData[index].rem = "";
              sbc.selItem.rem = "";
              thiss.inputLookupForm.rem = "";
              sbc.showInputLookup = false;
            }, function (tx, err) {
              cfunc.showMsgBox(err.message, "negative", "warning");
              $q.loading.hide();
            });
          });
        }
        function contUpdateRem () {
          console.log("contUpdateRem");
          cfunc.showLoading();
          sbc.db.transaction(function (tx) {
            let sql;
            if (sbc.globalFunc.company === "sbc") {
              sql = "update cart set rem=? where itemid=" + sbc.selItem.itemid;
            } else {
              sql = "update item set rem=? where itemid=" + sbc.selItem.itemid;
            }
            tx.executeSql(sql, [sbc.modulefunc.inputLookupForm.rem], function (tx, res) {
              if (typeof(sbc.modulefunc.tableData[index]) !== "undefined") sbc.modulefunc.tableData[index].rem = sbc.modulefunc.inputLookupForm.rem;
              if (typeof(sbc.modulefunc.lookupTableData[index2]) !== "undefined") sbc.modulefunc.lookupTableData[index2].rem = sbc.modulefunc.inputLookupForm.rem;
              sbc.selItem.rem = sbc.modulefunc.inputLookupForm.rem;
              sbc.showInputLookup = false;
              $q.loading.hide();
            }, function (tx, err) {
              cfunc.showMsgBox(err.message, "negative", "warning");
              $q.loading.hide();
            });
          }, function (err) {
            console.log("error: ", err);
          });
        }
      },
      cancelDisc: function () {
        sbc.showInputLookup = false;
        const index = sbc.modulefunc.tableData.indexOf(sbc.selItem);
        const index2 = sbc.modulefunc.lookupTableData.indexOf(sbc.selItem);
        if (typeof(sbc.modulefunc.tableData[index]) !== "undefined") {
          sbc.modulefunc.tableData[index].newdisc = sbc.modulefunc.tableData[index].disc;
        }
        if (typeof(sbc.modulefunc.lookupTableData[index2]) !== "undefined") {
          sbc.modulefunc.lookupTableData[index2].newdisc = sbc.modulefunc.lookupTableData[index2].disc;
        }
      },
      saveDisc: function () {
        console.log("saveDisc: ", sbc.selItem);
        let index = sbc.globalFunc.getIndex(sbc.modulefunc.tableData, sbc.selItem, "itemid");
        let index2 = sbc.globalFunc.getIndex(sbc.modulefunc.lookupTableData, sbc.selItem, "itemid");
        let amt;
        let isamt;
        let discount;
        let total;
        if (sbc.modulefunc.inputLookupForm.disc === "" || sbc.modulefunc.inputLookupForm.disc === 0 || sbc.modulefunc.inputLookupForm.disc === null || typeof(sbc.modulefunc.inputLookupForm.disc) === "undefined") {
          sbc.modulefunc.inputLookupForm.disc = "";
        } else {
          sbc.modulefunc.inputLookupForm.disc = sbc.modulefunc.inputLookupForm.disc.replace(" ", "");
        }
        if (sbc.globalFunc.company === "sbc") {
          if (sbc.selItem.qty <= 0) {
            cfunc.showMsgBox("Please enter quantity first.", "negative", "warning");
            return;
          }
          discount = sbc.globalFunc.computeDiscount(sbc.numeral(sbc.selItem.iamt).value(), sbc.modulefunc.inputLookupForm.disc);
          isamt = sbc.numeral(sbc.numeral(sbc.selItem.iamt).value() * sbc.numeral(sbc.selItem.factor).value()).format("0,0.00");
          amt = sbc.numeral(sbc.numeral(discount).value() * sbc.numeral(sbc.selItem.factor).value()).value();
          total = sbc.numeral(amt * sbc.selItem.qty).value();

          sbc.selItem.isamt = isamt;
          sbc.selItem.amt = amt;
          sbc.selItem.disc = sbc.modulefunc.inputLookupForm.disc;
          sbc.selItem.total = sbc.numeral(total).format("0,0.00");

          if (typeof(sbc.modulefunc.tableData[index]) !== "undefined") {
            sbc.modulefunc.tableData[index].total = sbc.numeral(total).format("0,0.00");
            sbc.modulefunc.tableData[index].isamt = isamt;
            sbc.modulefunc.tableData[index].amt = amt;
            sbc.modulefunc.tableData[index].disc = sbc.modulefunc.inputLookupForm.disc;
          }
          if (typeof(sbc.modulefunc.lookupTableData[index2]) !== "undefined") {
            sbc.modulefunc.lookupTableData[index2].ext = sbc.numeral(total).format("0,0.00");
            sbc.modulefunc.lookupTableData[index2].isamt = isamt;
            sbc.modulefunc.lookupTableData[index2].amt = amt;
            sbc.modulefunc.lookupTableData[index2].disc = sbc.modulefunc.inputLookupForm.disc;
          }
        } else {
          amt = sbc.numeral(sbc.selItem.newamt).format("0,0.00");
          discount = sbc.globalFunc.computeDiscount(sbc.numeral(sbc.selItem.newamt).value(), sbc.modulefunc.inputLookupForm.disc);
          qty = sbc.selItem.qty;
          total = sbc.numeral(discount * qty).format("0,0.00");
          sbc.selItem.total = sbc.numeral(discount * qty).format("0,0.00");
          sbc.selItem.amt = amt;
          sbc.selItem.newdisc = sbc.modulefunc.inputLookupForm.disc;
          sbc.selItem.disc = sbc.modulefunc.inputLookupForm.disc;
          if (typeof(sbc.modulefunc.tableData[index]) !== "undefined") {
            sbc.modulefunc.tableData[index].total = total;
            sbc.modulefunc.tableData[index].qty = qty;
            sbc.modulefunc.tableData[index].newdisc = sbc.modulefunc.inputLookupForm.disc;
            sbc.modulefunc.tableData[index].disc = sbc.modulefunc.inputLookupForm.disc;
          }
          if (typeof(sbc.modulefunc.lookupTableData[index2]) !== "undefined") {
            sbc.modulefunc.lookupTableData[index2].total = total;
            sbc.modulefunc.lookupTableData[index2].qty = qty;
            sbc.modulefunc.lookupTableData[index2].newdisc = sbc.modulefunc.inputLookupForm.disc;
            sbc.modulefunc.lookupTableData[index2].disc = sbc.modulefunc.inputLookupForm.disc;
          }
        }
        sbc.db.transaction(function (tx) {
          let sql;
          let data = [sbc.modulefunc.inputLookupForm.disc];
          if (sbc.globalFunc.company === "sbc") {
            sql = "update cart set disc=?, ext=?, isamt=?, amt=? where itemid=" + sbc.selItem.itemid;
            data = [sbc.modulefunc.inputLookupForm.disc, total, isamt, amt];
          } else {
            sql = "update item set newdisc=? where itemid=" + sbc.selItem.itemid;
          }
          tx.executeSql(sql, data, function (tx, res) {
            sbc.showInputLookup = false;
          }, function (tx, err) {
            cfunc.showMsgBox(err.message, "negative", "warning");
          });
        });
      },
      addToCart: function (item, index) {
        console.log("Add to cart Called: ", item);
        if (sbc.numeral(item.itembal).value() <= 0) {
          cfunc.showMsgBox("Insuficient Item balance", "negative", "warning");
          return;
        }
        let discount;
        let total;
        let isamt;
        let amt;
        let isqty;
        let iss;
        if (sbc.globalFunc.company === "sbc") {
          sbc.selItem = Object.assign({}, item);
          sbc.modulefunc.loadUOMs();
          if (sbc.selItem.qty > 0) {
            console.log("selItemwawsss: ", sbc.selItem);
            discount = sbc.globalFunc.computeDiscount(sbc.numeral(sbc.selItem.iamt).value(), sbc.selItem.disc);
            console.log("discount: ", discount);
            console.log("less discount: ", discount);
            isamt = sbc.numeral(sbc.numeral(sbc.selItem.iamt).value() * sbc.numeral(sbc.selItem.factor).value()).format("0,0.00");
            amt = sbc.numeral(sbc.numeral(discount).value() * sbc.numeral(sbc.selItem.factor).value()).value();
            total = sbc.numeral(sbc.numeral(amt).value() * sbc.selItem.qty).format("0,0.00");
            sbc.selItem.total = total;
            sbc.selItem.isamt = isamt;
            console.log("addtocart: isamt:", isamt);
          } else {
            sbc.selItem.total = "";
          }
        } else {
          // if (item.newuom.toUpperCase() === "BOX") {
          //   for (var i in sbc.edititemfields) {
          //     if (sbc.edititemfields[i].name === "newfactor") {
          //       sbc.edititemfields[i].show = "true";
          //     }
          //   }
          // }
          sbc.selItem = item;
          if (sbc.globalFunc.company !== "marswin") {
            if (sbc.globalFunc.company === "sbc") {
              if (sbc.selItem.qty > 0) {
                discount = sbc.globalFunc.computeDiscount(sbc.numeral(item.amt).value(), item.disc);
                total = sbc.numeral(sbc.numeral(discount).value() * item.qty).format("0,0.00");
                sbc.selItem.total = total;
              }
              sbc.selItem.amt = sbc.numeral(sbc.selItem.amt).format("0,0.00");
            } else {
              if (sbc.selItem.qty > 0) {
                discount = sbc.globalFunc.computeDiscount(sbc.numeral(item.newamt).value(), item.newdisc);
                total = sbc.numeral(sbc.numeral(discount).value() * item.qty).format("0,0.00");
                sbc.selItem.total = total;
              }
              sbc.selItem.newamt = sbc.numeral(sbc.selItem.newamt).format("0,0.00");
            }
          }
        }
        sbc.isFormEdit = true;
        sbc.selItemIndex = index;
        sbc.showEditItem = true;
      },
      checkout: function () {
        sbc.globalFunc.checkout();
      },
      loadUOMs: function () {
        let uoms = [];
        let defaultuom = [];
        let discount;
        sbc.db.transaction(function (tx) {
          console.log("loadUOms called: ", sbc.selItem);
          tx.executeSql("select * from uom where itemid=" + sbc.selItem.itemid, [], function (tx, res) {
            if (res.rows.length > 0) {
              for (var x = 0; x < res.rows.length; x++) {
                if (sbc.numeral(sbc.selItem.qty).value() === 0) {
                  if (res.rows.item(x).isdefault === 1) {
                    defaultuom = res.rows.item(x);
                    console.log("defaultuom: ", res.rows.item(x));
                    if (sbc.globalFunc.company === "sbc") {
                      sbc.selItem.uom = defaultuom.uom;
                      sbc.selItem.factor = defaultuom.factor;
                      discount = sbc.globalFunc.computeDiscount(sbc.numeral(sbc.selItem.iamt).value(), sbc.selItem.disc);
                      sbc.selItem.isamt = sbc.numeral(sbc.numeral(sbc.selItem.iamt).value() * sbc.numeral(defaultuom.factor).value()).format("0,0.00");
                      sbc.selItem.amt = sbc.numeral(sbc.numeral(discount).value() * sbc.numeral(defaultuom.factor).value()).value();
                    } else {
                      sbc.selItem.newuom = res.rows.item(x).uom;
                      sbc.selItem.newfactor = res.rows.item(x).factor;
                      sbc.selItem.newamt = sbc.numeral(sbc.numeral(sbc.selItem.newamt).value() * sbc.numeral(res.rows.item(x).factor).value()).format("0,0.00");
                    }
                    sbc.selItem.newitembal = sbc.numeral(sbc.numeral(sbc.selItem.itembal).value() / sbc.numeral(res.rows.item(x).factor).value()).format("0,0.00");
                  }
                } else {
                  if (sbc.globalFunc.company === "sbc") {
                    if (res.rows.item(x).uom === sbc.selItem.uom) sbc.selItem.newitembal = sbc.numeral(sbc.numeral(sbc.selItem.itembal).value() / sbc.numeral(res.rows.item(x).factor).value()).format("0,0.00");
                  } else {
                    if (res.rows.item(x).uom === sbc.selItem.newuom) sbc.selItem.newitembal = sbc.numeral(sbc.numeral(sbc.selItem.itembal).value() / sbc.numeral(res.rows.item(x).factor).value()).format("0,0.00");
                  }
                }
                uoms.push({
                  label: res.rows.item(x).uom,
                  value: res.rows.item(x).uom,
                  factor: res.rows.item(x).factor
                });
                if (parseInt(x) + 1 === res.rows.length) {
                  if (sbc.numeral(sbc.selItem.qty).value() === 0) {
                    if (defaultuom.length === 0) {
                      sbc.selItem.uom = sbc.selItem.newuom;
                      sbc.selItem.factor = sbc.selItem.newfactor;
                      discount = sbc.globalFunc.computeDiscount(sbc.numeral(sbc.selItem.iamt).value(), sbc.selItem.disc);
                      sbc.selItem.isamt = sbc.numeral(sbc.numeral(sbc.selItem.iamt).value() * sbc.numeral(sbc.selItem.newfactor).value()).format("0,0.00");
                      sbc.selItem.amt = sbc.numeral(sbc.numeral(discount).value() * sbc.numeral(sbc.selItem.newfactor).value()).value();
                    }
                  }
                  for (var i in sbc.edititemfields) {
                    if (sbc.globalFunc.company === "sbc") {
                      if (sbc.edititemfields[i].name === "uom") {
                        sbc.edititemfields[i].options = uoms;
                      }
                    } else {
                      if (sbc.edititemfields[i].name === "newuom") sbc.edititemfields[i].options = uoms;
                    }
                  }
                }
              }
            } else {
              console.log("wawssss");
            }
            console.log("waw", sbc.edititemfields);
          });
        });
      },
      computeItem: function () {
        console.log("computeItem called");
        sbc.db.transaction(function (tx) {
          let newamt;
          let itembal;
          let newitembal;
          if (sbc.globalFunc.company === "sbc") {
            console.log("factor: ", sbc.selItem.uom, " selItem: ", sbc.selItem);
            newamt = sbc.numeral(sbc.numeral(sbc.selItem.iamt).value() * sbc.numeral(sbc.selItem.uom.factor).value()).format("0,0.00");
            // if (sbc.numeral(sbc.selItem.uom.factor).value() === 1) {
            //   newamt = sbc.numeral(sbc.numeral(sbc.selItem.iamt).value() / sbc.numeral(sbc.selItem.factor).value()).format("0,0.00");
            // } else {
            //   newamt = sbc.numeral(sbc.numeral(sbc.selItem.iamt).value() * sbc.numeral(sbc.selItem.uom.factor).value()).format("0,0.00");
            // }
            newitembal = sbc.numeral(sbc.numeral(sbc.selItem.itembal).value() / sbc.numeral(sbc.selItem.uom.factor).value()).format("0,0.00");
          } else {
            if (sbc.numeral(sbc.selItem.newuom.factor).value() === 1) {
              newamt = sbc.numeral(sbc.numeral(sbc.selItem.amt).value() / sbc.numeral(sbc.selItem.newuom.factor).value()).format("0,0.00");
            } else {
              newamt = sbc.numeral(sbc.numeral(sbc.selItem.amt).value() * sbc.numeral(sbc.selItem.newuom.factor).value()).format("0,0.00");
            }
            newitembal = sbc.numeral(sbc.numeral(sbc.selItem.itembal).value() / sbc.numeral(sbc.selItem.newuom.factor).value()).format("0,0.00");
          }
          let index = sbc.globalFunc.getIndex(sbc.modulefunc.tableData, sbc.selItem, "itemid");
          let index2 = sbc.globalFunc.getIndex(sbc.modulefunc.lookupTableData, sbc.selItem, "itemid");
          sbc.selItem.newitembal = newitembal;
          let discount;
          let total;
          if (sbc.globalFunc.company === "sbc") {
            discount = sbc.globalFunc.computeDiscount(sbc.numeral(newamt).value(), sbc.selItem.disc);
          } else {
            discount = sbc.globalFunc.computeDiscount(sbc.numeral(newamt).value(), sbc.selItem.newdisc);
          }
          total = sbc.numeral(sbc.numeral(discount).value() * sbc.selItem.qty).format("0,0.00");
          sbc.selItem.total = total;

          if (sbc.selItem.qty > sbc.numeral(newitembal).value()) {
            cfunc.showMsgBox("Insufficient item balance", "negative", "warning");
            sbc.selItem.qty = Math.floor(sbc.numeral(newitembal).value());
            sbc.globalFunc.updateItem(0, sbc.selItem, sbc.selItem.qty);
            if (typeof(sbc.modulefunc.tableData[index]) !== "undefined") sbc.modulefunc.tableData[index].qty = sbc.selItem.qty;
            if (typeof(sbc.modulefunc.lookupTableData[index2]) !== "undefined") {
              sbc.modulefunc.lookupTableData[index2].qty = sbc.selItem.qty;
              sbc.modulefunc.lookupTableData[index2].ext = sbc.numeral(sbc.numeral(discount).value() * sbc.selItem.qty).format("0,0.00");
              sbc.modulefunc.lookupTableData[index2].total = sbc.numeral(sbc.numeral(discount).value() * sbc.selItem.qty).format("0,0.00");
            }
          } else {
            if (typeof(sbc.modulefunc.lookupTableData[index2]) !== "undefined") {
              sbc.modulefunc.lookupTableData[index2].ext = total;
              sbc.modulefunc.lookupTableData[index2].total = total;
            }
          }
          if (sbc.globalFunc.company === "sbc") {
            sbc.selItem.isamt = newamt;
            sbc.selItem.factor = sbc.selItem.uom.factor;
            sbc.selItem.uom = sbc.selItem.uom.value;
            sbc.selItem.newfactor = sbc.selItem.uom.factor;
            // sbc.selItem.uom = sbc.selItem.uom.value;
            console.log("selItem1: ", sbc.selItem);
            tx.executeSql("update cart set amt=?, ext=?, uom=?, factor=? where itemid=" + sbc.selItem.itemid, [discount, total, sbc.selItem.uom, sbc.selItem.factor], function (tx, res) {
              sbc.globalFunc.refreshCart();
            }, function (tx, err) {
              cfunc.showMsgBox("Error updating item: ", err.message);
            }, function (tx, err) {
              console.log("computeItem Error: ", err.message);
            });
          } else {
            sbc.selItem.newamt = newamt;
            sbc.selItem.newfactor = sbc.selItem.newuom.factor;
            sbc.selItem.newuom = sbc.selItem.newuom.value;
            tx.executeSql("update item set newuom=?, newfactor=? where itemid=" + sbc.selItem.itemid, [sbc.selItem.newuom, sbc.selItem.newfactor], null, function (tx, err) {
              cfunc.showMsgBox("Error updating item: ", err.message);
            });
          }
        }, function (err) {
          console.log(err.message);
        });
      },
      computeTotal: function () {
        console.log("computeTotal called");
        let ctotal = sbc.modulefunc.checkoutForm.total.replace(/<\/?[^>]+(>|$)/g, "");
        // let ctotal = 0;
        let total = sbc.numeral(sbc.modulefunc.checkoutForm.payment).value() - sbc.numeral(ctotal).value();
        sbc.modulefunc.checkoutForm.change = sbc.numeral(total).format("0,0.00");
      },
      checkoutOk: function () {
        console.log("checkoutOk called");
        if (sbc.numeral(sbc.modulefunc.checkoutForm.change).value() < 0) {
          cfunc.showMsgBox("The payment is insufficient.", "negative", "warning");
          return
        }
        if (sbc.modulefunc.checkoutForm.change === "" || sbc.modulefunc.checkoutForm.change === null || typeof(sbc.modulefunc.checkoutForm.change) === "undefined") {
          cfunc.showMsgBox("Click compute to continue.", "negative", "warning");
          return;
        }
        sbc.globalFunc.continueCheckout();
      },
      closeCheckout: function () {
        sbc.modulefunc.checkoutForm = { total: "", payment: "", payment2: "", change: "" };
        sbc.showCheckoutForm = false;
      }
    })';
  }
}