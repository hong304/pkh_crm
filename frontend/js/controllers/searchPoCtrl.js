'use strict';


function editPo(poId)
{
    var scope = angular.element(document.getElementById("queryInfo")).scope();
    scope.$apply(function () {
        scope.viewPurchaseOrder(poId);
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

    $scope.$on('$viewContentLoaded', function () {
        // initialize core components
        Metronic.initAjax();
        $scope.systeminfo = $rootScope.systeminfo;
        $scope.updateDataSet();
    });

    var queryPo = $scope.endpoint + "/queryPo.json";

    $scope.keyword = {
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
    };
   
       var today = new Date();
     var plus = today.getDay() == 6 ? 3 : 2;
     var currentDate = new Date(new Date().getTime() + 24 * 60 * 60 * 1000 * plus);
     var start_date = new Date(new Date().getTime() - 24 * 60 * 60 * 1000 * 1);
     
     var ymonth = start_date.getMonth() + 1;
     var yyear = start_date.getFullYear();
     var yday = start_date.getDate();
     
     var day = currentDate.getDate();
     var month = currentDate.getMonth() + 1;
     var year = currentDate.getFullYear();
     
     
     $("#startPodate").datepicker({
     rtl: Metronic.isRTL(),
     orientation: "left",
     autoclose: true
     });
     $("#startPodate").datepicker( "setDate", yyear + '-' + ymonth + '-' + yday);
     
     $("#endPodate").datepicker({
     rtl: Metronic.isRTL(),
     orientation: "left",
     autoclose: true
     });
     $("#endPodate").datepicker( "setDate", year + '-' + month + '-' + day );
     
     $scope.keyword.startPodate = yyear+'-'+ymonth+'-'+yday;
     $scope.keyword.endPodate = year+'-'+month+'-'+day;
     
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
        if($scope.keyword.poStatus == 0)
        {
             $scope.keyword.poStatus = '';
        }
        $scope.updateDataSet();
    }
    
  
    $scope.viewPurchaseOrder = function (poId)
    {
        $http.post($scope.endpoint + "/queryPo.json", {
            poCode: poId, mode: 'single'
        }).success(function (data) {
            $scope.order = data.po[0];
            $scope.invoiceinfo.poCode = data.po[0].poCode;
            $scope.invoiceinfo.poDate = data.po[0].poDate;
            $scope.invoiceinfo.etaDate = data.po[0].etaDate;
            $scope.invoiceinfo.actualDate = data.po[0].actualDate;
            $scope.invoiceinfo.receiveDate = data.po[0].receiveDate;
            $scope.invoiceinfo.remark = data.po[0].poRemark;
            $scope.invoiceinfo.poReference = data.po[0].poReference;
            $scope.invoiceinfo.poStatus = data.po[0].poStatus;
            $scope.invoiceinfo.poAmount = data.po[0].poAmount;

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

        $("#poDetails").modal({backdrop: 'static'});

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


    $scope.updateDataSet = function () {
        $(document).ready(function () {

            if (!$scope.firstload)
            {
                $("#datatable_ajax").dataTable().fnDestroy();
            }
            else
            {
                $scope.firstload = false;
            }


            $('#datatable_ajax').dataTable({
                // "dom": '<"row"f<"clear">>rt<"bottom"ip<"clear">>',

                "sDom": '<"row"<"col-sm-6"<"pull-left"p>><"col-sm-6"f>>rt<"row"<"col-sm-12"i>>',
                "bServerSide": true,
                "ajax": {
                    "url": queryPo, // ajax source
                    "type": 'POST',
                    "data": {mode: "collection", filterData: $scope.keyword},
                    "xhrFields": {withCredentials: true}
                },
                "iDisplayLength": 50,
                "pagingType": "full_numbers",
                "language": {
                    "lengthMenu": "顯示 _MENU_ 項結果",
                    "zeroRecords": "沒有匹配結果",
                    "sEmptyTable": "沒有匹配結果",
                    "info": "顯示第 _START_ 至 _END_ 項結果，共 _TOTAL_ 項",
                    "infoEmpty": "顯示第 0 至 0 項結果，共 0 項",
                    "infoFiltered": "(filtered from _MAX_ total records)",
                    "Processing": "處理中...",
                    "Paginate": {
                        "First": "首頁",
                        "Previous": "上頁",
                        "Next": "下頁",
                        "Last": "尾頁"
                    }
                },
                "columns": [
                    {"data": "poCode", "width": "10%"},
                    {"data": "poDate", "width": "10%"},
                    {"data": "etaDate", "width": "10%"},
                    {"data": "actualDate", "width": "10%"},
                    {"data": "supplierName", "width": "10%"},
                    {"data": "poAmount", "width": "10%"},
                    {"data": "poStatus", "width": "10%"},
                    {"data": "username", "width": "10%"},
                    {"data": "updated_at", "width": "10%"},
                    {"data": "link", "width": "10%"}
                ]

            });

        });
    };



    $scope.$on('handleSupplierUpdate', function () {
        // received client selection broadcast. update to the invoice portlet
        $scope.an = true;
        $scope.keyword.supplier = SharedService.supplierCode === undefined ? '' : SharedService.supplierCode;
       
        $scope.updateDataSet();
    });

    $scope.click = function (event)
    {

        $scope.keyword.sorting = event.target.id;

        if ($scope.keyword.current_sorting == 'asc') {
            $scope.keyword.current_sorting = 'desc';
        } else {
            $scope.keyword.current_sorting = 'asc';
        }

        $scope.updateDataSet();
    }


});