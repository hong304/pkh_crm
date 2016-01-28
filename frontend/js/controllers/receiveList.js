'use strict';

Metronic.unblockUI();



app.controller('receiveList', function($rootScope, $scope, $http, $timeout, SharedService, $location, $interval, $window, $state,$stateParams) {
    /* Register shortcut key */

    var querytarget = endpoint + '/queryReceiving.json';
    $scope.receive = {
        receivingId:'',
        receiveDate:'',
        poCode: '',
        supplierName:'',
        countryName:'',
        currencyId:'',
        status:'',
        poRemark:'',
        poStatus:'1',
        supplier:'',
        feight_cost:0,
        feight_local_cost:0,
        local_cost:0,
        total_cost:0,
        exchangeRate:0,
        shippingId:'',
        containerId:'',
        currencyName:'',
        hk_local_cost:0,
        shippingdbid:'',
        location:'',
    };
    
    $scope.filterData  = {
        startReceiveDate:'',
        endReceiveDate:'',
        location:'',
        supplier:'',
        productId:'',
        products:'',
        poCode : '',
    };

    var fetchDataDelay = 500;   // milliseconds
    var fetchDataTimer;

    var today = new Date();
    var plus = today.getDay() == 6 ? 2 : 1;

    var min = today.getDay() == 1 ? 3 : 2;

    var currentDate = new Date(new Date().getTime() + 24 * 60 * 60 * 1000 * plus);
    var start_date = new Date(new Date().getTime() - 24 * 60 * 60 * 1000 * min);

    var ymonth = start_date.getMonth() + 1;
    var yyear = start_date.getFullYear();
    var yday = start_date.getDate();

    var day = currentDate.getDate();
    var month = currentDate.getMonth() + 1;
    var year = currentDate.getFullYear();


    $("#startReceiveDate").datepicker({
        rtl: Metronic.isRTL(),
        orientation: "left",
        autoclose: true
    });
    $("#startReceiveDate").datepicker( "setDate", yyear + '-' + ymonth + '-' + yday);

    $("#endReceiveDate").datepicker({
        rtl: Metronic.isRTL(),
        orientation: "left",
        autoclose: true
    });
    $("#endReceiveDate").datepicker( "setDate", year + '-' + month + '-' + day );

    $scope.filterData.startReceiveDate = yyear+'-'+ymonth+'-'+yday;
    $scope.filterData.endReceiveDate = year+'-'+month+'-'+day;


    $scope.$on('$viewContentLoaded', function () {

    });


    $(document).ready(function(){
        $('#queryInfo').keydown(function (e) {
            if (e.keyCode == 13) { //Enter
                $scope.updateDataSet();
            }

        });

    });

    $scope.$on('handleSupplierUpdate', function(){
        $scope.filterData.supplier = SharedService.supplierCode === undefined ? '' : SharedService.supplierCode;
        $scope.filterData.supplierCode = SharedService.supplierName;
        $scope.updateDataSet();
    });

$scope.findDate = function(){
    $scope.updateDataSet();
}

    $scope.clearSearch = function()
    {
        $scope.filterData.supplier = "";
        $scope.filterData.supplierCode = '';
        $scope.updateDataSet();
    }

    $scope.updateByDelay = function()
    {
        $timeout.cancel(fetchDataTimer);
        fetchDataTimer = $timeout(function () {
            $scope.updateDataSet();
        }, fetchDataDelay);
    }

    $scope.updateDataSet = function()
    {

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

                    "lengthMenu": [
                        [20, 50],
                        [20, 50] // change per page values here
                    ],
                    "pageLength": 50, // default record count per page
                    "ajax": {
                        "url": querytarget, // queryReceiving.json
                        "type": 'POST',
                        "data": {filterData: $scope.filterData, mode: "collection"},
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


                        { "data": "receivingId" ,"width": "5%"},
                        { "data": "purchaseorder.supplier.supplierName","width": "15%" },
                        { "data": "purchaseorder.supplier.countryId" ,"width": "5%"},
                        { "data": "poCode","width": "6%" },
                        { "data": "shippingId","width": "8%" },
                        { "data": "containerId" ,"width": "15%"},
                        { "data": "receiving_date","width": "8%" },
                        { "data": "productId","width": "5%" },
                        { "data": "rec_good_qty","width": "5%" },
                        { "data": "expiry_date","width": "8%" },

                    ],

                    "order": [
                        [1, "asc"]
                    ] // set first column as a default sort by asc
                }
            });

    }

        $scope.checkProduct = function(ele)
        {
            $("#selectR").attr('disabled',false);
              var target = endpoint + '/getAllProducts.json';
              $http.post(target, {productId:ele}).success(function(res) {
                  if(typeof res == "object")
                  {
                     SharedService.setValue('productId', $scope.filterData.productId, 'handleReUpdate');
                     SharedService.setValue('productName', $scope.filterData.productName, 'handleReUpdate');
                     SharedService.setValue('products', res, 'handleReUpdate');
                   //  $("#selectR").attr('disabled',false);
                  }else 
                  {

                      $scope.filterData.productName = "";
                  }
                      
              });
        }
        

        


});