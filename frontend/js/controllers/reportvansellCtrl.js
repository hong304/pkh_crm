'use strict';

app.controller('reportvansellCtrl', function($scope, $http, SharedService, $timeout, $location, $sce) {

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
        availableunit	:	[]
    }

    $scope.invoiceStructure = {
        'value' : '',
        'org_qty' : '',
        'unit'  : ''
    }
	
    $scope.$on('$viewContentLoaded', function() {
        Metronic.initAjax();
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

    $scope.reset = function(){
        $scope.prepareforreport = false;
        $scope.allowSubmission = true;
    }
    $scope.loadReport = function()
    {
        if(!$scope.allowSubmission)
            return false;
        $scope.allowSubmission=false;

        $scope.prepareforreport = true;
    	$http.post(querytarget, {reportId: 'vanselllist', output: "preview", filterData: $scope.filterData})
            .success(function(res){


    		$scope.report = res.normal;
            $scope.report_selfdefine = res.selfdefine;

           $scope.qty = [];
                var i = 0;
                $scope.report.forEach(function(item) {
                    if(item.qty == item.org_qty && item.self_enter == 0)
                        item.qty = '';
                    $scope.qty[i] = $.extend(true, {}, $scope.invoiceStructure);
                    $scope.qty[i]['id'] = item.id;
                    $scope.qty[i]['productId'] = item.productId;
                    $scope.qty[i]['org_qty'] = item.org_qty;
                    $scope.qty[i]['value'] = item.qty;
                    $scope.qty[i]['unit'] = item.unit;
                    $scope.qty[i]['productlevel'] = item.productlevel;
                    i++;
               });
                console.log($scope.qty);

                $scope.selfdefine = [];

                var j = 0;
                $scope.report_selfdefine.forEach(function(item) {
                    console.log(item);

                    $scope.selfdefine[j] = $.extend(true, {}, $scope.selfdefineS);
                    $scope.selfdefine[j]['productId'] = item.productId;
                    $scope.selfdefine[j]['productName'] = item.name;
                    $scope.selfdefine[j]['qty'] = item.qty;
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
                         $scope.selfdefine[i] = $.extend(true, {}, $scope.selfdefineS);
                         $scope.selfdefine[i]['productId'] = value;
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

if(!$scope.prepareforreport){
    alert('請按提交,再產生PDF');
    return false;
}
console.log($scope.selfdefine);

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