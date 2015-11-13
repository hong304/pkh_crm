'use strict';

Metronic.unblockUI();



app.controller('arrivedContainer', function($rootScope, $scope, $http, $timeout, SharedService, $location, $interval, $window, $state,$stateParams) {
 
 
   $scope.$on('$viewContentLoaded', function () {
        // initialize core components
        Metronic.initAjax();
        $scope.systeminfo = $rootScope.systeminfo;
        $scope.updateDataSet();
        
    });
     $('#actualDateStart,#actualDateEnd').datepicker({
          rtl: Metronic.isRTL(),
          orientation: "left",
          autoclose: true
     });
     
     var querytarget = endpoint + '/jqueryGetArrived.json';
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
                    "url": querytarget, // ajax source
                    "type": 'POST',
                    "data": {mode: "collection", filterData: $scope.keywordpo},
                    "xhrFields": {withCredentials: true}
                },
                "iDisplayLength": 50,
                "pagingType": "full_numbers",
                "fnDrawCallback" : function() {
                   window.alert = function() {};
                },
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
                    {"data": "shipCompany", "width": "10%"},
                   // {"data": "poDate", "width": "10%"},
                  //  {"data": "etaDate", "width": "10%"},
                  //  {"data": "actualDate", "width": "10%"},
                 //   {"data": "supplierName", "width": "10%"},
                 //   {"data": "poAmount", "width": "10%"},
                //    {"data": "poStatus", "width": "10%"},
                //    {"data": "username", "width": "10%"},
                //    {"data": "updated_at", "width": "10%"},
                //    {"data": "link", "width": "10%"}
                ]

            });
            
           
        });
    };
   
});
