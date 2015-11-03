'use strict';

function editExpenses (id)
{
    var scope = angular.element(document.getElementById("queryInfo")).scope();
    scope.$apply(function () {
        scope.editExpenses (id);
    });
}

app.controller('expensesController', function($scope, $rootScope, $http, SharedService, $location, $timeout, $interval) {

    $scope.filterData = {
        'deliverydate'	:	'',
        'deliverydate2'	:	'',
        'zone'			:	''

    };
    $scope.expenses = {};
    $scope.expenses_def={
        'id': '',
        'cost1' :'',
        'cost2' :'',
        'cost3' :'',
        'cost4' :'',
        'cost3_remark' :'',
        'cost4_remark' :'',
        'deliveryDate' :'',
        'zone' : ''
    }

    var query = endpoint + '/queryExpenses.json';

    var today = new Date();
    var plus = today.getDay() == 6 ? 3 : 2;

    var min = today.getDay() == 1 ? 2 : 1;

    var currentDate = new Date(new Date().getTime() + 24 * 60 * 60 * 1000 * plus);
    var start_date = new Date(new Date().getTime() - 24 * 60 * 60 * 1000 * min);

    var ymonth = start_date.getMonth() + 1;
    var yyear = start_date.getFullYear();
    var yday = start_date.getDate();

    var day = currentDate.getDate();
    var month = currentDate.getMonth() + 1;
    var year = currentDate.getFullYear();


    $("#deliverydate").datepicker({
        rtl: Metronic.isRTL(),
        orientation: "left",
        autoclose: true
    });
    $("#deliverydate").datepicker( "setDate", yyear + '-' + ymonth + '-' + yday);

    $("#deliverydate2").datepicker({
        rtl: Metronic.isRTL(),
        orientation: "left",
        autoclose: true
    });
    $("#deliverydate2").datepicker( "setDate", year + '-' + month + '-' + day );

    $scope.filterData.deliverydate = yyear+'-'+ymonth+'-'+yday;
    $scope.filterData.deliverydate2 = year+'-'+month+'-'+day;

    $scope.$on('$viewContentLoaded', function() {
        Metronic.initAjax();
        $scope.systeminfo = $rootScope.systeminfo;
        $scope.updateDataSet();
   });

    $scope.$watch(function() {
        return $rootScope.systeminfo;
    }, function() {
        $scope.systeminfo = $rootScope.systeminfo;
    }, true);

    $scope.editExpenses = function(id){


        $http.post(query, {mode: "single", id: id})
            .success(function(res, status, headers, config){
               // $scope.customerInfo = $.extend(true, {}, $scope.customerInfo_def);
                $scope.expenses = $.extend(true, {}, $scope.expenses_def);
                $scope.expenses = res;

                $('.date-picker').datepicker({
                    rtl: Metronic.isRTL(),
                    orientation: "left",
                    autoclose: true
                });

                $("#expensesFormModal").modal({backdrop: 'static'});


                var pos = $scope.systeminfo.availableZone.map(function(e) {
                    return e.zoneId;
                }).indexOf(res.zoneId);

                $scope.expenses.zone = $scope.systeminfo.availableZone[pos];
            });

    }
$scope.submitExpensesForm = function(){
    $scope.buttonText = '提交中...';
    $http({
        method: 'POST',
        url: endpoint + '/addExpenses.json',
        data: {filterData: $scope.expenses}
    }).success(function (res) {
      //  $scope.buttonText = '提交成功';
      //  $("#submitbutton").prop("disabled",true);
        $("#expensesFormModal").modal('hide');
        $scope.updateDataSet();
    });
}


    $scope.addExpenses = function()
    {
        $scope.buttonText = '提交';
        $scope.expenses = $.extend(true, {}, $scope.expenses_def);
        $('.date-picker').datepicker({
            rtl: Metronic.isRTL(),
            orientation: "left",
            autoclose: true
        });

        $('.date-picker').datepicker( "setDate" , yyear + '-' + ymonth + '-' + yday );
        $("#expensesFormModal").modal({backdrop: 'static'});

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
                    "url": query, // ajax source
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
                    { "data": "zoneId", "width":"3%" },
                    { "data": "deliveryDate", "width":"7%" },
                    { "data": "cost1", "width":"5%" },
                    { "data": "cost2",  "width":"5%"},
                    { "data": "cost3", "width":"5%" },
                    { "data": "cost3_remark", "width":"20%" },
                    { "data": "cost4", "width":"6%" },
                    { "data": "cost4_remark", "width":"20%" },
                    { "data": "updated_by_text", "width":"5%" },
                    { "data": "link", "width":"5%" }

                ],

                "order": [
                    [1, "asc"]
                ] // set first column as a default sort by asc
            }
        });

    }


});