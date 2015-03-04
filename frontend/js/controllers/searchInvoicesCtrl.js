'use strict';

app.controller('searchInvoicesCtrl', function($scope, $rootScope, $http, SharedService, $location, $timeout, $interval) {
	
	$scope.firstload = true;
	$scope.autoreload = false; 
	
    $scope.$on('$viewContentLoaded', function() {   
        // initialize core components
        Metronic.initAjax();
        
    });
    
    $scope.$watch(function() {
  	  return $rootScope.systeminfo;
  	}, function() {
  	  $scope.systeminfo = $rootScope.systeminfo;
  	}, true);
    
    $scope.initializeTable = function()
    {
    	$scope.getData();
    	if($scope.autoreload)
		{
    		var promise =  $interval($scope.getDate(), 15000);
		}
    	
    	$timeout(function(){
    		$scope.systemInfo = SharedService.SystemInfo;
    	}, 1000);
    	
    }
    
    $scope.getData = function() {

    	var endpoint = $scope.endpoint + "/getInvoices.json";
    	 
    	var $url = $location.search();
    	var $parm = {};
    	
    	
    	$http.post(endpoint, $url)
    	.success(function(data){
    		$scope.dataTable = data;
    		
    		if($scope.firstload)
			{
    			$timeout(function(){
    				SharedService.initTable();
    			}, 500);
    			$scope.firstload = false;
			}
    		else
    		{
    			$('#datatable').dataTable().fnDestroy(); 
    		}
    		 
    	});
    }
    
    $scope.$on('$locationChangeSuccess', function(){
    	$scope.getData();     	
	});
    
    
    
    // --------------------- for approval modal
    $scope.toggle = function(index)
    {
    	jQuery("#cost_" + index).toggle();
    	jQuery("#controlcost_" + index).css('display', 'none');
    }
    
    $scope.manipulate = function(action, invoiceId)
    {
    	var approvalJson = $scope.endpoint + "/manipulateInvoiceStatus.json";
    	
    	$http.post(approvalJson, {
    		action: "approval",
    		status:	action,
    		target:	invoiceId
    	}).success(function(data) {
    		$("#invoiceNumber_" + invoiceId).remove();
        });
    	
    	$("#productDetails").modal('toggle');
    }
    
    $scope.goEdit = function(invoiceId)
    {
    	$location.url("/editOrder?invoiceId=" + invoiceId);
    }
    
    $scope.voidInvoice = function(invoiceId)
    {
    	$http.post($scope.endpoint + "/voidInvoice.json", {
    		invoiceId	:	invoiceId,
    	}).success(function(data) {
    		
    	});
    	
    	$("#invoiceNumber_" + invoiceId).remove();
    	$("#productDetails").modal('hide');

    }
    
    
    $scope.displayInvoiceItem = function(invoice)
    {
    	invoice.invoice_item.forEach(function(e){
    		var stdPrice = e.productInfo.productStdPrice[e.productQtyUnit];
    		
    		if((e.productPrice * (100-e.productDiscount)/100) < stdPrice)
    		{
    			e.requireApproval = true;
    			e.backgroundcode = "background:#FFCCCF";
    		}
    		else
    		{
    			e.requireApproval = false; 
    			e.backgroundcode = "";
    		}
    		//console.log(e);
    	});
    	
    	$("#productDetails").modal({backdrop: 'static'});
    	
    	
    	console.log(invoice);
    	$scope.previewItem = invoice;
    	$scope.systemInfo = SharedService.SystemInfo;
    }
    
    
});