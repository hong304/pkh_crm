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
        $(document).ready(function() {

            if(!$scope.firstload)
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

              //  "bServerSide": true,

                "ajax": {
                    "url": querytarget, // ajax source
                    "type": 'POST',
                    "data": {mode: "collection"},
                    "xhrFields": {withCredentials: true}
                },
                "iDisplayLength": 25,
                "pagingType": "full_numbers",
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
                    { "data": "from" },
                    { "data": "to" },
                    { "data": "size" },
                    { "data": "link" },

                ],
                "order": [
                    [0, "desc"],
                ]

            });



        });
    };


});