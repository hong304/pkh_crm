'use strict';

function editGroup(GroupId)
{
	var scope = angular.element(document.getElementById("queryInfo")).scope();
    scope.$apply(function () {
    	scope.editGroup(GroupId);
    });
}

function delGroup(id)
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
                        scope.delGroup(id);
                    }
                }
            }
        });

    });
}

app.controller('commissiongroupMaintenanceCtrl', function($scope, $rootScope, $http, SharedService, $location, $timeout, $interval) {

	var querytarget = endpoint + '/querycommissiongroup.json';
	var iutarget = endpoint + '/manipulateGroup.json';
    var fetchDataDelay = 250;   // milliseconds
    var fetchDataTimer;


    $scope.filterData = {
			'name'	:	'',
        'status' : '100',
	};
    $scope.submit = true;
	$scope.GroupInfo_def = {
			'id'		:	'',
			'name'		:	'',
        'contact_1' : '',
        'contact_2' : '',
        'phone_1' : '',
        'phone_2' : '',
        'email' : '',
        'description' : '',
        'address' : ''
	};
	
	$scope.submitbtn = true;
	$scope.newId = "";
	
	$scope.GroupInfo = {};
	
    $scope.$on('$viewContentLoaded', function() {   
        Metronic.initAjax();
        $scope.systeminfo = $rootScope.systeminfo;
        $('#name').focus();
        $scope.updateDataSet();

    });

    $scope.$watch('filterData', function() {
        $timeout.cancel(fetchDataTimer);
        fetchDataTimer = $timeout(function () {
            $scope.updateDataSet();
        },fetchDataDelay);

    }, true);

    $scope.$watch(function() {
    	return $rootScope.systeminfo;
  	}, function() {
  		$scope.systeminfo = $rootScope.systeminfo;
        $scope.updateDataSet();
  	}, true);

    $scope.$watch('filterData.status', function() {
        $scope.updateDataSet();
    }, true);

    $scope.editGroup = function(GroupId)
    {
    	$scope.submitbtn = true;
    	$http.post(querytarget, {mode: "single", GroupId: GroupId})
    	.success(function(res, status, headers, config){    
    		$scope.GroupInfo = $.extend(true, {}, $scope.GroupInfo_def);
    		$scope.GroupInfo = res;



    		var statuscat = [];
    		statuscat = statuscat.concat([{value: 1, label: "正常"}]);
    		statuscat = statuscat.concat([{value: 2, label: "暫停"}]);
        	$scope.statuscat = statuscat;
        	
        	var pos = $scope.statuscat.map(function(e) {
				return e.value;
			  }).indexOf(parseInt(res.groupStatus));



        	$scope.GroupInfo.groupStatus = $scope.statuscat[pos];



    		$("#GroupFormModal").modal({backdrop: 'static'});

    	});
    	
    	
    }

    $scope.addGroup = function()
    {
		var statuscat = [];
		statuscat = statuscat.concat([{value: '1', label: "正常"}]);
		statuscat = statuscat.concat([{value: '2', label: "暫停"}]);
    	$scope.statuscat = statuscat;

    	//console.log($scope.statuscat );

    	$scope.submitbtn = true;
    	$scope.GroupInfo = $.extend(true, {}, $scope.GroupInfo_def);
    	$scope.GroupInfo.groupStatus = $scope.statuscat[0];



    	$("#GroupFormModal").modal({backdrop: 'static'});
    	
    }



    $scope.delGroup = function(id){
        $http({
            method: 'POST',
            url: iutarget,
            data: {mode:'del',Group_id:id}
        }).success(function () {
            $scope.del = true;
            $scope.updateDataSet();
        });
    }

    $scope.submitGroupForm = function()
    {

        if(!$scope.submit)
            alert('客户編號不能用');
        else if($scope.GroupInfo.name == "")
    	{
    		alert('請輸入所需資料');
    	}
    	else
    	{
    		$http.post(iutarget, {GroupInfo: $scope.GroupInfo})
        	.success(function(res, status, headers, config){    
        		    		
        		$scope.submitbtn = false;

        		if(res.mode == 'update')
        		{
        			$("#GroupFormModal").modal('hide');
        		}
        		else
        		{
        			$scope.newId = "編號: " + res.id;
        		}
        		
        		$scope.updateDataSet();
        		
        	});
    	}
    	
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
                    "data": {mode: "collection"},
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
                            { "data": "commissiongroupId" },
                            { "data": "commissiongroupName" },
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