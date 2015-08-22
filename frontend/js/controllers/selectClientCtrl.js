'use strict';

app.controller('selectClientCtrl', function($scope, $http, SharedService, $timeout) {
	
	$scope.clientSuggestion = [];
	$scope.clientHeader = "建議客戶";
	
	$scope.lock = false;
	
	var fetchDataDelay = 250;   // milliseconds
    var fetchDataTimer;
    var csuggestion = -1;
    var customerTableKeyDownExist = false;


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
    
    $scope.selectClient = function(c)
    {
    	$('#selectclientmodel').modal('hide');
        $('#selectclientmodel').on('hidden.bs.modal', function () {
            //$('#productCode_1').focus();
            csuggestion = -1;
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
    	SharedService.setValue('clientZoneId', c.deliveryZone, 'handleCustomerUpdate');
    	SharedService.setValue('clientZoneName', c.zoneText, 'handleCustomerUpdate');
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

	    	$http(
	    			{
			    		method	:	"POST",
			    		url		: 	endpoint + '/checkClient.json', 
			        	data	:	{client_keyword: $scope.keyword},
			        	cache	:	true
			        	//timeout: canceler.promise,
			    	}        	
	        ).
	        success(function(res, status, headers, config) {
	        	$scope.clientSuggestion = res;
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