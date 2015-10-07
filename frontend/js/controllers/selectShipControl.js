'use strict';

Metronic.unblockUI();

app.controller('selectShipControl', function($rootScope, $scope, $http, $timeout, SharedService, $location, $interval, $window, $state,$stateParams) {

   $scope.$on('$viewContentLoaded', function() {
    
    Metronic.initAjax();
   });
   laodCountry();
    $scope.supplier = 
    {
        supplierCode : '',
        supplierName : '',
        phone_1 :'',
        status : '',
        countryName:'',
    }
    
    
    $scope.keyword = {
        'name': '',
        'id': '',
        'phone': '',
        'country' : '',
        'contact' : '',
        'sorting' : '',
        'current_sorting' : 'asc',
        'status': '1',
        'countryName' : '',
         findOverseas:'findOverseas',
	};
        
         $scope.filter = {
        'name': '',
        'id': '',
        'phone': '',
        'country' : '',
        'contact' : '',
        'sorting' : '',
        'current_sorting' : 'asc',
        'status': '1',
        'countryName' : '',
         findOverseas:'findOverseas',
	};
    
    
    
    $scope.searchSupplier = function(supplier){
        var target = endpoint + '/querySupplier.json';
        $scope.filter.id = $scope.keyword.id;
        $scope.filter.findOverseas = "overseas";
        $scope.filter.countryName = $scope.keyword.countryName;
        $scope.filter.phone = $scope.keyword.phone;
        $scope.filter.name = $scope.keyword.name;
        
        $http.post(target, {mode : 'collection',filterData : $scope.filter})
            .success(function(res, status, headers, config){
                console.log(res);
                $scope.pullAll =  res.aaData;
                console.log($scope.pullAll);
            });
    }
    
    
    $scope.selectSupplier = function(supplier){
          var target = endpoint + '/jsonSearchPo.json';
         $scope.supplier.supplierCode = supplier.supplierCode;
         $scope.supplier.supplierName = supplier.supplierName;
         SharedService.setValue('supplierCode', supplier.supplierCode, 'handleShipPassUpdate');
         SharedService.setValue('supplierName', supplier.supplierName, 'handleShipPassUpdate');
          $http.post(target, {supplierCode : $scope.supplier.supplierCode})
            .success(function(res, status, headers, config){
               $scope.purchaseorder = res;
            });
         
    }
    
    $scope.selectPo = function(po){
        SharedService.setValue('poCode', po.poCode, 'handleShipPassUpdate');
        $('#selectShipModel').hide();
    }
    
    function laodCountry()
    {
        $http(
	    	{
			method	:	"POST",
			url		: endpoint + '/queryCountry.json', 
			data	:	{mode: 'collection'},
			        	cache	:	true,
			        	//timeout: canceler.promise,
			    	}        	
	        ).
	        success(function(res, status, headers, config) {
	        	
                  $scope.countryData = res.aaData;
	        }).
	        error(function(res, status, headers, config) {
	          // called asynchronously if an error occurs
	          // or server returns response with an error status.
	        });
                
    }
    
    
     
    
    
});