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
        poCode : ''
    }

    $scope.filterSupplier = function()
    {
        $scope.filterData.supplierCode = $scope.keyword.id;
        $scope.filterData.supplierName = $scope.keyword.name;
        //$scope.filterData.phone = $scope.keyword.phone;
        $scope.filterData.country = $scope.keyword.country;

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

    var fetchDataTimer;

    $scope.selectPoCode = function(){

        $timeout.cancel(fetchDataTimer);
        fetchDataTimer = $timeout(function () {


        $scope.filterData.poCode = $scope.keyword.poCode;
        var ta = endpoint + '/searchPoBySupplier.json';
        $http.post(ta, {poCode: $scope.filterData.poCode ,location:$scope.orders.location })
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
        }, 500);
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
            $http.post(ta, {supplierCode: $scope.storePo ,location:$scope.orders.location })
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
                        SharedService.setValue('supplierSelectionCompleted', true, 'doneShippingUpdate');
                    }

                });
               
        }


        
    }
    
    $scope.selectContainer = function(container)
    {
        SharedService.setValue('shippingId', container.shippingId, 'handleShippingUpdate');
        SharedService.setValue('containerId', container.containerId, 'handleShippingUpdate');
        SharedService.setValue('shippingdbid', container.dbid, 'handleShippingUpdate');
          var searchContainer = endpoint + '/addProductContainer.json';
            if(container.containerId != "")
            {
                var i = 1;
                 $http.post(searchContainer, {containerId:  container.containerId })
                .success(function (res, status, headers, config) {      
                    for(var items in res)
                    {
                        $scope.product[i].productId = res[items].productId;
                     //   $scope.product[i].productName = item.product_detail.productName_chi;
                        $scope.product[i].qty = res[items].qty;
                        $scope.product[i].good_qty = res[items].qty;
                        $scope.product[i].productName = res[items].productName_chi;
                        $scope.unit_cost = 0;
                        if(res[items].unit == 'carton')
                        {
                            $scope.unit_cost = res[items].productCost_unit;
                        }else if(res[items].unit == 'inner')
                        {
                            $scope.unit_cost = res[items].supplierStdPrice_inner;
                        }else if(res[items].unit == 'unit')
                        {
                            $scope.unit_cost = res[items].supplierStdPrice_unit;
                        }
                        $scope.product[i].unit_cost = $scope.unit_cost;
                        addUnit(res[items],i);
                     
                        i++; 
                    }
                    console.log(res);
                    if(res.length > 0)
                    {
                         SharedService.setValue('items', res, 'handleShippingUpdate');
                    }
                });
            }


        $("#selectShipModel").hide();
    }
    
    
    function addUnit(item,i)
    {

            var availableunit = [];
            var storeUnit = [];
             if(item.supplierPackingInterval_carton > 0)
                  {
                      availableunit = availableunit.concat([{value: 'carton', label: item.productPackingName_carton}]);
                      storeUnit[0] = 'carton';
                  }else if(item.supplierPackingInterval_inner > 0)
                  {
                       availableunit = availableunit.concat([{value: 'inner', label: item.productPackingName_inner}]);
                       storeUnit[1] = 'inner';
                  }else if(item.supplierPackingInterval_unit > 0)
                  {
                       availableunit = availableunit.concat([{value: 'unit', label: item.productPackingName_unit}]);
                       storeUnit[2] = 'unit';
                  }
   
                  $scope.product[i].availableunit = availableunit;
                  var indexNum = storeUnit.indexOf(item.unit);

                  $scope.product[i]['unit'] = availableunit[indexNum];
    }

});