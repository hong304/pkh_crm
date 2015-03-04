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
  	}, true);
    
    
    $scope.init = function()
    {
    	
    	$http.post($scope.queryAssociationTarget, {
    		reportId	:	$location.search().rid
    	})
    	.success(function(res, status, headers, config){     
    		$scope.retrieval = res;
    		
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
    	    // this callback will be called asynchronously
    	    // when the response is available
    	  }).
    	  error(function(data, status, headers, config) {
    	    // called asynchronously if an error occurs
    	    // or server returns response with an error status.
    	  });
    }
    
    
    
});