'use strict';

function editPo(poId,location)
{
    var scope = angular.element(document.getElementById("queryInfo")).scope();
    scope.$apply(function () {
        scope.viewPurchaseOrder(poId,location);
        scope.viewUpdateRecord(poId,location);
    });
}


app.controller('queryPOCtrl', function($scope, $rootScope, $http, SharedService, $location, $timeout, $interval,$state) {



    var query = endpoint + '/queryPo.json';

    $scope.invoicepaid= [];
    $scope.invoiceStructure = {
        'paid' : ''
    }
    var fetchDataDelay = 500;   // milliseconds
    var fetchDataTimer;
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

    $scope.keywordpo.startPodate = yyear+'-'+ymonth+'-'+yday;
    $scope.keywordpo.endPodate = year+'-'+month+'-'+day;


    $(document).ready(function(){
        $('#queryInfo').keydown(function (e) {
            if (e.keyCode == 13) { //Enter
                $scope.updateDataSet();
            }

        });

    });


    $scope.$on('$viewContentLoaded', function () {
        // initialize core components
        Metronic.initAjax();
        $scope.systeminfo = $rootScope.systeminfo;
        //$scope.updateDataSet();

    });

    $scope.$on('handleSupplierUpdate', function(){
        console.log(SharedService);
        $scope.keywordpo.supplier = SharedService.supplierCode === undefined ? '' : SharedService.supplierCode;
        $scope.keywordpo.supplierName = SharedService.supplierName;
        $scope.updateDataSet();
    });

    $scope.updateStatus = function()
    {
        if($scope.keywordpo.poStatus == 0)
        {
            $scope.keywordpo.poStatus = '';
        }
        $scope.updateDataSet();
    }

    $scope.updateByDelay = function()
    {
        $timeout.cancel(fetchDataTimer);
        fetchDataTimer = $timeout(function () {
            $scope.updateDataSet();
        }, fetchDataDelay);
    }

    $scope.clearPoSearch = function()
    {
        $scope.keywordpo.supplier = "";
        $scope.keywordpo.supplierName = '';
        $scope.keywordpo.poCode = '';
        $scope.updateDataSet();
    }

    $scope.viewUpdateRecord = function(poId)
    {
        $http.post($scope.endpoint + "/queryPoUpdate.json", {
            poCode: poId
        }).success(function (data) {
            $scope.poaduit = data;
        });
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
    $scope.updateDataSet = function()
    {



        $scope.invoicepaid[0] = $.extend(true, {}, $scope.invoiceStructure);
        $scope.invoicepaid[0]['paid'] = 0;

    	Metronic.blockUI();
    	var grid = new Datatable();


    	//var info = grid.page.info();
    	if(!$scope.firstload)
		{
    		$("#datatable_ajax").dataTable().fnDestroy();
		}
    	else
		{
    		$scope.firstload = false;
		}
        grid.init({
            src: $("#datatable_ajax"),
            onSuccess: function (grid) {
                // execute some code after table records loaded
            	Metronic.unblockUI();
            },
            onError: function (grid) {
                // execute some code on network or other general error  
            	Metronic.unblockUI();
            },
            loadingMessage: 'Loading...',
            dataTable: { // here you can define a typical datatable settings from http://datatables.net/usage/options 


                "bStateSave": false, // save datatable state(pagination, sort, etc) in cookie.
                "bServerSide": true,

                "lengthMenu": [
                    [20, 50],
                    [20, 50] // change per page values here
                ],
                "pageLength": 50, // default record count per page

                "ajax": {
                    "url": query, // queryPo.json
                    "type": 'POST',
                    "data": {mode: "collection", filterData: $scope.keywordpo},
                    "xhrFields": {withCredentials: true}
                },
                "language": {
                    "lengthMenu": "顯示 _MENU_ 項結果",
                    "zeroRecords": "沒有匹配結果",
                    "sEmptyTable":     "沒有匹配結果",
                    "info": "顯示第 _START_ 至 _END_ 項結果，共 _TOTAL_ 項",
                    "infoEmpty": "顯示第 0 至 0 項結果，共 0 項",
                    "infoFiltered": "(filtered from _MAX_ total records)",
                    "Processing":   "處理中...",
                    "Paginate": {
                        "First":    "首頁",
                        "Previous": "上頁",
                        "Next":     "下頁",
                        "Last":     "尾頁"
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
                ],

                "order": [
                    [1, "asc"]
                ] // set first column as a default sort by asc
            }
        });

    }






});