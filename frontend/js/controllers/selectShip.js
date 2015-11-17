'use strict';

Metronic.unblockUI();



app.controller('selectShip', function($rootScope, $scope, $http, $timeout, SharedService, $location, $interval, $window, $state,$stateParams) {
 $scope.$on('$viewContentLoaded', function() {
        
       Metronic.initAjax();
    });
    initCountry();
  
        
        function initCountry()
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
                  $scope.currencyData = res.aaData;
                  SharedService.setValue('allCurrency', $scope.currencyData, 'handleShippingUpdate');
	        	//$timeout($scope.openSelectionModal, 1000);
	        	//$scope.openSelectionModal();
	        }).
	        error(function(res, status, headers, config) {
	          // called asynchronously if an error occurs
	          // or server returns response with an error status.
	        });
        
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
                  SharedService.setValue('allCountry', $scope.countryData, 'handleShippingUpdate');
                  
	        	//$timeout($scope.openSelectionModal, 1000);
	        	//$scope.openSelectionModal();
	        }).
	        error(function(res, status, headers, config) {
	          // called asynchronously if an error occurs
	          // or server returns response with an error status.
	        });
        
         }
    
    $scope.filterData = 
    {
        supplierCode : '',
        supplierName : '',
        phone :'',
        country:'',
    }

    $scope.filterSupplier = function()
    {  
        $scope.filterData.supplierCode = $scope.keyword.id;
        $scope.filterData.supplierName = $scope.keyword.name;
        $scope.filterData.phone = $scope.keyword.phone;
        $scope.filterData.country = $scope.keyword.country;
        console.log($scope.filterData);
          var ta = endpoint + '/searchSupplier.json';
           $http.post(ta, {filterData: $scope.filterData,location:$scope.orders.location})
           .success(function (res, status, headers, config) {
                for(var i = 0;i<res.length;i++)
                {
                    if(res[i].status == 1)
                    {
                        res[i].statusWord = "正常";
                    }else
                    {
                        res[i].statusWord = "暫停";
                    }
                }
                $scope.putSupplier = res;
                $scope.purchaseorderDetails = "";
                $scope.shippingContainer = "";
           });  
    }
    $scope.disableValue = 0;
    $scope.openLocal = function()
    {
        $("#frontReceive").hide();
        $("#downReceive").show();
        $scope.orders.location = '1';
        SharedService.setValue('location','1', 'handleShippingUpdate');
        $("#exchangeRate,#shipCode,#containerCode,#feight_cost,#local_cost,#misc_cost,#total_cost").hide();
        $scope.disableValue = 1;
          //  $scope.product[i].unit = $scope.product[i].availableunit[0];
    }
    
    $scope.openOverSea = function()
    {
        $("#frontReceive").hide();
        $("#downReceive").show();
        $scope.orders.location = '2';
        SharedService.setValue('location', '2', 'handleShippingUpdate');
        
    }
    
    $scope.selectTheSupplier = function(supplierEle)
    {
        var ta = endpoint + '/searchPoBySupplier.json';
        $scope.storePo = supplierEle.supplierCode;
        
        SharedService.setValue('supplierCode', $scope.storePo, 'handleShippingUpdate');
        SharedService.setValue('supplierName', supplierEle.supplierName, 'handleShippingUpdate');
        SharedService.setValue('countryId', supplierEle.countryId, 'handleShippingUpdate');
        SharedService.setValue('countryName', supplierEle.countryName, 'handleShippingUpdate');
        SharedService.setValue('currencyName', supplierEle.currencyName, 'handleShippingUpdate');
        SharedService.setValue('currencyId', supplierEle.currencyId, 'handleShippingUpdate');
            $http.post(ta, {poCode: $scope.storePo ,location:$scope.orders.location })
            .success(function (res, status, headers, config) {
                 for(var i = 0;i<res.length;i++)
                {
                    if(res[i].status == 1)
                    {
                        res[i].statusWord = "正常";
                    }else
                    {
                        res[i].statusWord = "暫停";
                    }
                }
                $scope.purchaseorderDetails = res;
              
            });
        
    }
    
    $scope.selectPo = function(po)
    {
        SharedService.setValue('poCode', po.poCode, 'handleShippingUpdate');
         $scope.storePoSecond = po.poCode;
        if($scope.orders.location == '2')
        {
            var ta = endpoint + '/searchShipping.json';
           
           // $scope.$on('handleShippingUpdate', function(){
            //    $scope.orders.location = SharedService.locationValue;
          //  });
            if($scope.storePoSecond != "")
            {
                $http.post(ta, {poCode:  $scope.storePoSecond})
                .success(function (res, status, headers, config) {
                    $scope.shippingContainer = res;
                });
            }
        
            
        }else if($scope.orders.location == '1')
        {
               $("#selectShipModel").hide();
                 var taPost = endpoint + '/getPurchaseAll.json';
        
        $http.post(taPost, {poCode: $scope.storePoSecond })
        .success(function (res, status, headers, config) {

            if(res.length > 0)
            {
                 SharedService.setValue('items', res[0].poitem, 'handleShippingUpdate');
                 
            }

        });
               
        }
        
      
        
    }
    
    $scope.selectContainer = function(container)
    {
        SharedService.setValue('shippingId', container.shippingId, 'handleShippingUpdate');
        SharedService.setValue('containerId', container.containerId, 'handleShippingUpdate');
        SharedService.setValue('shippingdbid', container.dbid, 'handleShippingUpdate');
          $("#selectShipModel").hide();


    }


   
    
});