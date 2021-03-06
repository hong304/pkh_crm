'use strict';
Metronic.unblockUI();

app.controller('reportFactoryCtrl', function($scope, $http, SharedService, $timeout, $location, $sce) {

    $(document).ready(function(){

        $('#queryInfo').keydown(function (e) {
            if (e.keyCode == 13) {
                $scope.loadReport();
            }

        });

    });

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
  //  var dayD = nextDay.getDate()+1;
    var month = nextDay.getMonth() + 1;
    var year = nextDay.getFullYear();
    var yday = nextDay.getDate()-1;
    var fetchDataDelay = 500;   // milliseconds
    var fetchDataTimer;

	var querytarget = endpoint + "/getReport.json";

    $scope.action = '';
	$scope.report = "";
	$scope.filterData = {
            'name' : '',
            'customerId' : '',
            'phone' : '',
             deliveryDate : year+'-'+month+'-'+yday,
             deliveryDate1 : year+'-'+month+'-'+day,
             deliveryDate2 : year+'-'+month+'-'+day,
            'productId' : '',
             'productName' : '',
        'group' : ''
	};
    $scope.setting = {
        'setting' : false
    };

    $scope.zone = {
        zoneId : ''
    }
	
    $scope.$on('$viewContentLoaded', function() {

        if($location.search().product == null)
            $location.search().product = '100';
        if($location.search().client == null)
            $location.search().client = '100002';

        Metronic.initAjax();        
        $scope.loadSetting();
        if($scope.setting.setting == true)
            $scope.loadReport();
    });  


       /* $scope.$watch(function() {
          return $scope.filterData;
        }, function() {

            $timeout.cancel(fetchDataTimer);
            fetchDataTimer = $timeout(function () {
                $scope.loadReport();
            }, fetchDataDelay);
        }, true);*/


    $scope.loadSetting = function()
    {
    	

        $http.post(querytarget, {reportId: $location.search().id, filterData: $scope.filterData, output: "setting"})
    	.success(function(res, status, headers, config){

    		$scope.setting = res;
    		Metronic.unblockUI();
    		$timeout(function(){


    			res.filterOptions.forEach(function(options){
    				if(options.type == "date-picker")
    				{
    					$("#" + options.id).datepicker({
    	    	            rtl: Metronic.isRTL(),
    	    	            orientation: "left",
    	    	            autoclose: true
    	    	        });
    					$("#" + options.id).datepicker( "setDate", year + '-' + month + '-' + day);
    				}

                    if(options.type == "date-picker1")
                    {

                        $("#" + options.id).datepicker({
                            rtl: Metronic.isRTL(),
                            orientation: "left",
                            autoclose: true
                        });
                        $("#" + options.id).datepicker( "setDate", year + '-' + month + '-' + yday );

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

                    if (options.type1 == "shift")
                    {
                        $scope.filterData[options.model1] = options.optionList1[0];
                    }

    			});
    		});

    	});
    }
    
    $scope.loadReport = function()
    {


        $scope.filterData.action = $location.search().action;

    	$http.post(querytarget, {reportId: $location.search().id, output: "preview", filterData: $scope.filterData, query:$location.search()})
    	.success(function(res, status, headers, config){



    		$scope.report = $sce.trustAsHtml(res);

    		Metronic.unblockUI();


                $http.post(querytarget, {reportId: $location.search().id, filterData: $scope.filterData, output: "setting"})
                    .success(function(res, status, headers, config){





                        $scope.setting = res;
                        Metronic.unblockUI();
                        $timeout(function(){


                            res.filterOptions.forEach(function(options){
                                if(options.type == "date-picker")
                                {
                                    $("#" + options.id).datepicker({
                                        rtl: Metronic.isRTL(),
                                        orientation: "left",
                                        autoclose: true
                                    });

                                }

                                if(options.type == "date-picker1")
                                {

                                    $("#" + options.id).datepicker({
                                        rtl: Metronic.isRTL(),
                                        orientation: "left",
                                        autoclose: true
                                    });


                                    $("#" + options.id1).datepicker({
                                        rtl: Metronic.isRTL(),
                                        orientation: "left",
                                        autoclose: true
                                    });


                                }

                                if (options.type == "version-dropdown")
                                {
                                    var pos = options.optionList.map(function(e) {
                                        return e.value;
                                    }).indexOf(options.defaultValue);
                                    $scope.filterData[options.model] = options.optionList[pos];
                                }


                            });
                        });







                    });



    	});
    }

    $scope.sendFile = function(file)
    {
        if($location.search().id=='pickinglist9f' && file.warning != false){
            $scope.invoiceStatusCheck(file);
        }else{

            if(file.warning != false)
            {
                bootbox.confirm(file.warning, function(result) {
                    if(result == true)
                    {
                        $scope.sendRealFile(file.type);
                    }
                });
            }
            else {
                    $scope.sendRealFile(file.type);
            }
        }
    	
    }

    $scope.invoiceStatusCheck = function(file){



        $scope.shift = $scope.filterData.shift.value;
        $scope.zone.zoneId =$scope.filterData.zone.value;
        $scope.deliveryDate = $scope.filterData.deliveryDate;

        $http({
            method: 'POST',
            url: endpoint + "/getInvoiceStatusMatchPrint.json",
            data: {zone:$scope.zone,shift:$scope.shift,deliveryDate:$scope.deliveryDate}
        }).success(function (res) {


            if(res['0'].countInDataMart>0) {

                //var reject = res['3'].countInDataMart;

                //var pending = res['1'].countInDataMart;

                var version = res['0'].countInDataMart;

                bootbox.dialog({
                    message: version+"張單還沒產生備貨單,處理完才可產生",
                    title: "警告!!!",
                    buttons: {
                        success: {
                            label: "取消",
                            className: "green",
                            callback: function() {

                            }
                        }
                    }
                });
            }else{
                bootbox.dialog({
                    message: file.warning,
                    title: file.name,
                    buttons: {
                        success: {
                            label: "取消",
                            className: "green",
                            callback: function() {

                            }
                        },
                        danger: {
                            label: "確定",
                            className: "red",
                            callback: function() {

                                $scope.sendRealFile(file.type)
                            }
                        }
                    }
                });
            }
        });
    }
    
    $scope.sendRealFile = function(type)
    {
    	var queryObject = {
    			filterData	:	$scope.filterData,
    			reportId	:	$location.search().id,
    			output		:	type,
    	};

    	var queryString = $.param( queryObject );
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
            $scope.filterData.productId = id;

        $location.search().action = action;
        $scope.action= action;

        $scope.loadSetting();
        $scope.loadReport();
    }

    $scope.selectClient = function(id,action){
        if(id !==null)
            $scope.filterData.customerId = id;

        $location.search().action = action;
        $scope.action= action;

        $scope.loadSetting();
        $scope.loadReport();
    }
    
});