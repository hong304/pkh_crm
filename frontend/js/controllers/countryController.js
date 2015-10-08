'use strict';

function editCountry(ipfId)
{
	var scope = angular.element(document.getElementById("queryInfo")).scope();
    scope.$apply(function () {
    	scope.editCountry(ipfId);
    });
}

app.controller('countryController', function($scope, $rootScope, $http, SharedService, $location, $timeout, $interval) {
	
	var querytarget = endpoint + '/queryCountry.json';
	var iutarget = endpoint + '/manipulateCountry.json';
	
	$scope.info_def = {
                        'id' : false,
			'countryId'		:	'',  //Since the ipfId is auto increment in db , so it is passed by false
			'countryCode'		:	'',
                        'countryIdOrig' : '',
		
	};
	
	$scope.info = {};
        
          $scope.filterData = 
            {
                'sorting' :'',
                'current_sorting' :'desc'
            }
	
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
       
    $scope.editCountry = function(countryId)
    {
    	
    	$http.post(querytarget, {mode: "single", countryId: countryId})
    	.success(function(res, status, headers, config){    
    		$scope.info = $.extend(true, {}, $scope.info_def);
    		$scope.info = res;
                $scope.info.countryIdOrig = $scope.info.countryId;
    		$("#countryFormModal").modal({backdrop: 'static'});
    		$('.date-picker').datepicker({
                rtl: Metronic.isRTL(),
                orientation: "left",
                autoclose: true
            });
           
    	});
    	
    	
    }
    
  
    
    $scope.click = function(event)
    {
       //  alert(event.target.id);
         $scope.filterData.sorting = event.target.id;
       
    
            if ($scope.filterData.current_sorting == 'asc'){
               // $scope.filterData.sorting_method = 'desc';
                $scope.filterData.current_sorting = 'desc';
            }else{
               $scope.filterData.current_sorting = 'asc';
            }
                
         $scope.updateDataSet();
    }
    
    $scope.addCountry = function()
    {
    	$scope.info = $.extend(true, {}, $scope.info_def);
    	$("#countryFormModal").modal({backdrop: 'static'});
    	$('.date-picker').datepicker({
            rtl: Metronic.isRTL(),
            orientation: "left",
            autoclose: true
        });
    	
    }
    
    $scope.submitIPFForm = function()
    {
    	
    		$http.post(iutarget, {info: $scope.info})
        	.success(function(res, status, headers, config){    
                        if(!res)
                        {
                            $("#countryFormModal").modal('hide');
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

                "bServerSide": true,

                "ajax": {
                    "url": querytarget, // ajax source
                    "type": 'POST',
                    "data": {mode: "collection",filterData : $scope.filterData},
                    "xhrFields": {withCredentials: true}
                },
                "iDisplayLength": 25,
                "pagingType": "full_numbers",
                "fnDrawCallback" : function() {
                   window.alert = function() {};
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
                    { "data": "countryId" },
                    { "data": "countryName" },
                    { "data": "link" },

                ],
               

            });



        });
    };


});