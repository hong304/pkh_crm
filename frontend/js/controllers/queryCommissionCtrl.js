'use strict';
app.controller('queryCommissionCtrl', function($scope, $rootScope, $http, SharedService, $location, $timeout, $interval) {

   // var querytarget = endpoint + '/queryCommission.json';
    var fetchDataDelay = 250;   // milliseconds
    var fetchDataTimer;

    $scope.firstload = true;
   $scope.filterData= {
        'productName_chi' : '',
       'name' :'',
       'phone' : '',
       'customerId' :'',
       'product' : '',
       'product_name' : '',
       'zone' : ''

    };


    var today = new Date();
    var plus = today.getDay() == 6 ? 2 : 1;

    var currentDate = new Date(new Date().getTime() + 24 * 60 * 60 * 1000 * plus);
    if(today.getHours() < 12)
    {
        var nextDay = today;
    }
    else
    {
        var nextDay = currentDate;
    }
    var day = nextDay.getDate();
    var month = nextDay.getMonth() + 1;
    var year = nextDay.getFullYear();



    $scope.$watch('filterData', function() {
        console.log($scope.filterData);
        $timeout.cancel(fetchDataTimer);
        fetchDataTimer = $timeout(function () {
            $scope.updateDataSet();
        },fetchDataDelay);

    }, true);

    $scope.$on('$viewContentLoaded', function() {
        Metronic.initAjax();
        $scope.systeminfo = $rootScope.systeminfo;

        $("#deliveryDate").datepicker({
            rtl: Metronic.isRTL(),
            orientation: "left",
            autoclose: true
        });
        $("#deliveryDate").datepicker( "setDate", year + '-' + month + '-' + day );

        $("#deliveryDate1").datepicker({
            rtl: Metronic.isRTL(),
            orientation: "left",
            autoclose: true
        });
        $("#deliveryDate1").datepicker( "setDate", year + '-' + month + '-' + day );

    });

    $scope.$watch(function() {
        return $rootScope.systeminfo;
    }, function() {
        $scope.systeminfo = $rootScope.systeminfo;
        $scope.updateDataSet();
    }, true);


    $scope.sendRealFile = function()
    {

        var queryObject = {
            filterData	:	$scope.filterData,
            mode	:	"csv"

        };

        var queryString = $.param( queryObject );
        window.open(endpoint + "/queryCommission.json?" + queryString);


    }

    $scope.updateDataSet = function()
    {

        /*
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
                    "url": querytarget, // ajax source
                    "type": 'POST',
                    "data": {filterData: $scope.filterData, mode: "collection"},
                    "xhrFields": {withCredentials: true},
                    "async": false
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


                    { "data": "productId" },
                    { "data": "productName_chi" },
                    { "data": "productQtys" },
                    { "data": "productUnitName" },

                ],

                "order": [
                    [1, "asc"]
                ] // set first column as a default sort by asc
            }
        });
*/
    }






});