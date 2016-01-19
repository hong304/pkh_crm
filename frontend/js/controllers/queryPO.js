'use strict';

app.controller('queryPOCtrl', function($scope, $rootScope, $http, SharedService, $location, $timeout, $interval,$state) {



    var query = endpoint + '/queryPo.json';

    $scope.invoicepaid= [];
    $scope.invoiceStructure = {
        'paid' : ''
    }

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

    $scope.clearPoSearch = function()
    {
        $scope.keywordpo.supplier = "";
        $scope.keywordpo.supplierName = '';
        $scope.updateDataSet();
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
                    "url": query, // ajax source
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