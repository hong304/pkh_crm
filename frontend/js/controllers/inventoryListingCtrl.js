'use strict';

Metronic.unblockUI();

function editProduct(id)
{
	var scope = angular.element(document.getElementById("queryInfo")).scope();
    scope.$apply(function () {
    	scope.editProduct(id);
    });
}


function viewProduct(productId)
{
    var scope = angular.element(document.getElementById("queryInfo")).scope();
    scope.$apply(function () {
        scope.viewProduct(productId);
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
            'productLocation' : '',
            'sorting' : '',
        'current_sorting': 'asc',
        'zerofilter' : '>'
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



    $scope.pinfo_def = {
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
        'allowSeparate' : ''
    };



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
        'productPackingName_unit':'',
        deleted : 0

    }

    $scope.receiveInclude = [];
    $scope.receive = {
        'receivingId': '',
        'good_qty': ''
    };

    $scope.totalline = 1;

    $scope.open = function($event) {
        $event.preventDefault();
        $event.stopPropagation();
        return $scope.opened = true;
    };

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



    $scope.click = function(event)
    {
        //  alert(event.target.id);
        $scope.filterData.sorting = event.target.id;

        if ($scope.filterData.current_sorting == 'asc'){
            // $scope.filterData.sorting_method = 'desc';
            $scope.filterData.current_sorting = 'desc';
        }else{
            $scope.filterData.current_sorting = 'asc';
        }

        $scope.updateDataSet();
    }


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

/*
        if($scope.out.total_normalized_unit != $scope.totalAmount){
            alert('輸出及輸入數量必需相同');
            return false
        }

        if($scope.out.total_normalized_unit > $scope.out.available){
            alert('Repack amount cant more than available amount');
            return false
        }
*/
        if($scope.out.total_normalized_unit != $scope.totalAmount)
        bootbox.dialog({
            message: "輸出及輸入數量不相同，確定要包裝產品嗎？",
            title: "產品包裝",
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
                        insertToAdjust($scope.selfdefine);
                    }
                }
            }
        });
else
            insertToAdjust($scope.selfdefine);


    }


    $scope.searchReceiving = function(){
        var product = $scope.out.productId;
        var target = endpoint + '/outRepackProduct.json';
        if(product.length>3)
        $http.post(target, {productId:$scope.out.productId})
            .success(function (res, status, headers, config) {
                console.log(res);
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
                $scope.out.productPackingName_unit = res.productPackingName_unit;

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
            console.log(item);
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
                $scope.finalUnitName = item.productPackingName_unit;
                $scope.selfdefine[i]['total_finalized_unit'] = finalize_amount;

            }
            i++;
        });
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
                    $scope.selfdefine[i]['normalized_unit'] = res.normalized_unit;
                    $scope.selfdefine[i]['normalized_inner'] = res.productPacking_unit;
                    $scope.selfdefine[i]['productPackingName_unit'] = res.productPackingName_unit;
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

                    if(res.status == 'error'){
                        alert(res.msg);
                        return false;
                    }
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

    $scope.viewProduct = function(productId)
    {
        $scope.newId = "";
        $scope.submitbtn = false;
        $http.post(endpoint + '/queryProduct.json', {mode: "single", productId: productId}) //queryProduct.json
            .success(function(res, status, headers, config){
                $scope.info = $.extend(true, {}, $scope.pinfo_def);
                $scope.info = res;

                $scope.hasCommission = res.hasCommission;
                $scope.allowNegativePrice = res.allowNegativePrice;
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



                $("#productFormModal").modal();
                /*
                 var pos = $scope.systeminfo.availableZone.map(function(e) {
                 return e.zoneId;
                 }).indexOf(res.deliveryZone);

                 $scope.customerInfo.deliveryZone = $scope.systeminfo.availableZone[pos];
                 */
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


    $scope.submitStockTake = function()
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
                    { "data": "poCode" ,"width": "5%" },
                            { "data": "productId" ,"width": "5%" },
                            { "data": "productName_chi","width": "15%" },
                            { "data": "good_qty" ,"width": "5%"},
                            { "data": "damage_qty" ,"width": "5%"},
                            { "data": "on_hold_qty" ,"width": "5%"},
                            { "data": "qty_carton" ,"width": "5%"},
                            { "data": "expiry_date","width": "7%" },
                            { "data": "bin_location","width": "5%" },
                            { "data": "updated_at","width": "10%" },
                            { "data": "link" ,"width": "5%"},

                ],
                
                "order": [
                    [1, "asc"]
                ] // set first column as a default sort by asc
            }
        });

    }




});