'use strict';


app.controller('invoiceFlowCtrl', function($scope, $rootScope, $http, SharedService, $location, $timeout, $interval) {

	$scope.queryAssociationTarget = endpoint + "/retrieveInvoiceAssociation.json";
	$scope.updateStatusTarget = endpoint + "/updateStatus.json";
	
	$scope.nextStep = [];
	
    $scope.$on('$viewContentLoaded', function() {
        Metronic.initAjax();        
        $scope.systeminfo = $rootScope.systeminfo;        
    });
    
    $scope.$watch(function() {
    	return $rootScope.systeminfo;
  	}, function() {
  		$scope.systeminfo = $rootScope.systeminfo;
        $scope.init();
  	}, true);
    
    
    $scope.init = function()
    {
    	
    	$http.post($scope.queryAssociationTarget, {
    		reportId	:	$location.search().rid
    	})
    	.success(function(res, status, headers, config){     
    		$scope.retrieval = res;
                $scope.retrieval.invoiceStatus = parseInt($scope.retrieval.invoiceStatus);
                $scope.retrieval.userRole = parseInt($scope.retrieval.userRole);


    		res.invoices.forEach(function(invoice){
    			$scope.nextStep[invoice.invoiceId] = invoice.nextStatus.default;
    		});
    		
    	});
    }
    
    $scope.updateStatus = function(steps)
    {
    	var invoiceSteps = $.extend(true, {}, $scope.nextStep);
    	
    	$http.post($scope.updateStatusTarget, {steps: invoiceSteps}).
    	  success(function(data, status, headers, config) {
                alert('狀態已更改')
    	        // $scope.init();
                window.location.replace("/#/salesPanel");

    	  }).
    	  error(function(data, status, headers, config) {
    	    // called asynchronously if an error occurs
    	    // or server returns response with an error status.
    	  });
    }
    
    
    
});