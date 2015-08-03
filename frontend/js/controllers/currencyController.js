'use strict';

function editCurrency(currencyId)
{
	var scope = angular.element(document.getElementById("queryInfo")).scope();
    scope.$apply(function () {
    	scope.editCurrency(currencyId);
    });
}

app.controller('currencyController', function($scope, $rootScope, $http, SharedService, $location, $timeout, $interval) {
	
	var querytarget = endpoint + '/queryCurrency.json';
	var iutarget = endpoint + '/manipulateCurrency.json';
	
	$scope.info_def = {
                        'id' : false,
			'currencyId'		:	'',  //Since the ipfId is auto increment in db , so it is passed by false
			'currencyName'		:	'',
                        'currencyIdOrig' : '',
		
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
       
    $scope.editCurrency = function(currencyId)
    {
    	
    	$http.post(querytarget, {mode: "single", currencyId: currencyId})
    	.success(function(res, status, headers, config){    
    		$scope.info = $.extend(true, {}, $scope.info_def);
    		$scope.info = res;
                $scope.info.currencyIdOrig = $scope.info.currencyId;
    		$("#currencyFormModal").modal({backdrop: 'static'});
    		$('.date-picker').datepicker({
                rtl: Metronic.isRTL(),
                orientation: "left",
                autoclose: true
            });
           
    	});
    	
    	$scope.actionNow = "update";
    }
    
    $scope.addCurrency = function()
    {
    	$scope.info = $.extend(true, {}, $scope.info_def);
    	$("#currencyFormModal").modal({backdrop: 'static'});
    	$('.date-picker').datepicker({
            rtl: Metronic.isRTL(),
            orientation: "left",
            autoclose: true
        });
    	$scope.actionNow = "create";
    }
    
    $scope.submitIPFForm = function()
    {
    	        if($scope.actionNow == "create")
                {
                    $scope.info.currencyIdOrig = "";
                }
    		$http.post(iutarget, {info: $scope.info})
        	.success(function(res, status, headers, config){    
                        if(!res)
                        {
                            $("#currencyFormModal").modal('hide');
        		    $scope.updateDataSet();
                        }else
                        {
                             alert(res);
                        }

        	});
    	
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
                    { "data": "currencyId" },
                    { "data": "currencyName" },
                    { "data": "link" },

                ],
                "order": [
                    [0, "asc"],
                ]

            });



        });
    };


});