'use strict';

app.controller('selectGroupCtrl', function($scope, $http, SharedService, $timeout) {
	
	$scope.clientSuggestion = [];
	$scope.clientHeader = "建議客戶";
	
	$scope.lock = false;

    $scope.keyword = {
        'id' :'',
        'keyword':''
    };

	var fetchDataDelay = 500;   // milliseconds
    var fetchDataTimer;
    var csuggestion = -1;
    var customerTableKeyDownExist = false;


    if(customerTableKeyDownExist == false) {
    $("#selectGroupmodel").keydown(function (e) {
        if(($("#selectGroupmodel").data('bs.modal') || {}).isShown == true) {
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
    
    
    
    $scope.openSelectionModal = function() {
    	//$('#selectclientmodel').modal('show');
    }
    
    $scope.select = function(c)
    {
    	$('#selectGroupmodel').modal('hide');
        $('#selectGroupmodel').on('hidden.bs.modal', function () {
            $('#address_cht').focus();
            csuggestion = -1;
        })

    	$scope.searchGroup("");
    	SharedService.setValue('GroupId', c.id, 'handleCustomerUpdate');
    	SharedService.setValue('GroupName', c.name, 'handleCustomerUpdate');

    	
    }
    
    $scope.searchGroup = function(keyword)
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
			    		url		: 	endpoint + '/checkGroup.json',
			        	data	:	{client_keyword: $scope.keyword},
			        	cache	:	true
			        	//timeout: canceler.promise,
			    	}        	
	        ).
	        success(function(res, status, headers, config) {
	        	$scope.clientSuggestion = res;
                  csuggestion = -1;
                    console.log(res);

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