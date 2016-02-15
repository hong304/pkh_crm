'use strict';

function editProduct(productId)
{
	var scope = angular.element(document.getElementById("queryInfo")).scope();
    scope.$apply(function () {
    	scope.editProduct(productId);
    });
}

function delCustomer(id)
{
    var scope = angular.element(document.getElementById("queryInfo")).scope();
    scope.$apply(function () {

        bootbox.dialog({
            message: "刪除產品後將不能復原，確定要刪除產品嗎？",
            title: "刪除客戶",
            buttons: {
                success: {
                    label: "取消",
                    className: "green",
                    callback: function() {

                    }
                },
                danger: {
                    label: "確定刪除",
                    className: "red",
                    callback: function() {
                        scope.delCustomer(id);
                    }
                }
            }
        });

    });
}

app.controller('productMaintenanceCtrl', function($scope, $rootScope, $http, SharedService, $location, $timeout, $interval) {
	
	var fetchDataDelay = 250;   // milliseconds
    var fetchDataTimer;
	var querytarget = endpoint + '/queryProduct.json';
	var iutarget = endpoint + '/manipulateProduct.json';
	
	$scope.filterData = {
			'group'	:	'',
			'keyword'	:	'',
            'status' : '',
            'productLocation' : ''
		};

    $scope.hasCommission = '';
    $scope.vansale = '';
    $scope.allowNegativePrice = '';
    $scope.allowSeparate = '';
    
    $scope.submit = true;
	$scope.info_def = {
			'group'	:	false,
			'productId' : '',
			'productLocation' : '',
			'productStatus'	:	'',
            'supplierProductStatus' : '',
			'productPacking_carton' : '1',
			'productPacking_inner' : '1',
			'productPacking_unit' : '1',
			'productPacking_size' : '',
			'productPackingName_carton' : '',
			'productPackingName_inner' : '',
			'productPackingName_unit' : '',
			'productPackingInterval_carton' : '',
			'productPackingInterval_inner' : '',
			'productPackingInterval_unit' : '',
			'productStdPrice_carton' : '',
			'productStdPrice_inner' : '',
			'productStdPrice_unit' : '',
			'productMinPrice_carton' : '',
			'productMinPrice_inner' : '',
			'productMinPrice_unit' : '',
			'productCost_unit'	:	'',
			'productName_chi' : '',
			'productName_eng' : '',
            'productnewId' :'',
            'hasCommission' : '',
            'allowNegativePrice' : '',
            'allowSeparate' : '',
            'commissiongroup' : '',
            'vansale' : ''
	};
	
	$scope.submitbtn = true;
	$scope.newId = "";
	
	$scope.info = {};
	
    $scope.$on('$viewContentLoaded', function() {   
    	
        Metronic.initAjax();        
        $scope.systeminfo = $rootScope.systeminfo;   

    });
    
    $scope.$watch(function() {
    	return $rootScope.systeminfo;
  	}, function() {
  		$scope.systeminfo = $rootScope.systeminfo;
        $scope.updateDataSet();
  	}, true);

    $scope.$watch('filterData', function() {
        $scope.updateDataSet();
    }, true);

    $scope.delCustomer = function(id){
        $http({
            method: 'POST',
            url: iutarget,
            data: {mode:'del',customer_id:id}
        }).success(function () {
            $scope.del = true;
            $scope.updateDataSet();
        });




    }
    $scope.checkIdexist = function(){

        $http.post(querytarget, {mode: "checkId", productId: $scope.info.productnewId})
            .success(function(res, status, headers, config){
                $scope.productIdused = res;
                   if($scope.productIdused == 1){
                       $scope.submit = false;
                   }else
                       $scope.submit = true;

            });

    }

    $scope.getGroupPrefix = function(){
        $http.post(querytarget, {mode: "getGroupPrefix", group: $scope.info.group})
            .success(function(res){
                $scope.prefix = res;
            });
    }

    $scope.getNewId = function(){
        $http.post(iutarget, {mode: "getNewId", groupPrefix: $scope.info.groupPrefix})
            .success(function(res){
               if(res.length == 1)
                    $scope.info.productnewId = $scope.info.groupPrefix+'00'+res;
                else if(res.length == 2)
                    $scope.info.productnewId = $scope.info.groupPrefix+'0'+res;
                else if(res.length > 2){
                   if(isNaN($scope.info.groupPrefix))
                       $scope.info.productnewId = $scope.info.groupPrefix+res;
                   else
                       $scope.info.productnewId = res;
               }
                $scope.checkIdexist();
            });
    }

    $scope.editProduct = function(productId)
    {
    	$scope.newId = "";
    	$scope.submitbtn = true;
    	$http.post(querytarget, {mode: "single", productId: productId}) //queryProduct.json
    	.success(function(res, status, headers, config){    
    		$scope.info = $.extend(true, {}, $scope.info_def);
    		$scope.info = res;
            $scope.hasCommission = res.hasCommission;
            $scope.allowNegativePrice = res.allowNegativePrice;
            $scope.allowSeparate = res.allowSeparate;
            $scope.vansale = res.vansale;
    		//console.log($scope.info);

    		var floorcat = [];
        	floorcat = floorcat.concat([{value: '1', label: "1F"}]);
        	floorcat = floorcat.concat([{value: '9', label: "9F"}]);
        	$scope.floorcat = floorcat;
        	
        	var pos = floorcat.map(function(e) { 
				return e.value; 
			  }).indexOf(res.productLocation);
        	
        	$scope.info.productLocation = floorcat[pos];
        	
        	
        	var status = [];
        	status = status.concat([{value: 'o', label: "正常"}]);
        	status = status.concat([{value: 's', label: "暫停"}]);
        	$scope.status = status;
        	
        	var pos = status.map(function(e) { 
				return e.value; 
			  }).indexOf(res.productStatus);
        	
        	$scope.info.productStatus = status[pos];


            var supplierProductStatus = [];
            supplierProductStatus = supplierProductStatus.concat([{value: 'o', label: "正常"}]);
            supplierProductStatus = supplierProductStatus.concat([{value: 's', label: "暫停"}]);
            $scope.supplierProductStatus = supplierProductStatus;

            var pos = supplierProductStatus.map(function(e) {
                return e.value;
            }).indexOf(res.supplierProductStatus);

            $scope.info.supplierProductStatus = supplierProductStatus[pos];

                var pos = $scope.systeminfo.productgroup.map(function(e) {
                    return e.groupid;
                }).indexOf(res.department+'-'+res.group+'-');

                $scope.info.group = $scope.systeminfo.productgroup[pos];

            $scope.commissiongroup = res.commissiongroup;

            var pos = $scope.commissiongroup.map(function(e) {
                return e.commissiongroupId;
            }).indexOf(res.commissiongroupId);

            $scope.info.commissiongroup = $scope.commissiongroup[pos];



    		$("#productFormModal").modal({backdrop: 'static'});
    		/*
    		var pos = $scope.systeminfo.availableZone.map(function(e) { 
				return e.zoneId; 
			  }).indexOf(res.deliveryZone);
	    	
	    	$scope.customerInfo.deliveryZone = $scope.systeminfo.availableZone[pos];
	    	*/
    	});
    	
    	
    }
    
 $scope.addProduct = function()
    {

        $http.post(querytarget, {mode: "commissiongroup"}) //queryProduct.json
            .success(function(res, status, headers, config){
                $scope.commissiongroup = res.commissiongroup;

                var pos = $scope.commissiongroup.map(function(e) {
                    return e.commissiongroupId;
                }).indexOf(res.commissiongroupId);


            });

        $scope.info = $.extend(true, {}, $scope.info_def);

    	$scope.newId = "";
    	var floorcat = [];
    	floorcat = floorcat.concat([{value: '1', label: "1F"}]);
    	floorcat = floorcat.concat([{value: '9', label: "9F"}]);
    	$scope.floorcat = floorcat;
    	
    	var status = [];
    	status = status.concat([{value: 'o', label: "正常"}]);
    	status = status.concat([{value: 's', label: "暫停"}]);
    	$scope.status = status;
        $scope.info.productStatus = status[0];

        var supplierProductStatus = [];
        supplierProductStatus = supplierProductStatus.concat([{value: 'o', label: "正常"}]);
        supplierProductStatus = supplierProductStatus.concat([{value: 's', label: "暫停"}]);
        $scope.supplierProductStatus = supplierProductStatus;
        $scope.info.supplierProductStatus = supplierProductStatus[0];

    	$scope.submitbtn = true;

    	
    	$("#productFormModal").modal({backdrop: 'static'});
    	
    }
    $scope.selectCom = function(i){
        if(i == 'yes')
        $scope.info.hasCommission= 1;
        else
            $scope.info.hasCommission= 0;
    }

    $scope.selectNeg = function(i){
        if(i == 'yes')
            $scope.info.allowNegativePrice= 1;
        else
            $scope.info.allowNegativePrice= 0;
    }

    $scope.selectSep = function(i){
        if(i == 'yes')
            $scope.info.allowSeparate= 1;
        else
            $scope.info.allowSeparate= 0;
    }

    $scope.selectVan = function(i){
        if(i == 'yes')
            $scope.info.vansale= 1;
        else
            $scope.info.vansale= 0;
    }

    $scope.submitProductForm = function()
    {

        if(!$scope.submit)
            alert('產品編號不能用');
    	else if(
    			$scope.info.productLocation == "" ||
    			$scope.info.productName_chi == ""  ||
    			(!$scope.info.productId && !$scope.info.group) || (!$scope.info.productId && !$scope.info.productnewId)
    	)
    	{
    		alert('請輸入所需資料');
    	}
    	else
    	{
    		$scope.submitbtn = false;
    		 $http.post(iutarget, {info: $scope.info}) //manipulateProduct.json
        	.success(function(res, status, headers, config){    
        		   
        		$scope.submitbtn = false;

        		if(res.mode == 'update')
        		{
        			$("#productFormModal").modal('hide');
        		}
        		else if(res.error == 1)
        		{
                    alert('Error: error code (00136)');
                    $scope.newId = res.msg;
                    $scope.submitbtn = true;
        		}else{
                    $scope.newId = "編號: " + res.id;
                }
        		$scope.updateDataSet();
        		
        	});
    		 
    	}
    	
    }
    
    $scope.updateKeyword = function()
    {
    	$timeout.cancel(fetchDataTimer);
    	fetchDataTimer = $timeout(function () {
    		$scope.updateDataSet();
    	}, fetchDataDelay);
    }
    
    $scope.updateDataSet = function()
    {
    	
    	var grid = new Datatable();
    	    	
    	//var info = grid.page.info();
    	if(!$scope.firstload)
		{
    		$("#datatable_ajax").dataTable().fnDestroy(); 
		}
    	else
		{
    		$scope.firstload = false;
		}
        grid.init({
            src: $("#datatable_ajax"),
            onSuccess: function (grid) {
                // execute some code after table records loaded
            	
            },
            onError: function (grid) {
                // execute some code on network or other general error  
            },
            loadingMessage: 'Loading...',
            dataTable: { // here you can define a typical datatable settings from http://datatables.net/usage/options 

                
                "bStateSave": false, // save datatable state(pagination, sort, etc) in cookie.

                "lengthMenu": [
                    [10, 20, 50],
                    [10, 20, 50] // change per page values here
                ],
                "pageLength": 50, // default record count per page
                "ajax": {
                    "url": querytarget, // ajax source
                    "type": 'POST',
                    "data": {filterData: $scope.filterData, mode: "collection"},
            		"xhrFields": {withCredentials: true}
                },
                "language": {
                    "lengthMenu": "顯示 _MENU_ 項結果",
                    "zeroRecords": "沒有匹配結果",
                    "sEmptyTable":     "沒有匹配結果",
                    "info": "顯示第 _START_ 至 _END_ 項結果，共 _TOTAL_ 項",
                    "infoEmpty": "顯示第 0 至 0 項結果，共 0 項",
                    "infoFiltered": "(filtered from _MAX_ total records)",
                    "Processing":   "處理中...",
                    "Paginate": {
                        "First":    "首頁",
                        "Previous": "上頁",
                        "Next":     "下頁",
                        "Last":     "尾頁"
                    }
                },
                "columns": [
                            { "data": "productId" },
                            { "data": "productName_chi" },
                            { "data": "productStatus" },
                             { "data": "hasCommission" },
                            { "data": "productStdPrice_carton" },
                            { "data": "productStdPrice_inner" },
                            { "data": "productStdPrice_unit" },
                            { "data": "updated_at" },
                            { "data": "link" },
                ],
                
                "order": [
                    [1, "asc"]
                ] // set first column as a default sort by asc
            }
        });

    }
    
});