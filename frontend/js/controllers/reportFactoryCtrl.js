'use strict';
Metronic.unblockUI();

app.controller('reportFactoryCtrl', function($scope, $http, SharedService, $timeout, $location, $sce) {
	
	var querytarget = endpoint + "/getReport.json";
	
	$scope.report = "";
	$scope.filterData = {
			
	};
	
    $scope.$on('$viewContentLoaded', function() {   
        Metronic.initAjax();        
        $scope.loadSetting();
        $scope.loadReport();
    });  
    
	$scope.$watch(function() {
	  return $scope.filterData;
	}, function() {
	  $scope.loadReport();
	}, true);
    
    $scope.loadSetting = function()
    {
    	
        
        $http.post(querytarget, {reportId: $location.search().id, filterData: $scope.filterData, output: "setting"})
    	.success(function(res, status, headers, config){    
    		$scope.setting = res;
    		Metronic.unblockUI();
    		$timeout(function(){
    			
    			
    			res.filterOptions.forEach(function(options){
    				if(options.type == "date-picker")
    				{
    					$("#" + options.id).datepicker({
    	    	            rtl: Metronic.isRTL(),
    	    	            orientation: "left",
    	    	            autoclose: true
    	    	        });
    					$("#" + options.id).datepicker( "setDate" , options.defaultValue );   					
    				}
    				
    				else if (options.type == "single-dropdown")
    				{
    					/*
    					var pos = options.optionList.map(function(e) {
    						console.log(e.value);
	    					return e.value; 
						  }).indexOf(options.defaultValue);
    					$scope.filterData[options.model] = options.optionList[pos];
    					
    					console.log(pos);
    					console.log(options.optionList);
    					console.log(options.defaultValue);
    					*/
    					$scope.filterData[options.model] = options.optionList[0];
    				}
    			});
    		});
    		
    	});
    }
    
    $scope.loadReport = function()
    {
    	$http.post(querytarget, {reportId: $location.search().id, output: "preview", filterData: $scope.filterData})
    	.success(function(res, status, headers, config){    
    		$scope.report = $sce.trustAsHtml(res);   
    		Metronic.unblockUI();
    		
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
    
    $scope.sendRealFile = function(type)
    {
    	var queryObject = {
    			filterData	:	$scope.filterData,
    			reportId	:	$location.search().id,
    			output		:	type,
    	};
    	var queryString = $.param( queryObject );
    	
    	var realFileDisplay = window.open(endpoint + "/getReport.json?" + queryString);
    }
    
    
});