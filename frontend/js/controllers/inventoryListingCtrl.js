'use strict';

Metronic.unblockUI();

function editProduct(id)
{
	var scope = angular.element(document.getElementById("queryInfo")).scope();
    scope.$apply(function () {
    	scope.editProduct(id);
    });
}

function salesReturn(id)
{
    var scope = angular.element(document.getElementById("queryInfo")).scope();
    scope.$apply(function () {
        scope.salesReturn(id);
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

app.controller('inventoryListingCtrl', function($scope, $rootScope, $http, SharedService, $location, $timeout) {
	
	var fetchDataDelay = 250;   // milliseconds
    var fetchDataTimer;
	var querytarget = endpoint + '/queryInventory.json';
	var iutarget = endpoint + '/manipulateInventory.json';
	
	$scope.filterData = {
			'group'	:	'',
			'keyword'	:	'',
            'status' : '',
            'productLocation' : ''
		};

    $scope.hasCommission = '';
    $scope.submit = true;
	$scope.info_def = {
			'group'	:	false,
			'productId' : '',
			'good_qty' : '',
			'damage_qty' :	'',
            'expiry_date': '',
            'remark' : ''

	};
	
	$scope.submitbtn = true;
	$scope.newId = "";
	
	$scope.info = {};



    $scope.itemlist = [0];
    $scope.repack = {
        productId: '',
        productName: '',
        products: ''
    };
    $scope.selfdefine = [];
    $scope.selfdefineS = {
        'productId': '',
        'qty': '',
        'unit': '',
        'productlevel': '',
        'adjustType':'1',  //repack is 1,退貨 is 2,when " " is 3
        'adjustId':'',
        'receivingId':'',
        'good_qty':'',
        deleted : 0

    }

    $scope.receiveInclude = [];
    $scope.receive = {
        'receivingId': '',
        'good_qty': ''
    };

    $scope.totalline = 1;

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






    $scope.rePack = function(){

        $scope.out = {}

        $("#repackAll").modal({backdrop: 'static'});

        $scope.itemlist.forEach(function(key){
            $scope.selfdefine[key] = $.extend(true, {}, $scope.selfdefineS);
        });

    }



    $scope.addRows = function () {
        var j = $scope.totalline;
        $scope.selfdefine[j] = $.extend(true, {}, $scope.selfdefineS);
        $scope.totalline += 1;
    }

    $scope.deleteRow = function(i)
    {
        $scope.selfdefine[i].deleted = 1;

    }

    $scope.submitRepack = function () {


        if($scope.out.total_normalized_unit != $scope.totalAmount){
            alert('輸出及輸入數量必需相同');
            return false
        }

        if($scope.out.total_normalized_unit > $scope.out.available){
            alert('Repack amount cant more than available amount');
            return false
        }


        insertToAdjust($scope.selfdefine);

    }


    $scope.searchReceiving = function(){
        var target = endpoint + '/outRepackProduct.json';
        $http.post(target, {productId:$scope.out.productId})
            .success(function (res, status, headers, config) {

                $scope.out.productName = res.productName;
                var availableunit = [];
                if(res.productPackingName_carton != '')
                    availableunit = availableunit.concat([{value: 'carton', label: res.productPackingName_carton}]);
                if(res.productPackingName_unit != '')
                    availableunit = availableunit.concat([{value: 'unit', label: res.productPackingName_unit}]);
                $scope.out.unit = availableunit[0];
                $scope.out.available = res.total;
                $scope.out.availableunit = availableunit;
                $scope.out.normalized_unit = res.normalized_unit;

            });
    }

    $scope.calc = function(){

        var finalize_amount = 0;
        if($scope.out.unit.value=='carton')
            finalize_amount  = $scope.out.normalized_unit * $scope.out.qty;
        else if ($scope.out.unit.value=='unit')
            finalize_amount = $scope.out.qty;


        $scope.out.total_normalized_unit =  finalize_amount;
    }

    $scope.calcIn = function(){
        $scope.totalAmount = 0;

        var i = 0;
        $scope.selfdefine.forEach(function(item){
            if(item.deleted == 0)
            {
                var finalize_amount = 0;
                if(item.unit.value=='carton') {
                    finalize_amount = item.normalized_unit * item.qty;
                    $scope.selfdefine[i]['packing_size'] = item.normalized_unit;
                }else if (item.unit.value=='inner'){
                    finalize_amount = item.normalized_inner * item.qty;
                    $scope.selfdefine[i]['packing_size'] = item.normalized_inner;
                }else{
                    finalize_amount = item.qty
                    $scope.selfdefine[i]['packing_size'] = item.qty;
                }
                $scope.totalAmount += Number(finalize_amount);
                $scope.selfdefine[i]['total_finalized_unit'] = finalize_amount;

            }
            i++;
        });
    }

    $scope.searchProduct = function (value,i)
    {
        var target = endpoint + '/preRepackProduct.json';
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
                    $scope.selfdefine[i]['normalized_unit'] = res.normalized_unit;
                    $scope.selfdefine[i]['normalized_inner'] = res.productPacking_unit;
                }
            });
    }

    function insertToAdjust(items)
    {
        if(items != "")
        {
            var target = endpoint + '/addAjust.json';
            $http.post(target, {items:items,outProduct:$scope.out})
                .success(function (res, status, headers, config) {

                        $("#repackAll").modal('hide');

                        $scope.updateDataSet();

                        Metronic.alert({
                            container: '#firstContainer', // alerts parent container(by default placed after the page breadcrumbs)
                            place: 'prepend', // append or prepent in container
                            type: 'success',  // alert's type
                            message: '<span style="font-size:16px;">包裝成功</span>',  // alert's message
                            close: true, // make alert closable
                            reset: true, // close all previouse alerts first
                            focus: true, // auto scroll to the alert after shown
                            closeInSeconds: 0, // auto close after defined seconds
                            icon: 'warning' // put icon before the message
                        });

                });
        }
    }


    $scope.editProduct = function(id)
    {
    	$http.post(querytarget, {mode: "single", id: id})
    	.success(function(res, status, headers, config){    
    		$scope.info = $.extend(true, {}, $scope.info_def);
    		$scope.info = res;
            $scope.info.adjusted_good_qty = res.good_qty;
            $scope.info.adjusted_damage_qty = res.damage_qty;
      		$("#inventoryFormModal").modal({backdrop: 'static'});
    	});
    }

    $scope.salesReturn = function(id)
    {
        $http.post(querytarget, {mode: "single", id: id})
            .success(function(res, status, headers, config){
                $scope.info = $.extend(true, {}, $scope.info_def);
                $scope.info = res;
                $scope.info.return_good_qty = 0;
                $scope.info.return_damage_qty = 0;
                $("#inventorySalesReturnModal").modal({backdrop: 'static'});
            });
    }



    $scope.submitSalesReturnForm = function()
    {
        $http.post(iutarget, {info: $scope.info, mode:'salesReturn'})
    .success(function(res, status, headers, config){
        $("#inventorySalesReturnModal").modal('hide');
        $scope.updateDataSet();

        Metronic.alert({
            container: '#firstContainer', // alerts parent container(by default placed after the page breadcrumbs)
            place: 'prepend', // append or prepent in container
            type: 'success',  // alert's type
            message: '<span style="font-size:16px;">提交成功</span>',  // alert's message
            close: true, // make alert closable
            reset: true, // close all previouse alerts first
            focus: true, // auto scroll to the alert after shown
            closeInSeconds: 0, // auto close after defined seconds
            icon: 'warning' // put icon before the message
        });

    });
}


    $scope.submitProductForm = function()
    {
    		 $http.post(iutarget, {info: $scope.info , mode:'stockTake'})
        	.success(function(res, status, headers, config){    
      			$("#inventoryFormModal").modal('hide');
        		$scope.updateDataSet();

                     Metronic.alert({
                         container: '#firstContainer', // alerts parent container(by default placed after the page breadcrumbs)
                         place: 'prepend', // append or prepent in container
                         type: 'success',  // alert's type
                         message: '<span style="font-size:16px;">提交成功</span>',  // alert's message
                         close: true, // make alert closable
                         reset: true, // close all previouse alerts first
                         focus: true, // auto scroll to the alert after shown
                         closeInSeconds: 0, // auto close after defined seconds
                         icon: 'warning' // put icon before the message
                     });

        	});

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
                            { "data": "good_qty" },
                            { "data": "damage_qty" },
                            { "data": "expiry_date" },
                            { "data": "updated_at" },
                            { "data": "updated_by" },
                            { "data": "link" },
                            { "data": "sales_return" },
                ],
                
                "order": [
                    [1, "asc"]
                ] // set first column as a default sort by asc
            }
        });

    }




});