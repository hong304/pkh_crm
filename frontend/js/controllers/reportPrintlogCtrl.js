'use strict';

function reprint(i)
{
    var scope = angular.element(document.getElementById("queryInfo")).scope();
    scope.$apply(function () {
        scope.reprint(i);
    });
}

app.controller('reportPrintlogCtrl', function($scope, $http, SharedService, $timeout, $location, $sce) {

	var querytarget = endpoint + "/getPrintLog.json";

	$scope.filterData = {
			'zone' : '',
            'onedate' : ''
	};

    var today = new Date();
   // var plus = today.getDay() == 6 ? 2 : 1;

   // var currentDate = new Date(new Date().getTime() + 24 * 60 * 60 * 1000 * plus);
   // if(today.getHours() < 12)
   // {
        var nextDay = today;
   // }
   // else
  //  {
   //     var nextDay = currentDate;
   // }
    var day = nextDay.getDate();
    var month = nextDay.getMonth() + 1;
    var year = nextDay.getFullYear();
	
    $scope.$on('$viewContentLoaded', function() {
        Metronic.initAjax();


        $(".date").datepicker({
            rtl: Metronic.isRTL(),
            orientation: "left",
            autoclose: true
        });
        $(".date").datepicker( "setDate", year + '-' + month + '-' + day );
        $scope.updateDataSet();
    });



	$scope.$watch(function() {
	  //return $scope.filterData;
	}, function() {
	  $scope.updateDataSet();
	}, true);

    $scope.updateZone = function(){
        $scope.updateDataSet();
    }


    $scope.updateDate = function(){
        $scope.updateDataSet();
    }

    $scope.reprint = function(i){

        bootbox.dialog({
            message: "將會重印訂單",
            title: "重印訂單",
            buttons: {
                success: {
                    label: "取消",
                    className: "green",
                    callback: function() {

                    }
                },
                danger: {
                    label: "確定重印",
                    className: "red",
                    callback: function() {
                        $http.post(querytarget, {mode: 'reprint', filterData: i})
                            .success(function(res, status, headers, config){
                                 $scope.updateDataSet();
                            });
                    }
                }
            }
        });



    }


    $scope.sendFile = function(file)
    {
    	
    	if(file.warning != false)
		{
    		bootbox.confirm(file.warning, function(result) {
    			if(result == true)
    			{
    				$scope.sendRealFile(file.type);
    			}
        	}); 
		}
    	else
    	{
    		$scope.sendRealFile(file.type);
    	}
    	
    }
    
    $scope.sendRealFile = function()
    {

        $http.post(querytarget, {reportId: 'vanselllist', output: "create", filterData: $scope.filterData,data:$scope.qty})
            .success(function(res, status, headers, config){

                var queryObject = {
                    filterData	:	$scope.filterData,
                    reportId	:	'vanselllist',
                    output		:	'pdf'
                };
                var queryString = $.param( queryObject );

                window.open(endpoint + "/getVansellreport.json?" + queryString);

            });



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


                "bStateSave": true, // save datatable state(pagination, sort, etc) in cookie.

                "lengthMenu": [
                    [20, 50],
                    [20, 50] // change per page values here
                ],
                "pageLength": 50, // default record count per page
                "ajax": {
                    "url": querytarget, // ajax source
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
                    { "data": 'job_id' },
                    { "data": 'target_path' },
                    { "data": 'zone.zoneName' },
                    { "data": 'shift' },
                    { "data": "updated_at" },
                    { "data": "count" },

                     { "data": "link" },
                    {"data": "view"},

                ],

                "order": [
                    [1, "asc"]
                ] // set first column as a default sort by asc
            }
        });

    }
    
});