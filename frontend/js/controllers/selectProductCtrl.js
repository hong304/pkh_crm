'use strict';

app.controller('selectProductCtrl', function($scope, $http, SharedService, $timeout) {
	
	$scope.productGroups = '';
	$scope.productsInGroup = '';
	$scope.productDisplay = 'list';
	$scope.productSearchResult = [];
	$scope.customerId = '0';
	
	var fetchDataDelay = 250;   // milliseconds
    var fetchDataTimer;
    var suggestion = -1;

    
	
    document.addEventListener('keydown', function(evt) {
		var e = window.event || evt;
		var key = e.which || e.keyCode;
		
		if(e.keyCode == 13)
		{    			
			$("#productSearchField").focus().select();
			suggestion = -1;
		}
		
		if(e.keyCode == 38) // up
		{
			e.preventDefault();
			$("#suggestion_row_" + suggestion).css('background', '');
			suggestion--;
			$("#suggestion_row_" + suggestion).css('background', '#E6FFE6');
			//console.log(suggestion);
		}
		if(e.keyCode == 40)
		{
			e.preventDefault();
			$("#suggestion_row_" + suggestion).css('background', '');
			suggestion++;
			$("#suggestion_row_" + suggestion).css('background', '#E6FFE6');
			//console.log(suggestion);
		}
		if(e.keyCode == 39)
		{
			e.preventDefault();
			$("#suggestion_row_" + suggestion).css('background', '');
			$("#suggestion_row_" + suggestion).click();
			suggestion = -1;
			//console.log(suggestion);
		}
		
		
	}, false);
	
    $scope.$on('$viewContentLoaded', function() {   
        // initialize core components
        Metronic.initAjax();
        
    });
    
    $scope.$on('doneCustomerUpdate', function(){
    	$scope.customerId = SharedService.clientId;
    	$scope.searchProductByField("");
    	
    });
    
    $scope.$on('updateProductSelection', function(){
		$scope.currentrow = SharedService.currentSelectProductRow;
		//console.log($scope.currentrow);
	});
    
    $scope.selectProductToMain = function(pid)
    
    {    	  
    	var focus_col = "department";
    	SharedService.setValue('selectedProductId', pid, 'updateProductSelected');
    }
    
    $scope.searchProductByField = function(keyword){
    	$timeout.cancel(fetchDataTimer);
    	fetchDataTimer = $timeout(function () {
	    	
	    	$http(
	    			{
			    		method	:	"POST",
			    		url		: 	endpoint + '/searchProductOrHotProduct.json', 
			        	data	:	{keyword: keyword, customerId: $scope.customerId},
			        	cache	:	true,
			        	//timeout: canceler.promise,
			    	}        	
	        ).
	        success(function(res, status, headers, config) {
	        	$scope.productSearchResult = res;
	        	//$timeout($scope.openSelectionModal, 1000);
	        	//$scope.openSelectionModal();
	        }).
	        error(function(res, status, headers, config) {
	          // called asynchronously if an error occurs
	          // or server returns response with an error status.
	        });
    	}, fetchDataDelay);
    }
    
    $scope.initProductData = function(){
    	
    	if(!$.cookie('display'))
    	{
    		$.cookie('display', 'list');
    	}
    	
    	if($.cookie('display') == 'grid')
		{
    		$scope.productDisplay = 'grid';
    		$scope.productGrid = 'active';
    		
		}
    	else if($.cookie('display') == 'list')
		{
    		$scope.productDisplay = 'list';
    		$scope.productList = 'active';
		}
    	
    	
    	$http.get(endpoint + '/getProductGroups.json')
    	.success(function(res, status, headers, config) {
        	$scope.productGroups = res.groups;        	
        	
        	//console.log(res.groups);
        })
        .error(function(res, status, headers, config) {
          // called asynchronously if an error occurs
          // or server returns response with an error status.
        });
    	
    	
    	
    	
    	$timeout(function(){
    		$("#productSearchField").focus();
    	});
    };
    
    $scope.switchDisplayMethod = function(displayMethod)
    {
    	
    	if(displayMethod == 'list')
		{
    		$scope.productDisplay = 'list';
    		$scope.productList = 'active';
    		$.cookie('display', 'list');
		}
    	else if(displayMethod == 'grid')
		{
    		$scope.productDisplay = 'grid';
    		$scope.productGrid = 'active';
    		$.cookie('display', 'grid');
		}
    }
    
    $scope.showGroup = function(id)
    {
    	$(".selection_group").css('display', 'none').removeClass('active');
    	$("#group_" +id).css('display', '');
    	$("department_" +id).addClass('active');
    	$("#selectProduct").animate({ scrollTop: 0 }, "slow");
    	
    	
    }
    
    $scope.showProduct = function(did, gid)
    {
    	
    	$(".selection_department").removeClass('active');
    	$("#group_"+did+gid).addClass('active');
    	
    	$http.post(
            	endpoint + '/getProductsFromGroup.json', {
            	departmentid : did,
            	groupid : gid,
            }).
            success(function(res, status, headers, config) {
            	$scope.productsInGroup = res;
            	
            	
            	$scope.productsInGroupUpper = res.slice(0, Math.ceil(res.length/2));
            	$scope.productsInGroupLower = res.slice(Math.ceil(res.length/2), res.length);
            	//console.log(res);
            }).
            error(function(res, status, headers, config) {
              // called asynchronously if an error occurs
              // or server returns response with an error status.
            });
    }
    /*
    $scope.colFilter = function(col) {
        var count = 0;
        // we hold onto count here, because $filter doesn't 
        // tell us the position of `val` in the array
        return function(val) {
            count++;
            if ((count-1) % 2 === col) {
                return val;
            }
        }
    }
    */
    
    $scope.colFilter = function(part)
    {
    	var middle = Math.ceil(totallength / 2);
    	if(part == "0")
		{
			var count = 0;
		}
		else
		{
			// lower part
			var count = middle;
		}
    	var totallength = $scope.productsInGroup.length;
    	var middle = Math.ceil(totallength / 2);
    	return function(val)
    	{
    		if(part == "0")
    		{
    			// upper part
    			if(count < middle) return val;
    		}
    		else
    		{
    			// lower part
    			if(count > middle) return val;
    		}
    		
    		count++;
    	}
    }

	
});