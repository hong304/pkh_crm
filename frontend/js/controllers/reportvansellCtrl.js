'use strict';

app.controller('reportvansellCtrl', function($scope, $http, SharedService, $timeout, $location, $sce) {

	var querytarget = endpoint + "/getVansellreport.json";


	$scope.report = "";
	$scope.filterData = {
			'shift' : '1',
            deliveryDate : ''

	};
    $scope.qty = [];
$scope.selfdefine = [];
$scope.totalline = 0;
    $scope.initline = 0;
    $scope.selfdefineS = {
        'productId' : '',
        'name' : '',
        'qty' : '',
        'unit'  : '',
        'productlevel' : ''
    }

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
    	$http.post(querytarget, {reportId: 'vanselllist', output: "preview", filterData: $scope.filterData})
            .success(function(res){

    		$scope.report = res.normal;
            $scope.report_selfdefine = res.selfdefine;

           var i = 0;
                $scope.report.forEach(function(item) {
                    $scope.qty[i] = $.extend(true, {}, $scope.invoiceStructure);
                    $scope.qty[i]['productId'] = item.productId;
                    $scope.qty[i]['value'] = item.qty;
                    $scope.qty[i]['unit'] = item.unit;
                    $scope.qty[i]['productlevel'] = item.productlevel;
                   i++;
               });
                $scope.selfdefine = [];

                var j = 0;
                $scope.report_selfdefine.forEach(function(item) {
                    $scope.selfdefine[j] = $.extend(true, {}, $scope.selfdefineS);
                    $scope.selfdefine[j]['productId'] = item.productId;
                    $scope.selfdefine[j]['productName'] = item.name;
                    $scope.selfdefine[j]['qty'] = item.qty;
                    $scope.selfdefine[j]['unit'] = item.unit;
                    j++;
                });
                $scope.initline = j;
                $scope.totalline = $scope.initline;
    		Metronic.unblockUI();
    	});
    }

    $scope.addRows = function(){

   var j = $scope.totalline;

            $scope.selfdefine[j] = $.extend(true, {}, $scope.selfdefineS);
            $scope.selfdefine[j]['productId'] = '';
            $scope.selfdefine[j]['productName'] = '';
            $scope.selfdefine[j]['qty'] = '';
            $scope.selfdefine[j]['unit'] = '';
        $scope.totalline += 1;

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

        $http.post(querytarget, {reportId: 'vanselllist', output: "create", filterData: $scope.filterData,data:$scope.qty,selfdefine:$scope.selfdefine})
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