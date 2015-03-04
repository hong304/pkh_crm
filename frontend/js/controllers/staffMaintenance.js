'use strict';

function editStaff(StaffId, RoleId)
{
	var scope = angular.element(document.getElementById("queryInfo")).scope();
    scope.$apply(function () {
    	scope.editStaff(StaffId);
    });
}

app.controller('staffMaintenanceCtrl', function($scope, $rootScope, $http, SharedService, $location, $timeout, $interval, $state) {
	
	var querytarget = endpoint + '/queryStaff.json';
	var iutarget = endpoint + '/manipulateStaff.json';
	
	$scope.info_def = {
			'id'		:	false,
			'username'	:	'',
			'password'	:	'',
			'name'	:	'',
			'disabled'	:	'0',
			'permission'	:	'',
			'group'	:	'',
	};
	
	$scope.info = {};
	$scope.zone = {};
	$scope.permission = {};
	
    $scope.$on('$viewContentLoaded', function() {   
    	
        Metronic.initAjax();        
        $scope.systeminfo = $rootScope.systeminfo;   
        $scope.updateDataSet();
        
        
    });
    
    $scope.$watch(function() {
    	return $rootScope.systeminfo;
  	}, function() {
  		$scope.systeminfo = $rootScope.systeminfo; 
  	}, true);
       
    $scope.editStaff = function(StaffId)
    {
    	/*
    	$scope.submitbtn = true;
    	$http.post(querytarget, {mode: "single", StaffId: StaffId, RoleId: RoleId})
    	.success(function(res, status, headers, config){    
    		$scope.info = $.extend(true, {}, $scope.info_def);
    		$scope.info = res.account;
    		$scope.permission = res.permission;
    		$scope.zone = res.zone;
    		
        	
    		$("#StaffFormModal").modal({backdrop: 'static'});
    		$('.date-picker').datepicker({
                rtl: Metronic.isRTL(),
                orientation: "left",
                autoclose: true
            });
    	});
    	*/
    	
    	// $state.go("userProfile", {userid: StaffId}, {reload: true});
    	$location.url("/user-maintenance/form?staffId=" + StaffId);
    	
    	
    }
    
    // ---- profile page
    $scope.loadStaffProfile = function()
    {
    	$scope.staffId = $location.search().staffId;
    	
    	$http.post(querytarget, {mode: "single", StaffId: $scope.staffId})
    	.success(function(res, status, headers, config){    
    		
    		
    		$scope.user = res.account;
    		$scope.availableroles = res.available_roles;
    		
    		var pos = $scope.availableroles.map(function(e) { 
				return e.id; 
			  }).indexOf(res.account.roles[0].id);
    		$scope.user.roles = $scope.availableroles[pos];
    		
    		$scope.zones = res.zones;
    		
    		$scope.loginrecords = res.loginrecords;
    		
    	});
    	
    	
    }
    $scope.saveProfile = function()
    {
    	$scope.staffId = $location.search().staffId;
    	
    	$http.post(iutarget, {StaffId: $scope.staffId, account: $scope.user, zone: $scope.zones})
    	.success(function(res, status, headers, config){    
    		alert('已更新用戶資料');
    		
    	});
    }
    
    // -- kick user
    $scope.logout = function(auditId)
    {
    	console.log(auditId);
    	$http.post(querytarget, {mode: "forcelogout", hash: auditId})
    	.success(function(res, status, headers, config){    
    		$("#forcebtn_" + auditId).css('display', 'none');
    		alert('已登出及解鎖');
    	});
    }
    
    
    $scope.addStaff = function()
    {
    	
    	$scope.submitbtn = true;
    	$scope.info = $.extend(true, {}, $scope.info_def);
    	
    	var groups = [];
    	groups = groups.concat([{value: '3', label: "Manager"}]);
    	groups = groups.concat([{value: '2', label: "System Administrator"}]);
    	groups = groups.concat([{value: '4', label: "Telesales"}]);
    	
    	$scope.groups = groups;
    	
    	var disabled = [];
    	disabled = groups.concat([{value: '0', label: "Normal"}]);
    	disabled = groups.concat([{value: '1', label: "Suspended"}]);
    	
    	$scope.disabled = disabled;
    	
    	$("#StaffFormModal").modal({backdrop: 'static'});
    	$('.date-picker').datepicker({
            rtl: Metronic.isRTL(),
            orientation: "left",
            autoclose: true
        });
    	
    }
    
    $scope.submitStaffForm = function()
    {
    	if(
    			$scope.info.username == "" ||
    			$scope.info.name == "" 
    	)
    	{
    		alert('請輸入所需資料');
    	}
    	else
    	{

    		$http.post(iutarget, {info: $scope.info, permission: $scope.permission, zone: $scope.zone})
        	.success(function(res, status, headers, config){    
        		$scope.submitbtn = false;

        		if(res.mode == 'error')
    			{
    			
    			}
        		else if(res.mode == 'update')
        		{
        			$("#StaffFormModal").modal('hide');
        			
        		}
        		else
        		{
        			$scope.editStaff(res.id, $scope.info.groups.value);
        		}
        		
        		$scope.updateDataSet();
        		
        	});
    	}
    	
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

                
                "bStateSave": true, // save datatable state(pagination, sort, etc) in cookie.

                "lengthMenu": [
                    [10, 20, 50],
                    [10, 20, 50] // change per page values here
                ],
                "pageLength": 10, // default record count per page
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
                            { "data": "username" },
                            { "data": "name" },
                            { "data": "role" },
                            { "data": "link" },
                            
                ],           
                
                "order": [
                    [1, "asc"]
                ] // set first column as a default sort by asc
            }
        });

    }
    
});