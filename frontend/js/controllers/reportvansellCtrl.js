'use strict';

app.controller('reportvansellCtrl', function($scope, $http, SharedService, $timeout, $location, $sce) {

	var querytarget = endpoint + "/getVansellreport.json";


	$scope.report = "";
	$scope.filterData = {
			'shift' : '1',
            deliveryDate : ''

	};
    $scope.qty = [];

    $scope.invoiceStructure = {
        'id' : '',
        'value' : '',
        'org_qty' : '',
        'unit'  : '',
    }
	
    $scope.$on('$viewContentLoaded', function() {
        Metronic.initAjax();
        $scope.loadSetting();
        if($scope.filterData.deliveryDate != '')
            $scope.loadReport();
    });



	$scope.$watch(function() {
	  //return $scope.filterData;
	}, function() {
	 // $scope.loadReport();
	}, true);

    $scope.updateZone = function(){
        $scope.loadReport();
    }

    $scope.updateShift = function(){
        $scope.loadReport();
    }

    $scope.updateDate = function(){
        $scope.loadReport();
    }

    $scope.loadSetting = function()
    {
    	

        $http.post(querytarget, {reportId: 'vanselllist', filterData: $scope.filterData, output: "setting"})
    	.success(function(res, status, headers, config){


    		$scope.setting = res;
    		Metronic.unblockUI();
    		$timeout(function(){


    			res.filterOptions.forEach(function(options){
    				if(options.type == "date-picker")
    				{

                       var today = new Date();
                        var plus = today.getDay() == 6 ? 2 : 1;

                        var currentDate = new Date(new Date().getTime() + 24 * 60 * 60 * 1000 * plus);
                        if(today.getHours() < 12)
                        {
                            var nextDay = today;
                        }
                        else
                        {
                            var nextDay = currentDate;
                        }
                        var day = nextDay.getDate();
                        var month = nextDay.getMonth() + 1;
                        var year = nextDay.getFullYear();

    					$("#" + options.id).datepicker({
    	    	            rtl: Metronic.isRTL(),
    	    	            orientation: "left",
    	    	            autoclose: true
    	    	        });
    					$("#" + options.id).datepicker( "setDate", year + '-' + month + '-' + day );
    				}

    				else if (options.type == "single-dropdown")
    				{
    					$scope.filterData[options.model] = options.optionList[0];

    				}



    			});


    		});

    	});


    }
    
    $scope.loadReport = function()
    {
        console.log($scope.filterData.deliveryDate);

    	$http.post(querytarget, {reportId: 'vanselllist', output: "preview", filterData: $scope.filterData})
            .success(function(res){
    		$scope.report = res;


           var i = 0;
              res.forEach(function(item) {
                    $scope.qty[i] = $.extend(true, {}, $scope.invoiceStructure);
                    $scope.qty[i]['productId'] = item.productId;
                    $scope.qty[i]['value'] = item.qty;
                   i++;
               });
    		Metronic.unblockUI();
    	});
    }

    $scope.sendFile = function(file)
    {
    	
    	if(file.warning != false)
		{
    		bootbox.confirm(file.warning, function(result) {
    			if(result == true)
    			{
    				$scope.sendRealFile(file.type);
    			}
        	}); 
		}
    	else
    	{
    		$scope.sendRealFile(file.type);
    	}
    	
    }
    
    $scope.sendRealFile = function()
    {

        $http.post(querytarget, {reportId: 'vanselllist', output: "create", filterData: $scope.filterData,data:$scope.qty})
            .success(function(res, status, headers, config){

                var queryObject = {
                    filterData	:	$scope.filterData,
                    reportId	:	'vanselllist',
                    output		:	'pdf'
                };
                var queryString = $.param( queryObject );

                window.open(endpoint + "/getVansellreport.json?" + queryString);

            });



    }

    
});