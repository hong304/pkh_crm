'use strict';

app.controller('vansaleManagementCtrl', function($scope, $http, SharedService, $timeout, $location, $sce,$rootScope) {

	var querytarget = endpoint + "/getVansellreport.json";
    $scope.allowSubmission = true;

	$scope.report = "";
	$scope.filterData = {
			'shift' : '1',
            deliveryDate : ''

	};

$scope.selfdefine = [];
$scope.totalline = 0;
    $scope.initline = 0;
    $scope.selfdefineS = {
        'productId' : '',
        'name' : '',
        'qty' : '',
        'unit'  : '',
        'productlevel' : '',
        deleted : 0,
        availableunit	:	[],
        'return_qty' : ''
    }

    $scope.vanStracture = {
        'qty' : '',
        'org_qty' : '',
        'unit'  : '',
        'preload' : '',
        'return_qty' : ''
    }
	
    $scope.$on('$viewContentLoaded', function() {
        Metronic.initAjax();
        $scope.systeminfo = $rootScope.systeminfo;
        $scope.loadSetting();

    });



	$scope.$watch(function() {
	  //return $scope.filterData;
	}, function() {
	 // $scope.loadReport();
	}, true);

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

                        var target = endpoint + '/getHoliday.json';

                        $http.get(target)
                            .success(function(res){

                                var today = new Date();
                                var plus = today.getDay() == 6 ? 2 : 1;

                                var currentDate = new Date(new Date().getTime() + 24 * 60 * 60 * 1000 * plus);
                                if(today.getHours() > 11 || today.getDay() == 0)
                                {
                                    var nextDay = currentDate;
                                }
                                else
                                {
                                    var nextDay = today;
                                }

                                var flag = true;
                                var working_date = ("0" + (nextDay.getMonth() + 1)).slice(-2)+'-'+("0" + (nextDay.getDate())).slice(-2);
                                do{
                                    flag= true;
                                    $.each( res, function( key, value ) {
                                        if(value == working_date){
                                            flag = false;
                                            var today = new Date(nextDay.getFullYear()+'-'+working_date);
                                            nextDay = new Date(today);
                                            nextDay.setDate(today.getDate()+1);

                                            if(nextDay.getDay() == 0)
                                                nextDay.setDate(today.getDate()+2);

                                            working_date = ("0" + (nextDay.getMonth() + 1)).slice(-2)+'-'+("0" + (nextDay.getDate())).slice(-2);
                                        }
                                    });
                                }while(flag == false);

                                var day = ("0" + (nextDay.getDate())).slice(-2);
                                var month = ("0" + (nextDay.getMonth() + 1)).slice(-2);
                                var year = nextDay.getFullYear();

                                var current_day = ("0" + (today.getDate())).slice(-2);
                                var current_month = ("0" + (today.getMonth() + 1)).slice(-2);
                                var current_year = today.getFullYear();

                                $("#" + options.id).datepicker({
                                    rtl: Metronic.isRTL(),
                                    orientation: "left",
                                    autoclose: true
                                });
                                $("#" + options.id).datepicker( "setDate", current_year + '-' + current_month + '-' + current_day );

                                $scope.next_working_day =  day + '/' + month;
                                $scope.filterData.next_working_day =  year + '-' + month + '-' + day;
                            });

    				}

    				else if (options.type == "single-dropdown")
    				{
    					$scope.filterData[options.model] = options.optionList[0];

    				}

    			});


    		});

    	});


    }

    $scope.reset = function(){
        $scope.prepareforreport = false;
        $scope.allowSubmission = true;
        $scope.show = $scope.setting.shift[$scope.filterData.zone.value];
    }

    $scope.finalsubmitnextvanqty=function(){
        $http.post(querytarget, {output: "vanPost", filterData: $scope.filterData,data:$scope.info,selfdefine:$scope.selfdefine}) //getVansellreport.json
            .success(function(res){
                $scope.preload_check = res.preload_check;
            });
    }

    $scope.unlock=function(){
        $http.post(querytarget, {output: "unlock", filterData: $scope.filterData}) //getVansellreport.json
            .success(function(res){
                $scope.preload_check = res.preload_check;
            });
    }

    $scope.loadReport = function(i)
    {

        if(i==0){
            if(!$scope.allowSubmission)
                return false;
            $scope.allowSubmission=false;


        }

        $scope.prepareforreport = true;
    	$http.post(querytarget, {output: "preview", filterData: $scope.filterData, mode: i,data:$scope.info,selfdefine:$scope.selfdefine}) //getVansellreport.json
            .success(function(res){


    		$scope.report = res.normal;
            $scope.report_selfdefine = res.selfdefine;
            $scope.preload_check = res.preload_check;


                console.log($scope.preload_check);

           $scope.info = [];
                var i = 0;
                $scope.report.forEach(function(item) {
                    $scope.info[i] = $.extend(true, {}, $scope.vanStracture);
                    $scope.info[i]['id'] = item.id;
                    $scope.info[i]['productId'] = item.productId;
                    $scope.info[i]['org_qty'] = item.org_qty;
                    if(item.return_qty == 0)
                        item.return_qty = '';
                    $scope.info[i]['return_qty'] = item.return_qty;
                    if(item.preload == 0)
                        item.preload = '';
                    $scope.info[i]['preload'] = item.preload;
                    $scope.info[i]['qty'] = item.qty;
                    $scope.info[i]['unit'] = item.unit;
                    $scope.info[i]['productlevel'] = item.productlevel;
                    i++;
               });


           $scope.selfdefine = [];
                var j = 0;
                $scope.report_selfdefine.forEach(function(item) {
                    $scope.selfdefine[j] = $.extend(true, {}, $scope.selfdefineS);
                    $scope.selfdefine[j]['productId'] = item.productId;
                    $scope.selfdefine[j]['productName'] = item.name;
                    $scope.selfdefine[j]['qty'] = item.qty;
                    if(item.preload == 0)
                        item.preload = '';

                    $scope.selfdefine[j]['preload'] = item.preload;
                    if(item.return_qty == 0)
                        item.return_qty = '';
                    $scope.selfdefine[j]['return_qty'] = item.return_qty;
                    $scope.selfdefine[j]['success'] = 1;

                    var availableunit = [];
                    //console.log(item);
                    if(item.products.productPackingName_carton != '')
                        availableunit = availableunit.concat([{value: 'carton', label: item.products.productPackingName_carton}]);
                    if(item.products.productPackingName_inner != '')
                    availableunit = availableunit.concat([{value: 'inner', label: item.products.productPackingName_inner}]);
                                    if(item.products.productPackingName_unit != '')
                        availableunit = availableunit.concat([{value: 'unit', label: item.products.productPackingName_unit}]);

                    $scope.selfdefine[j].availableunit=availableunit;

                    if(typeof $scope.selfdefine[j]['availableunit'][pos] == 'undefined'){
                        var pos = $scope.selfdefine[j].availableunit.map(function(e) {
                            return e.value;
                        }).indexOf(item.productlevel);
                    }
                    $scope.selfdefine[j]['unit'] = $scope.selfdefine[j]['availableunit'][pos];


                    //$scope.selfdefine[j]['unit']['label'] = item.unit;
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
            $scope.selfdefine[j]['preload'] = '';
        $scope.totalline += 1;

    }

    $scope.deleteRow = function(i)
    {
        $scope.selfdefine[i].deleted = 1;

    }

    $scope.searchProduct = function (value,i)
    {
        var product = value;
        var target = endpoint + '/preRepackProduct.json';
        if(product.length>2)
            $http.post(target, {productId:value})
                .success(function (res, status, headers, config) {
                    $scope.selfdefine[i]['success'] = 0;
                    if(typeof res == "object")
                    {
                        var availableunit = [];
                        if(res.productPackingInterval_unit > 0)
                            availableunit = availableunit.concat([{value: 'unit', label: res.productPackingName_unit}]);
                        if(res.productPackingInterval_inner > 0)
                            availableunit = availableunit.concat([{value: 'inner', label: res.productPackingName_inner}]);
                        if(res.productPackingInterval_carton > 0)
                            availableunit = availableunit.concat([{value: 'carton', label: res.productPackingName_carton}]);

                        // $scope.selfdefine[i]['availableunit'] = availableunit.reverse();
                        $scope.selfdefine[i]['availableunit'] = availableunit;
                        $scope.selfdefine[i]['unit'] = $scope.selfdefine[i]['availableunit'][0];
                        $scope.selfdefine[i]['qty'] = '';
                        $scope.selfdefine[i]['productName'] = res.productName_chi;
                        $scope.selfdefine[i]['success'] = 1;
                    }


                }).error(function(data, status, headers, config){
                        // $scope.selfdefine[i] = $.extend(true, {}, $scope.selfdefineS);
                        // $scope.selfdefine[i]['productId'] = value;
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
    
    $scope.sendRealFile = function(i)
    {

        console.log(i);

        if(!$scope.prepareforreport){
            alert('請按提交,再產生PDF');
            return false;
        }


        if(i == 'audit'){

                    var queryObject = {
                        filterData	:	$scope.filterData,
                        reportId	:	'vansaleAudit',
                        output		:	'auditPdf'
                    };
                    var queryString = $.param( queryObject );

                   window.open(endpoint + "/getVansellreport.json?" + queryString);


        }else
            $http.post(querytarget, {output: "create", filterData: $scope.filterData,data:$scope.info,selfdefine:$scope.selfdefine})
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