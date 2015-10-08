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
    $("#selectclientmodel").keydown(function (e) {
        if(($("#selectclientmodel").data('bs.modal') || {}).isShown == true) {
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
                if(($("#selectclientmodel").data('bs.modal') || {}).isShown == true)
                    $("#suggestion_row1_" + csuggestion).click();
                console.log(csuggestion);
                csuggestion = -1;
                console.log(csuggestion);
            }
        }

        customerTableKeyDownExist = true;
    });
}

/* var isCtrl = false;$(document).keyup(function (e) {
     if(e.which == 17) isCtrl=false;
 }).keydown(function (e) {
     if(e.which == 17) isCtrl=true;
     if(e.which == 83 && isCtrl == true) {
         alert('Keyboard shortcuts + JQuery are even more cool!');
         return false;
     }
 });*/




    $scope.$on('$viewContentLoaded', function() {   
        // initialize core components

        Metronic.initAjax();      
         
    }); 
    
    $scope.$on('ZoneChanged', function(){
    	$scope.searchClient($scope.keyword);
       // $("#keyword").focus().select();
    });
    
    
    
    $scope.openSelectionModal = function() {
    	//$('#selectclientmodel').modal('show');
    }
    
    $scope.selectSupplier = function(c)
    {
    	$('#selectclientmodel').modal('hide');
        $('#selectclientmodel').on('hidden.bs.modal', function () { // Jump to bottom of page
            $('#productCode_1').focus();
            csuggestion = -1;
        })
    	$scope.searchClient("");
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
    
   $scope.searchClient = function(keyword)
    {   
        $scope.keyword.access = "search";
        console.log($scope.keyword);
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
                 console.log($scope.keyword);
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