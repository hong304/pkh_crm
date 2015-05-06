'use strict';

app.controller('selectClientCtrl', function($scope, $http, SharedService, $timeout) {
	
	$scope.clientSuggestion = [];
	$scope.clientHeader = "建議客戶";
	
	$scope.lock = false;
	
	var fetchDataDelay = 250;   // milliseconds
    var fetchDataTimer;
    var suggestion = -1;


    document.addEventListener('keydown', function(evt) {
        var e = window.event || evt;
        var key = e.which || e.keyCode;

        if(($("#selectclientmodel").data('bs.modal') || {}).isShown == true) {
            if (e.keyCode == 38) // up
            {
                e.preventDefault();
                $("#suggestion_row1_" + suggestion).css('background', '');
                suggestion--;
                $("#suggestion_row1_" + suggestion).css('background', '#E6FFE6');

            } else if (e.keyCode == 40) //down
            {
                e.preventDefault();
                $("#suggestion_row1_" + suggestion).css('background', '');
                suggestion++;
                $("#suggestion_row1_" + suggestion).css('background', '#E6FFE6');

            } else if (e.keyCode == 39) {


                e.preventDefault();
                $("#suggestion_row1_" + suggestion).css('background', '');
                $("#suggestion_row1_" + suggestion).click();

                suggestion = -1;

            }
        }

    }, false);

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
    
    $scope.selectClient = function(c)
    {
    	$('#selectclientmodel').modal('hide');
        $('#selectclientmodel').on('hidden.bs.modal', function () {
            $('#productCode_1').focus();


        })
    	$scope.keyword = {
            'zone' :'',
            'id' :'',
            'keyword':''
        };
    	$scope.searchClient("");
    	SharedService.setValue('clientId', c.customerId, 'handleCustomerUpdate');
    	SharedService.setValue('clientName', c.customerName_chi, 'handleCustomerUpdate');
    	SharedService.setValue('clientAddress', c.address_chi, 'handleCustomerUpdate');
    	SharedService.setValue('clientZoneId', c.zone.zoneId, 'handleCustomerUpdate');
    	SharedService.setValue('clientZoneName', c.zone.zoneName, 'handleCustomerUpdate');
    	SharedService.setValue('clientRoute', c.routePlanningPriority, 'handleCustomerUpdate');
        SharedService.setValue('clientRemark', c.remark, 'handleCustomerUpdate');
    	SharedService.setValue('clientPaymentTermId', c.paymentTermId, 'handleCustomerUpdate');
        SharedService.setValue('clientShift', c.shift, 'handleCustomerUpdate');
    	SharedService.setValue('clientDiscount', c.discount, 'handleCustomerUpdate');
    	
    	SharedService.setValue('clientSelectionCompleted', true, 'doneCustomerUpdate');
    	
    }
    
    $scope.searchClient = function(keyword)
    {   

    	$timeout.cancel(fetchDataTimer);
    	fetchDataTimer = $timeout(function () {
	    	if(keyword != "")
	    	{
	    		$scope.clientHeader = "搜尋結果";
	    	}
	    	//canceler.resolve();
            console.log($scope.keyword);

	    	$http(
	    			{
			    		method	:	"POST",
			    		url		: 	endpoint + '/checkClient.json', 
			        	data	:	{client_keyword: $scope.keyword},
			        	cache	:	true,
			        	//timeout: canceler.promise,
			    	}        	
	        ).
	        success(function(res, status, headers, config) {
	        	$scope.clientSuggestion = res;

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