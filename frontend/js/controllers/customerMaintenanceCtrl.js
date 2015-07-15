'use strict';

function editCustomer(customerId)
{
	var scope = angular.element(document.getElementById("queryInfo")).scope();
    scope.$apply(function () {
    	scope.editCustomer(customerId);
    });
}

function delCustomer(id)
{
    var scope = angular.element(document.getElementById("queryInfo")).scope();
    scope.$apply(function () {

        bootbox.dialog({
            message: "刪除客戶後將不能復原，確定要刪除客戶嗎？",
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

app.controller('customerMaintenanceCtrl', function($scope, $rootScope, $http, SharedService, $location, $timeout, $interval) {
    var fetchDataTimer;
	var querytarget = endpoint + '/queryCustomer.json';
	var iutarget = endpoint + '/manipulateCustomer.json';
	
	$scope.filterData = {
        'name': '',
        'id': '',
        'phone': '',
        'zone': '',
        'status': '100',
        'groupname' : '',
        'address' : ''
	};

    $scope.submit = true;
	$scope.customerInfo_def = {
			'address_chi'		:	'', 
			'address_eng'		:	'',
			'contactPerson_1'	:	'',
			'contactPerson_2'	:	'',
			'currencyId'		:	'0',
			'customerId'		:	'',
			'customerName_chi'	:	'',
			'customerName_eng'	:	'',
			'customerTypeId'	:	'',
			'deliveryZone'		:	'',
			'discount'			:	'',
			'email'				:	'',
			'fax_1'				:	'',
			'fax_2'				:	'',
			'status'			:	'',
			'phone_1'			:	'',
			'phone_2'			:	'',
			'paymentTermId'		:	'',
			'routePlanningPriority':	'',
            'remark' : '',
            'shift' : '',
            'productnewId' :'',
            'customer_group_id' : ''

	};
	
	$scope.submitbtn = true;
	$scope.newId = "";
	
	$scope.customerInfo = {};
	
    $scope.$on('$viewContentLoaded', function() {   
        Metronic.initAjax();
        $scope.systeminfo = $rootScope.systeminfo;
        $('#keywordId').focus();
        $scope.updateDataSet();
    });
    
    $scope.$watch(function() {
    	return $rootScope.systeminfo;
  	}, function() {
  		$scope.systeminfo = $rootScope.systeminfo;  		
  	}, true);

    $scope.$watch('filterData.status', function() {
        $scope.updateDataSet();
    }, true);


    $scope.$on('handleCustomerUpdate', function(){
        // received client selection broadcast. update to the invoice portlet
        $scope.customerInfo.customer_group_id = SharedService.GroupId;
        $scope.customerInfo.groupname = SharedService.GroupName;
    });

  /*  $scope.$on('handleCustomerUpdate', function(){
    	
		//$scope.filterData.clientId = SharedService.clientId;
		//$scope.filterData.displayName = SharedService.clientId + " (" + SharedService.clientName + ")";
	//	$scope.filterData.zone = '';
		$scope.updateDataSet();
	});*/
    
    $scope.editCustomer = function(customerId)
    {
    	$scope.submitbtn = true;
        $(".phone").inputmask("99999999");

    	$http.post(querytarget, {mode: "single", customerId: customerId})
    	.success(function(res, status, headers, config){    
    		$scope.customerInfo = $.extend(true, {}, $scope.customerInfo_def);
    		$scope.customerInfo = res;
                if(res.group)
                    $scope.customerInfo.groupname = $scope.customerInfo.group.name;



    		var statuscat = [];
    		statuscat = statuscat.concat([{value: 1, label: "Normal"}]);
    		statuscat = statuscat.concat([{value: 2, label: "Suspended"}]);
        	$scope.statuscat = statuscat;
        	
        	var pos = $scope.statuscat.map(function(e) {

				return e.value;
			  }).indexOf(parseInt(res.status));



        	$scope.customerInfo.status = $scope.statuscat[pos];


                var statuscat1 = [];
                statuscat1 = statuscat1.concat([{value: 1, label: "早班"}]);
                statuscat1 = statuscat1.concat([{value: 2, label: "晚班"}]);
                $scope.statuscat1 = statuscat1;

                var pos = $scope.statuscat1.map(function(e) {

                    return e.value;
                }).indexOf(parseInt(res.shift));



                $scope.customerInfo.shift = $scope.statuscat1[pos];

    		$("#customerFormModal").modal({backdrop: 'static'});


    		var pos = $scope.systeminfo.availableZone.map(function(e) { 
				return e.zoneId; 
			  }).indexOf(res.deliveryZone);

	    	$scope.customerInfo.deliveryZone = $scope.systeminfo.availableZone[pos];
    	});
    	
    	
    }

    $scope.checkIdexist = function(){

        $http.post(querytarget, {mode: "checkId", customerId: $scope.customerInfo.productnewId})
            .success(function(res, status, headers, config){
                $scope.productIdused = res;
                if($scope.productIdused == 1){
                    $scope.submit = false;
                }else
                    $scope.submit = true;

            });

    }

    $scope.showselectgroup = function()
    {
        $("#selectGroupmodel").modal({backdrop: 'static'});
    }

    $scope.addCustomer = function()
    {
		var statuscat = [];
		statuscat = statuscat.concat([{value: '1', label: "Normal"}]);
		statuscat = statuscat.concat([{value: '2', label: "Suspended"}]);
    	$scope.statuscat = statuscat;

        var statuscat1 = [];
        statuscat1 = statuscat1.concat([{value: '1', label: "早班"}]);
        statuscat1 = statuscat1.concat([{value: '2', label: "晚班"}]);
        $scope.statuscat1 = statuscat1;

    	//console.log($scope.statuscat );

    	$scope.submitbtn = true;
    	$scope.customerInfo = $.extend(true, {}, $scope.customerInfo_def);
    	$scope.customerInfo.status = $scope.statuscat[0];
        $scope.customerInfo.shift =     $scope.statuscat1[0];

        $(".phone").inputmask("99999999");
    	$("#customerFormModal").modal({backdrop: 'static'});
    	
    }



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

    $scope.clearGroup = function(){
        $scope.customerInfo.customer_group_id = '';
        $scope.customerInfo.groupname = '';
    }
    $scope.submitCustomerForm = function()
    {

        if(!$scope.submit)
            alert('客户編號不能用');
        else if($scope.customerInfo.address_chi == ""  || $scope.customerInfo.customerName_chi == "" || $scope.customerInfo.deliveryZone.zoneName == ""
            || jQuery.trim($scope.customerInfo.routePlanningPriority).length == 0 || $scope.customerInfo.shift.label === "" || $scope.customerInfo.phone_1 == "" || $scope.customerInfo.paymentTermId == "")
    	{
    		alert('請輸入所需資料');
    	}
    	else
    	{
    		$http.post(iutarget, {customerInfo: $scope.customerInfo})
        	.success(function(res, status, headers, config){    
        		    		console.log($scope.customerInfo);
        		$scope.submitbtn = false;

        		if(res.mode == 'update')
        		{
        			$("#customerFormModal").modal('hide');
        		}
        		else
        		{
        			$scope.newId = "編號: " + res.id;
        		}
        		
        		$scope.updateDataSet();
        		
        	});
    	}
    	
    }
    
    $scope.updateZone = function()
    {
    	$scope.updateDataSet();
    }

    $scope.searchGroup = function(){
        $scope.updateDataSet();
    }

    $scope.searchClient = function()
    {
        $scope.updateDataSet();
    }

    $scope.updateDataSet = function()
    {



        $timeout.cancel(fetchDataTimer);
        fetchDataTimer = $timeout(function () {


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
            		"xhrFields": {withCredentials: true},
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
                            { "data": "customerId" },
                            { "data": "customerName_chi" },
                            { "data": "status" },
                            { "data": "deliveryZone" },
                            { "data": "routePlanningPriority" },
                            { "data": "paymentTerms" },
                            { "data": "phone_1" },
                            { "data": "contactPerson_1" },
                            { "data": "address_chi" },
                    { "data": "updated_at" },
                            { "data": "link" },

                            
                ],           
                
                "order": [
                    [1, "asc"]
                ] // set first column as a default sort by asc
            }
        });

        },500);
    }


    
});