'use strict';

app.controller('selectPoControl', function($scope, $http, SharedService, $timeout) {
	
	$scope.clientSuggestion = [];
	$scope.clientHeader = "建議客戶";
	$scope.purchaseorder = [];
	$scope.lock = false;
	
	var fetchDataDelay = 250;   // milliseconds
    var fetchDataTimer;
    var csuggestion = -1;
    var customerTableKeyDownExist = false;

        laodCountry();
    if(customerTableKeyDownExist == false) {
    $("#selectPomodel").keydown(function (e) {
        if(($("#selectPomodel").data('bs.modal') || {}).isShown == true) {
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

    $scope.$on('$viewContentLoaded', function() {   
        // initialize core components

        Metronic.initAjax();      
         
    }); 
    
    $scope.$on('ZoneChanged', function(){
    	$scope.searchClient($scope.keyword);
       // $("#keyword").focus().select();
    });
    
    
    
      $scope.pos = 
        {
           poCode:'',
           poDate:'',
           receiveDate:'',
        };
    
    $scope.selectSupplier = function(c)
    {
    	$('#selectPomodel').modal('show');
    	$scope.searchClient("");
    	SharedService.setValue('supplierCode', c.supplierCode, 'handleSupplierUpdate');
    	SharedService.setValue('supplierName', c.supplierName, 'handleSupplierUpdate');
        
        $scope.loadPurchaseOrder(c.supplierCode,c.supplierName);
       
        $timeout(function(){
            $('#selectPomodel').modal('show');
            $('#selectPomodel').on('shown.bs.modal', function () {
            $('#keyword').focus();
            })
        }, 1000);

    }
    
    $scope.selectPo = function(e)
    {
        SharedService.setValue('supplierPoCode', e.poCode, 'handlePoUpdate');
        $('#selectPoModel').modal('hide');
    }
    
    $scope.loadPurchaseOrder = function(ele,sname)
    {
        if(ele !==undefined)
        {
             $http.post(endpoint + '/jsonSelectPo.json', {input: ele , supplier:sname})
             .success(function(data, status, headers, config){
              $scope.purchaseorder = data;
              });
        }
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
	};
    
   $scope.searchClient = function(keyword)
    {   
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
                          $scope.keyword.country = $scope.keyword.country.countryName;
                      }
                }else
                {
                    $scope.keyword.country = "";
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