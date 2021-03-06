'use strict';

function editIPF(ipfId)
{
	var scope = angular.element(document.getElementById("queryInfo")).scope();
    scope.$apply(function () {
    	scope.editIPF(ipfId);
    });
}

app.controller('invoicePrintMaintenanceCtrl', function($scope, $rootScope, $http, SharedService, $location, $timeout, $interval) {
	
	var querytarget = endpoint + '/queryIPF.json';
	var iutarget = endpoint + '/manipulateIPF.json';
	
	$scope.info_def = {
			'ipfId'		:	false, 
			'from'		:	'',
			'to'	:	'',
			'size'	:	'',
			'advertisement'	:	'',
	};
	
	$scope.info = {};
	
    $scope.$on('$viewContentLoaded', function() {   
    	
        Metronic.initAjax();        
        $scope.systeminfo = $rootScope.systeminfo;   
        $scope.updateDataSet();
        
        
    });
    
    $scope.$watch(function() {
    	return $rootScope.systeminfo;
  	}, function() {
  		$scope.systeminfo = $rootScope.systeminfo; 
  		$('.date-picker').datepicker({
            rtl: Metronic.isRTL(),
            orientation: "left",
            autoclose: true
        });
  	}, true);
       
    $scope.editIPF = function(ipfId)
    {
    	
    	$http.post(querytarget, {mode: "single", ipfId: ipfId})
    	.success(function(res, status, headers, config){    
    		$scope.info = $.extend(true, {}, $scope.info_def);
    		$scope.info = res;
    		
    		$("#ipfFormModal").modal({backdrop: 'static'});
    		$('.date-picker').datepicker({
                rtl: Metronic.isRTL(),
                orientation: "left",
                autoclose: true
            });
    	});
    	
    	
    }
    
    $scope.addIPF = function()
    {
    	$scope.info = $.extend(true, {}, $scope.info_def);
    	$("#ipfFormModal").modal({backdrop: 'static'});
    	$('.date-picker').datepicker({
            rtl: Metronic.isRTL(),
            orientation: "left",
            autoclose: true
        });
    	
    }
    
    $scope.submitIPFForm = function()
    {
    	if(
    			$scope.info.from == "" ||
    			$scope.info.to == "" ||
    			$scope.info.size == "" ||
    			$scope.info.advertisement == "" 
    	)
    	{
    		alert('請輸入所需資料');
    	}
    	else
    	{
    		$http.post(iutarget, {info: $scope.info})
        	.success(function(res, status, headers, config){    
        		    		
        		$("#ipfFormModal").modal('hide');
        		$scope.updateDataSet();
        		
        	});
    	}
    	
    }




    $scope.updateDataSet = function () {
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

            // "dom": '<"row"f<"clear">>rt<"bottom"ip<"clear">>',

            dataTable: {
                "bStateSave": false, // save datatable state(pagination, sort, etc) in cookie.


                "lengthMenu": [
                    [20, 50],
                    [20, 50] // change per page values here
                ],
                "pageLength": 20, // default record count per page

                "ajax": {
                    "url": querytarget, // ajax source
                    "type": 'POST',
                    "data": {mode: "collection"},
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
                    { "data": "from", "width":'10%' },
                    { "data": "to" , "width":'10%'},
                    { "data": "size", "width":'5%' },
                    { "data": "advertisement", "width":'70%' },
                    { "data": "link", "width":'5%' },
                ]

            }
        });

    }



});