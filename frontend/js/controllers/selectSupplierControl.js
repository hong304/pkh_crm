'use strict';

app.controller('selectSupplierControl', function($scope, $http, SharedService, $timeout) {
	
	$scope.clientSuggestion = [];
	$scope.clientHeader = "建議客戶";
	
	$scope.lock = false;
	
	var fetchDataDelay = 250;   // milliseconds
    var fetchDataTimer;
    var csuggestion = -1;
    var customerTableKeyDownExist = false;

        laodCountry();
      loadCurrency();
    if(customerTableKeyDownExist == false) {
    $("#selectSuppliermodel").keydown(function (e) {
        if(($("#selectSuppliermodel").data('bs.modal') || {}).isShown == true) {
            if (e.keyCode == 38) // up
            {
                e.preventDefault();
                $("#suggestion_row1_" + csuggestion).css('background', '');
                csuggestion--;
                $("#suggestion_row1_" + csuggestion).css('background', '#E6FFE6');
                console.log(csuggestion);
            } else if (e.keyCode == 40) //down
            {
                e.preventDefault();
                $("#suggestion_row1_" + csuggestion).css('background', '');
                csuggestion++;
                $("#suggestion_row1_" + csuggestion).css('background', '#E6FFE6');
                console.log(csuggestion);
            } else if (e.keyCode == 39) {
                e.preventDefault();
                $("#suggestion_row1_" + csuggestion).css('background', '');
                if(($("#selectSuppliermodel").data('bs.modal') || {}).isShown == true)
                    $("#suggestion_row1_" + csuggestion).click();
                console.log(csuggestion);
                csuggestion = -1;
                console.log(csuggestion);
            }
        }

        customerTableKeyDownExist = true;
    });
}



    $scope.$on('$viewContentLoaded', function() {   
        // initialize core components

        Metronic.initAjax();      
         
    }); 
    
    $scope.$on('ZoneChanged', function(){
    	$scope.searchSupplier($scope.keyword);
       // $("#keyword").focus().select();
    });
    
    
    
    $scope.openSelectionModal = function() {
    	//$('#selectclientmodel').modal('show');
    }
    
    $scope.selectSupplier = function(c)
    {
    	$('#selectSuppliermodel').modal('hide');
    	$scope.searchSupplier("");
    	SharedService.setValue('supplierCode', c.supplierCode, 'handleSupplierUpdate');
    	SharedService.setValue('supplierName', c.supplierName, 'handleSupplierUpdate');
    	SharedService.setValue('countryName', c.countryName, 'handleSupplierUpdate');
    	SharedService.setValue('address', c.address, 'handleSupplierUpdate');
        SharedService.setValue('currencyName', c.currencyName, 'handleSupplierUpdate');
        SharedService.setValue('currencyId', c.currencyId, 'handleSupplierUpdate');
        SharedService.setValue('contactPerson_1', c.contactPerson_1, 'handleSupplierUpdate');
        SharedService.setValue('status', c.status, 'handleSupplierUpdate');
        SharedService.setValue('payment', c.payment, 'handleSupplierUpdate');
        SharedService.setValue('location', c.location, 'handleSupplierUpdate');
        SharedService.setValue('country', c.countryId, 'handleSupplierUpdate');

        SharedService.setValue('SupplierSelectionCompleted', true, 'doneSupplierUpdate');
         
    	
    	//SharedService.setValue('clientSelectionCompleted', true, 'doneCustomerUpdate');
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
	        	
                  csuggestion = -1;
                  $scope.countryData = res.aaData;
                  SharedService.setValue('allCountry', $scope.countryData, 'handleSupplierUpdate');
                  
	        	//$timeout($scope.openSelectionModal, 1000);
	        	//$scope.openSelectionModal();
	        }).
	        error(function(res, status, headers, config) {
	          // called asynchronously if an error occurs
	          // or server returns response with an error status.
	        });
                
    }
    function loadCurrency()
    {
         $http(
	    	{
			method	:	"POST",
			url		: endpoint + '/queryCurrency.json', 
			data	:	{mode: 'collection'},
			        	cache	:	true,
			        	//timeout: canceler.promise,
			    	}        	
	        ).
	        success(function(res, status, headers, config) {
	        	
                  csuggestion = -1;
                  $scope.currencyData = res.aaData;
                  SharedService.setValue('allCurrency', $scope.currencyData, 'handleSupplierUpdate');
	        	//$timeout($scope.openSelectionModal, 1000);
	        	//$scope.openSelectionModal();
	        }).
	        error(function(res, status, headers, config) {
	          // called asynchronously if an error occurs
	          // or server returns response with an error status.
	        });
                
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
        'access':'',
	};
    
   $scope.searchSupplier = function(keyword)
    {   
        $scope.keyword.access = "search";
    	$timeout.cancel(fetchDataTimer);
    	fetchDataTimer = $timeout(function () {
	    	if(keyword != "")
	    	{
	    		$scope.clientHeader = "搜尋結果";
	    	}
              
                if($scope.keyword.country != null)
                {
                      if($scope.keyword.country.countryName != "" && $scope.keyword.country.countryName != null)
                      {
                          $scope.keyword.countryName = $scope.keyword.country.countryName;
                      }
                }else
                {
                    $scope.keyword.countryName = "";
                }
	    	$http(
	    			{
			    		method	:	"POST",
			    		url		: 	endpoint + '/querySupplier.json', 
			        	data	:	{mode: 'collection','filterData' :$scope.keyword},
			        	cache	:	true,
			        	//timeout: canceler.promise,
			    	}        	
	        ).
	        success(function(res, status, headers, config) {
	        	$scope.clientSuggestion = res.aaData;
                console.log($scope.clientSuggestion);
                  csuggestion = -1;
             

	        	//$timeout($scope.openSelectionModal, 1000);
	        	//$scope.openSelectionModal();
	        }).
	        error(function(res, status, headers, config) {
	          // called asynchronously if an error occurs
	          // or server returns response with an error status.
	        });
    	}, fetchDataDelay);
    }
    
	
});