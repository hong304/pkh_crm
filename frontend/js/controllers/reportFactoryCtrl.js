'use strict';
Metronic.unblockUI();

app.controller('reportFactoryCtrl', function($scope, $http, SharedService, $timeout, $location, $sce) {

    var fetchDataDelay = 250;   // milliseconds
    var fetchDataTimer;

	var querytarget = endpoint + "/getReport.json";

    $scope.action = '';
	$scope.report = "";
	$scope.filterData = {
			'shift' : '1'
	};
	
    $scope.$on('$viewContentLoaded', function() {

        if ($location.search().id =='printlog')
            $location.url('/reportPrintlog');


        if($location.search().product == null)
            $location.search().product = '100';
        if($location.search().client == null)
            $location.search().client = '100002';

        Metronic.initAjax();        
        $scope.loadSetting();
        $scope.loadReport();
    });  
    
	$scope.$watch(function() {
	  return $scope.filterData;
	}, function() {
	  $scope.loadReport();
	}, true);
    
    $scope.loadSetting = function()
    {
    	

        $http.post(querytarget, {reportId: $location.search().id, filterData: $scope.filterData, output: "setting"})
    	.success(function(res, status, headers, config){
              //  console.log(res);

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

                    if(options.type == "date-picker1")
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

                        $("#" + options.id1).datepicker({
                            rtl: Metronic.isRTL(),
                            orientation: "left",
                            autoclose: true
                        });
                        $("#" + options.id1).datepicker( "setDate", year + '-' + month + '-' + day );

                    }

    				else if (options.type == "single-dropdown")
    				{
    					/*
    					var pos = options.optionList.map(function(e) {
    						console.log(e.value);
	    					return e.value;
						  }).indexOf(options.defaultValue);
    					$scope.filterData[options.model] = options.optionList[pos];

    					console.log(pos);
    					console.log(options.optionList);
    					console.log(options.defaultValue);
    					*/
    					$scope.filterData[options.model] = options.optionList[0];
    				}
    			});
    		});

    	});
    }
    
    $scope.loadReport = function()
    {
    	$http.post(querytarget, {reportId: $location.search().id, output: "preview", filterData: $scope.filterData, query:$location.search()})
    	.success(function(res, status, headers, config){



    		$scope.report = $sce.trustAsHtml(res);

    		Metronic.unblockUI();


                $http.post(querytarget, {reportId: $location.search().id, filterData: $scope.filterData, output: "setting"})
                    .success(function(res, status, headers, config){
                        $scope.setting.title = res.title;
                    });


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
    
    $scope.sendRealFile = function(type)
    {
    	var queryObject = {
    			filterData	:	$scope.filterData,
    			reportId	:	$location.search().id,
    			output		:	type,
    	};

    	var queryString = $.param( queryObject );
        console.log(queryObject);

        var realFileDisplay = window.open(endpoint + "/getReport.json?" + queryString);
    }

    $scope.searchProductByField = function(keyword){
        $timeout.cancel(fetchDataTimer);
        fetchDataTimer = $timeout(function () {

            $http(
                {
                    method	:	"POST",
                    url		: 	endpoint + '/searchProductDataProduct.json',
                    data	:	{keyword: keyword, customerId: '100002'},
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

    $scope.searchClient = function(keyword)
    {
        $timeout.cancel(fetchDataTimer);
        fetchDataTimer = $timeout(function () {
            if(keyword != "")
            {
                $scope.clientHeader = "搜尋結果";
            }
            //canceler.resolve();
            $http(
                {
                    method	:	"POST",
                    url		: 	endpoint + '/checkClient.json',
                    data	:	{client_keyword: keyword},
                    cache	:	true,
                    //timeout: canceler.promise,
                }
            ).
                success(function(res, status, headers, config) {
                    $scope.clientSuggestion = res;

                }).
                error(function(res, status, headers, config) {
                    // called asynchronously if an error occurs
                    // or server returns response with an error status.
                });
        }, fetchDataDelay);
    }


    $scope.selectProduct = function(id,action){
        if(id !==null)
            $location.search().product = id;

        $location.search().action = action;
        $scope.action= action;

        $scope.loadSetting();
        $scope.loadReport();
    }

    $scope.selectClient = function(id,action){
        if(id !==null)
            $location.search().client = id;

        $location.search().action = action;
        $scope.action= action;

        $scope.loadSetting();
        $scope.loadReport();
    }
    
});