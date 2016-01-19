'use strict';


function editPo(poId,location)
{
    var scope = angular.element(document.getElementById("queryInfo")).scope();
    scope.$apply(function () {
        scope.viewPurchaseOrder(poId,location);
        scope.viewUpdateRecord(poId,location);
    });
}

function updateStatus(statusNum)
{
    var scope = angular.element(document.getElementById("queryInfo")).scope();
    scope.$apply(function () {
        scope.viewPurchaseOrder(statusNum);
    });

}

app.controller('searchPoCtrl', function ($scope, $rootScope, $http, SharedService, $location, $timeout, $interval) {


    $scope.firstload = true;
    $scope.autoreload = false;



    var queryPo = $scope.endpoint + "/queryPo.json";
    
 

    $scope.keywordpo = {
        supplier: '',
        poCode: '',
        poStatus: '',
        poDate: '',
        sorting: '',
        current_sorting: 'desc',
        endPodate:'',
        startPodate:'',
    };

    $scope.invoiceinfo = {
        poCode: '',
        poDate: '',
        etaDate: '',
        actualDate: '',
        receiveDate: '',
        remark: '',
        poStatus: '',
        poAmount: '',
        invoice_item: '',
        invoice: '',
        location:'',
    };
    


        if($location.search().poDate  !== undefined)
        {
            $scope.keywordpo.endPodate = $location.search().poDate;
            $scope.keywordpo.startPodate = $location.search().poDate;
            $scope.keywordpo.sorting = "purchaseorders.updated_at";  
        }
     
     $scope.$watch(function() {
     return $rootScope.systeminfo;
     }, function() {
     $scope.systeminfo = $rootScope.systeminfo;
     }, true);
     /*
     $scope.initializeTable = function()
     {
     $scope.getData();
     if($scope.autoreload)
     {
     var promise =  $interval($scope.getDate(), 15000);
     }
     
     $timeout(function(){
     $scope.systemInfo = SharedService.SystemInfo;
     }, 1000);
     
     }
     
     $scope.getData = function() {
     
     var endpoint = $scope.endpoint + "/getInvoices.json";
     
     var $url = $location.search();
     var $parm = {};
     
     
     $http.post(endpoint, $url)
     .success(function(data){
     $scope.dataTable = data;
     
     if($scope.firstload)
     {
     $timeout(function(){
     SharedService.initTable();
     }, 500);
     $scope.firstload = false;
     }
     else
     {
     $('#datatable').dataTable().fnDestroy(); 
     }
     
     });
     }
     
     $scope.$on('$locationChangeSuccess', function(){
     $scope.getData();     	
     });
     
     
     
     // --------------------- for approval modal
     $scope.toggle = function(index)
     {
     jQuery("#cost_" + index).toggle();
     jQuery("#controlcost_" + index).css('display', 'none');
     }
     
     $scope.manipulate = function(action, invoiceId)
     {
     var approvalJson = $scope.endpoint + "/manipulateInvoiceStatus.json";
     
     $http.post(approvalJson, {
     action: "approval",
     status:	action,
     target:	invoiceId
     }).success(function(data) {
     $("#invoiceNumber_" + invoiceId).remove();
     });
     
     $("#productDetails").modal('toggle');
     }
     
     
     
     
     
     $("#invoiceNumber_" + invoiceId).remove();
     $("#productDetails").modal('hide');
     
     }
     
     
     $scope.displayInvoiceItem = function(invoice)
     {
     invoice.invoice_item.forEach(function(e){
     var stdPrice = e.productInfo.productStdPrice[e.productQtyUnit];
     
     if((e.productPrice * (100-e.productDiscount)/100) < stdPrice)
     {
     e.requireApproval = true;
     e.backgroundcode = "background:#FFCCCF";
     }
     else
     {
     e.requireApproval = false; 
     e.backgroundcode = "";
     }
     //console.log(e);
     });
     
     $("#productDetails").modal({backdrop: 'static'});
     
     
     console.log(invoice);
     $scope.previewItem = invoice;
     $scope.systemInfo = SharedService.SystemInfo;
     }
     */
    $scope.updateStatus = function()
    {
        if($scope.keywordpo.poStatus == 0)
        {
             $scope.keywordpo.poStatus = '';
        }
        $scope.updateDataSet();
    }
    
  
    $scope.viewPurchaseOrder = function (poId,location)
    {
        $http.post($scope.endpoint + "/queryPo.json", {
            poCode: poId, mode: 'single'
        }).success(function (data) {
           console.log(data);
            $scope.order = data.po[0];
            $scope.invoiceinfo.location = data.po[0].location;
            $scope.invoiceinfo.poCode = data.po[0].poCode;
            $scope.invoiceinfo.poDate = data.po[0].poDate;
            $scope.invoiceinfo.etaDate = data.po[0].etaDate;
            $scope.invoiceinfo.actualDate = data.po[0].actualDate;
            $scope.invoiceinfo.receiveDate = data.po[0].receiveDate;
            $scope.invoiceinfo.remark = data.po[0].poRemark;
            $scope.invoiceinfo.poReference = data.po[0].poReference;
            $scope.invoiceinfo.poStatus = data.po[0].poStatus;
            $scope.invoiceinfo.poAmount = data.po[0].poAmount;
            $scope.invoiceinfo.supplierName = data.po[0].supplierName;
            $scope.invoiceinfo.currencyName = data[0].currencyName;
            $scope.invoiceinfo.invoice_item = data.items;
            $scope.invoiceinfo.invoice = data.po[0];
        //    $scope.invoiceinfo.unitprice = data.items[0].unitprice;

            $scope.invoiceinfo.allowance_1 = data.po[0].allowance_1;
            $scope.invoiceinfo.allowance_2 = data.po[0].allowance_2;
            $scope.invoiceinfo.discount_1 = data.po[0].discount_1;
            $scope.invoiceinfo.discount_2 = data.po[0].discount_2;
            $scope.invoiceinfo.poAmount = data.po[0].poAmount;
            $scope.invoiceinfo.totalsAmount = 0;
            for(var i = 0;i<data.items.length;i++)
            {
                $scope.invoiceinfo.totalsAmount += (data.items[i].productQty * data.items[i].unitprice * (100-data.items[i].discount_1)/100 * (100-data.items[i].discount_2)/100 * (100-data.items[i].discount_3)/100) - data.items[i].allowance_1 - data.items[i].allowance_2 - data.items[i].allowance_3;
            }
        })
        if(location == 1)
            $scope.locationShow = true;
        else if(location == 2)
            $scope.locationShow = false;
        $("#poDetails").modal({backdrop: 'static'});

    }
    
    $scope.viewUpdateRecord = function(poId)
    {
        $http.post($scope.endpoint + "/queryPoUpdate.json", {
            poCode: poId
        }).success(function (data) {
            $scope.poaduit = data;
        });
    }
    
    $scope.genA4Invoice = function(poCode,lang)
    {
       window.open(endpoint + "/printPo.json?poCode=" + poCode + "&lang=" + lang); //window open is a new tab 
    }

    $scope.goEdit = function (invoiceId)
    {
        $(document).ready(function () {
            if ($scope.invoiceinfo.poStatus == 99)
            {
                alert("此記錄無法修改");

            } else
            {
                $location.url("/PoMain?poCode=" + invoiceId);
            }
        });
        
    }

    $scope.voidPo = function (poCode)
    {
        if ($scope.invoiceinfo.poStatus == 1)
        {
            $http.post($scope.endpoint + "/voidPo.json", {
                poCode: poCode,
                updateStatus: 'delete'
            }).success(function (data) {
                $scope.updateDataSet();
                $scope.invoiceinfo.poStatus = 99;
                alert("這個記錄被刪除");
            });
        } else
        {
            alert("此記錄無法刪除");
        }

    }
    



    $scope.$on('handleSupplierUpdate', function () {
        // received client selection broadcast. update to the invoice portlet
        $scope.an = true;
        $scope.keywordpo.supplier = SharedService.supplierCode === undefined ? '' : SharedService.supplierCode;
  
        $scope.updateDataSet();
    });

    $scope.click = function (event)
    {

        $scope.keywordpo.sorting = event.target.id;

        if ($scope.keywordpo.current_sorting == 'asc') {
            $scope.keywordpo.current_sorting = 'desc';
        } else {
            $scope.keywordpo.current_sorting = 'asc';
        }

        $scope.updateDataSet();
    }
    

    $scope.clearPoSearch = function()
    {
        $scope.keywordpo.supplier = "";
        $scope.updateDataSet();
    }
    
    $scope.overseaPoGetISnvoice = function(poCode)
    {
        window.open(endpoint + "/overseaPoGetISnvoice.json?poCode=" + poCode); //window open is a new tab 
    }

});