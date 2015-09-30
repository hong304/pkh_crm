/* Define the environment according to the windows setting */
//hi test
if(window.location.hostname == "dev-f.pingkeehong.com")
{
	var endpoint = '//dev-b.pingkeehong.com';
	var assets = '//dev-f.pingkeehong.com/assets';
}
else if(window.location.hostname == "frontend.pingkeehong.com")
{
	var endpoint = '//backend.pingkeehong.com/';
	var assets = '//frontend.pingkeehong.com/assets';
}else if(window.location.hostname == "pkh-f.sylam.net")
{
    var endpoint = '//pkh-b.sylam.net/';
    var assets = '//pkh-f.sylam.net/assets';
}else{
    var endpoint = '//b.pingkeehong.com/';
    var assets = '//f.pingkeehong.com/assets';
}

var appname = 'Web Application';
var companyname = 'PING KEE';

/* Start Angularjs Environment */
var app = angular.module("app", [
    "ui.router", 
    "ui.bootstrap", 
    "oc.lazyLoad",  
    "ngSanitize",
    'angular-loading-bar',
]); 


app.config(['$httpProvider', function($httpProvider) {
	  $httpProvider.defaults.withCredentials = true;
}]) 
	
/* Configure ocLazyLoader(refer: https://github.com/ocombe/ocLazyLoad) */
app.config(['$ocLazyLoadProvider', function($ocLazyLoadProvider) {
    $ocLazyLoadProvider.config({
        cssFilesInsertBefore: 'ng_load_plugins_before' // load the above css files before a LINK element with this ID. Dynamic CSS files must be loaded between core and theme css files
    });
}]);

/* Setup global settings */
app.factory('settings', ['$rootScope', function($rootScope) {
    // supported languages
    var settings = {
        layout: {
            pageAutoScrollOnLoad: 1000 // auto scroll to top on page load
        },
        layoutImgPath: Metronic.getAssetsPath() + 'admin/layout/img/',
        layoutCssPath: Metronic.getAssetsPath() + 'admin/layout/css/'
    };

    $rootScope.settings = settings;
    $rootScope.endpoint = endpoint;
    $rootScope.appname = appname;
    $rootScope.companyname = companyname;

    return settings;
}]);



app.factory('httpPreConfig', ['$http', '$rootScope', function($http, $rootScope) {
    $http.defaults.transformRequest.push(function (data) {
        return data;
    });
    $http.defaults.transformResponse.push(function(data){ 
        $rootScope.$broadcast('httpCallStopped');
        return data;
    })
    return $http;
}]);

/* Setup App Main Controller */
app.controller('AppController', ['$scope', '$rootScope', '$http', '$interval', 'SharedService', '$timeout', function($scope, $rootScope, $http, $interval, SharedService, $timeout) {



	// get system configuration from cloud
	$http.get($scope.endpoint + '/system.json').success(function(data) {

          $scope.systemInfo = data;

       var text = '';
        $.each(data.broadcastMessage, function(index, value) {
            text += value.content + "<br>";
        });

        if(text != ''){
            bootbox.dialog({
                title: "重要事項",
                message: text,
                closeButton:false,
                onEscape:false,
                buttons: {
                    danger: {
                        label: "確定",
                        className: "red",
                        callback: function() {

                            $http.post(endpoint + '/updateBroadcast.json',{mode:'generalMsg',bid:data.broadcastMessage}).success(function(res, status, headers, config) {

                            }).error(function(res, status, headers, config) {

                            });
                        }
                    }
                }
            });
        }

      if(!data.user.loggedin)
        {
            var msg = '';
            $http.get($scope.endpoint + '/getOweInvoices.json')
                .success(function(res){
                    $.each(res, function(index, value) {
                        var owe = value.total.owe;
                        msg += '車號:'+index+' 欠單總數:'+value.total.invoices+' 欠單總額:$'+owe.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ",")+'<br/>';
                    });


                    bootbox.dialog({
                        title: "欠單詳情",
                        message: msg,
                        closeButton:false,
                        onEscape:false,
                        buttons: {
                            danger: {
                                label: "確定",
                                className: "red",
                                callback: function() {

                                    $http.post(endpoint + '/updateBroadcast.json',{mode:'oweInvoices'}).success(function(res, status, headers, config) {

                                    }).error(function(res, status, headers, config) {

                                    });
                                }
                            }
                        }
                    });

                });

        }


        $rootScope.systeminfo = data;
        $timeout(function(){
        	SharedService.setValue('SystemInfo', $scope.systemInfo, 'UpdateSystemInfo');
        	
        }, 500);
        
    }).error(function(data, status, headers, config) {
            window.location.href = $scope.endpoint + '/credential/auth';
        });

    $scope.$on('$viewContentLoaded', function() {
        Metronic.initComponents(); // init core components



        //Layout.init(); //  Init entire layout(header, footer, sidebar, etc) on page load if the partials included in server side instead of loading with ng-include directive 
    });
}]);


app.factory('SharedService', function($rootScope){
	var sharedService = [];
	
	sharedService.setValue = function(name, value, broadcastItem)
	{		
		sharedService[name] = value;
		$rootScope.$broadcast(broadcastItem);
	}	
	
	
	 
	return sharedService;
});

/***
Layout Partials.
By default the partials are loaded through AngularJS ng-include directive. In case they loaded in server side(e.g: PHP include function) then below partial 
initialization can be disabled and Layout.init() should be called on page load complete as explained above.
***/

/* Setup Layout Part - Header */
app.controller('HeaderController', ['$scope', 'SharedService', '$interval', '$http', '$timeout', '$location', function($scope, SharedService, $interval, $http, $timeout, $location) {
	
	$interval(function(){
		Metronic.unblockUI();
	}, 3000)
	$scope.$on('UpdateSystemInfo', function(){
		
		$scope.systemInfo = SharedService.SystemInfo;
		
		$scope.endpoint = endpoint;
		
        $scope.getNotification();
       $interval($scope.getNotification, 30000);
       // $interval($scope.broadCastNotification, 500);
        
	});
	
	// ---------------------------------------------------------------------------------------------
	// For Switching zone
	// ---------------------------------------------------------------------------------------------
	$scope.switchZone = function()
	{
		// display modal
		$("#switchZoneModal").modal('toggle'); 
	}
	
	$scope.switchToZone = function(id, name)
	{
		$scope.systemInfo.currentzone = name;
		
		var target = endpoint + '/selectZone';
    	
    	$http.post(target, {zoneId: id})
    	.success(function(data, status, headers, config){  
    		window.location.reload();
    		//SharedService.setValue('ZoneChanged', true, 'ZoneChanged');
    		
    	});
		
		$("#switchZoneModal").modal('toggle');
	}
	
	// ---------------------------------------------------------------------------------------------
	// For Manager Approval Action Start
	// ---------------------------------------------------------------------------------------------
	
    $scope.$on('$includeContentLoaded', function() {
        Layout.initHeader(); // init header      
    });
    
    $scope.getNotification = function() {
    	var notificationJson = $scope.endpoint + "/getNotification.json";
    	//console.log('getNotification');
    	$http.get(notificationJson).success(function(data) {
    		
    		// --------------------- handle pending approval order
    		$scope.notification = data;

                     if(parseInt(data.logintime) != parseInt(data.db_logintime)){
               alert('你的帳戶已從新的瀏覽器或裝置登入。請立即檢查此登入')
               window.location.href = $scope.endpoint + '/logout?mode=manual';
            }

            $timeout(function(){
            	$(".slimScrollDiv").css('height', '');
            });
        });
    }
    
    
    $scope.broadCastNotification = function()
    {
    	SharedService.setValue('pendingApprovalOrder', $scope.receivedData, 'updateOfPendingApprovalOrder');
    }
    
    $scope.triggerApprovalNotification = function()
    {
    	$("#approval_notification_btn").trigger('click');
    }
    
    $scope.triggerRejectedNotification = function()
    {
    	$("#rejected_notification_btn").trigger('click');
    }
    
	// ---------------------------------------------------------------------------------------------
	// For Manager Approval Action End
	// ---------------------------------------------------------------------------------------------
    
    $scope.updateStatusFindReport = function(statusId)
    {

    	$("#updateStatusFindReport").modal({backdrop: 'static'});

    }
    
    $scope.updateStatusNextStep = function(reportId)
    {
    	
    	if(typeof reportId == "undefined" || reportId == "")
    	{
    		
    	}
    	else
    	{
    		$("#updateStatusFindReport").modal('hide');
    		$scope.reportId = "";
    		$location.url('/invoiceStatusManager?rid=' + reportId);
    	}
    }
    
}]);

/* Setup Layout Part - Sidebar */
app.controller('PageHeadController', ['$scope', function($scope) {
    $scope.$on('$includeContentLoaded', function() {        
        //Demo.init(); // init theme panel
    });
}]);

/* Setup Layout Part - Footer */
app.controller('FooterController', ['$scope', function($scope) {
    $scope.$on('$includeContentLoaded', function() {
        Layout.initFooter(); // init footer
    });
}]);


/* Setup Rounting For All Pages */
app.config(['$stateProvider', '$urlRouterProvider', function($stateProvider, $urlRouterProvider) {

    // Redirect any unmatched url
    $urlRouterProvider.otherwise("/salesPanel");

    $stateProvider

        // Dashboard
    .state('salesPanel', {
        url: "/salesPanel",
        templateUrl: "views/dashboard.html",            
        data: {pageTitle: '下單平台', pageSubTitle: ''},
        controller: "DashboardController",
        resolve: {
            deps: ['$ocLazyLoad', function($ocLazyLoad) {
                return $ocLazyLoad.load({
                    name: 'app',
                    insertBefore: '#ng_load_plugins_before', // load the above css files before '#ng_load_plugins_before'
                    files: [

                        assets + '/global/plugins/bootstrap-datepicker/css/datepicker3.css',
                        assets + '/global/plugins/bootstrap-datepicker/js/bootstrap-datepicker.js',

                        'js/controllers/DashboardController.js',
                    ] 
                });
            }]
        }
    })


        .state('permissionController', {
            url: "/permission_control",
            templateUrl: "views/permission_control.html",
            data: {pageTitle: '下單平台', pageSubTitle: ''},
            controller: "permissionController",
            resolve: {
                deps: ['$ocLazyLoad', function($ocLazyLoad) {
                    return $ocLazyLoad.load({
                        name: 'app',
                        insertBefore: '#ng_load_plugins_before', // load the above css files before '#ng_load_plugins_before'
                        files: [

                            assets + '/global/plugins/bootstrap-datepicker/css/datepicker3.css',
                            assets + '/global/plugins/bootstrap-datepicker/js/bootstrap-datepicker.js',

                            'js/controllers/permissionController.js',
                        ]
                    });
                }]
            }
        })
        
    .state('newOrder', {
        url: "/newOrder/:action/:instatus/:invoiceNumber",
        templateUrl: "views/orderForm.html",            
        data: {pageTitle: '下單平台', pageSubTitle: ''},

        controller: "controlOrderController",
        resolve: {
            deps: ['$ocLazyLoad', function($ocLazyLoad) {
                return $ocLazyLoad.load({
                    name: 'app',
                    insertBefore: '#ng_load_plugins_before', // load the above css files before '#ng_load_plugins_before'
                    files: [
                        assets + '/global/plugins/bootstrap-datepicker/css/datepicker3.css',
                        assets + '/global/plugins/bootstrap-datepicker/js/bootstrap-datepicker.js',
                        assets + '/global/plugins/fuelux/js/spinner.min.js',
                        assets + '/dependencies/jquery.cookie.min.js',
                        assets + '/global/plugins/jquery-inputmask/jquery.inputmask.bundle.min.js',
                        assets + '/global/plugins/bootstrap-maxlength/bootstrap-maxlength.min.js',
                        assets + '/global/plugins/bootbox/bootbox.min.js',

                        'js/controllers/controlOrderController.js',
                        'js/controllers/selectClientCtrl.js',
                        'js/controllers/selectProductCtrl.js',
                    ] 
                });
            }]
        }
    }) 
    
    .state('editOrder', {
        url: "/editOrder", 
        templateUrl: "views/orderForm.html",            
        data: {pageTitle: '下單平台', pageSubTitle: ''},
        controller: "controlOrderController",
        resolve: {  
            deps: ['$ocLazyLoad', function($ocLazyLoad) {
                return $ocLazyLoad.load({
                    name: 'app',
                    insertBefore: '#ng_load_plugins_before', // load the above css files before '#ng_load_plugins_before'
                    files: [
                        assets + '/global/plugins/bootstrap-datepicker/css/datepicker3.css',
                        assets + '/global/plugins/bootstrap-datepicker/js/bootstrap-datepicker.js',
                        assets + '/global/plugins/fuelux/js/spinner.min.js',
                        assets + '/global/plugins/jquery-inputmask/jquery.inputmask.bundle.min.js',
                        assets + '/global/plugins/bootstrap-maxlength/bootstrap-maxlength.min.js',
                        assets + '/global/plugins/bootbox/bootbox.min.js',

                        'js/controllers/controlOrderController.js',
                        'js/controllers/selectClientCtrl.js',
                        'js/controllers/selectProductCtrl.js',
                    ] 
                });
            }]
        }
    })
    
    .state('viewPendingApprovalOrder', {
        url: "/viewPendingApprovalOrder",
        templateUrl: "views/viewpendingorder.html",            
        data: {pageTitle: '檢視批核訂單資料', pageSubTitle: ''},
        controller: "pendingOrderController",
        resolve: {
            deps: ['$ocLazyLoad', function($ocLazyLoad) {
                return $ocLazyLoad.load({
                    name: 'app',
                    insertBefore: '#ng_load_plugins_before', // load the above css files before '#ng_load_plugins_before'
                    files: [
                        
                        assets + '/global/plugins/select2/select2.css',
						assets + '/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.css', 
						assets + '/global/plugins/datatables/extensions/Scroller/css/dataTables.scroller.min.css',
						assets + '/global/plugins/datatables/extensions/ColReorder/css/dataTables.colReorder.min.css',
                        
						assets + '/global/plugins/select2/select2.min.js',
                        assets + '/global/plugins/datatables/all.min.js',
                        
                        'js/controllers/pendingOrderController.js',
                    ] 
                });
            }]
        }
    })
    
    .state('searchInvoices', {
        url: "/searchInvoices",
        templateUrl: "views/searchInvoices.html",            
        data: {pageTitle: '檢視訂單資料', pageSubTitle: ''},
        controller: "searchInvoicesCtrl",
        resolve: {
            deps: ['$ocLazyLoad', function($ocLazyLoad) {
                return $ocLazyLoad.load({
                    name: 'app',
                    insertBefore: '#ng_load_plugins_before', // load the above css files before '#ng_load_plugins_before'
                    files: [
                        
                        assets + '/global/plugins/select2/select2.css',
						assets + '/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.css', 
						assets + '/global/plugins/datatables/extensions/Scroller/css/dataTables.scroller.min.css',
						assets + '/global/plugins/datatables/extensions/ColReorder/css/dataTables.colReorder.min.css',
                        
						assets + '/global/plugins/select2/select2.min.js',
                        assets + '/global/plugins/datatables/all.min.js',
                        
                        'js/controllers/searchInvoicesCtrl.js',
                    ] 
                });
            }]
        }
    })
.state('searchPo', {		
        url: "/searchPo",		
        templateUrl: "views/searchPo.html",            		
        data: {pageTitle: '檢視訂單資料', pageSubTitle: ''},		
        controller: "searchPoCtrl",		
        resolve: {		
            deps: ['$ocLazyLoad', function($ocLazyLoad) {		
                return $ocLazyLoad.load({		
                    name: 'app',		
                    insertBefore: '#ng_load_plugins_before', // load the above css files before '#ng_load_plugins_before'		
                    files: [		
                        		
                      assets + '/global/plugins/bootstrap-datepicker/css/datepicker3.css',		
                        assets + '/global/plugins/bootstrap-datepicker/js/bootstrap-datepicker.js',		
        assets + '/css/dataTable/bootstrap.min.css',		
                        assets + '/css/dataTable/dataTables.bootstrap.css',		
                        assets + '/js/dataTable/jquery.dataTables.min.js',		
                        assets + '/js/dataTable/dataTables.bootstrap.js',		
                        		
                        'js/controllers/searchPoCtrl.js',		
                         'js/controllers/selectSupplierControl.js',		
                         'js/controllers/selectProductCtrl.js',		
                        		
                    ] 		
                });		
            }]		
        }		
    })        
    .state('generatePickingList', {
        url: "/pickingList",
        templateUrl: "views/pickingList.html",            
        data: {pageTitle: '檢視訂單資料', pageSubTitle: ''},
        controller: "pickingListCtrl",
        resolve: {
            deps: ['$ocLazyLoad', function($ocLazyLoad) {
                return $ocLazyLoad.load({
                    name: 'app',
                    insertBefore: '#ng_load_plugins_before', // load the above css files before '#ng_load_plugins_before'
                    files: [
						assets + '/global/plugins/bootstrap-datepicker/css/datepicker3.css',
						assets + '/global/plugins/bootstrap-datepicker/js/bootstrap-datepicker.js',
                        assets + '/global/plugins/select2/select2.css',
						assets + '/global/plugins/datatables/plugins/bootstrap/dataTables.bootstrap.css', 
						assets + '/global/plugins/datatables/extensions/Scroller/css/dataTables.scroller.min.css',
						assets + '/global/plugins/datatables/extensions/ColReorder/css/dataTables.colReorder.min.css',
                        
						assets + '/global/plugins/select2/select2.min.js',
                        assets + '/global/plugins/datatables/all.min.js',
                        
                        'js/controllers/pickingListCtrl.js',
                    ] 
                });
            }]
        }
    })
    
    // -- invoice maintenance
    // - search invoice
    .state('queryInvoice', {
        url: "/queryInvoice",
        templateUrl: "views/queryInvoice.html",            
        data: {pageTitle: '訂單檢索系統', pageSubTitle: ''},
        controller: "queryInvoiceCtrl",
        resolve: {
            deps: ['$ocLazyLoad', function($ocLazyLoad) {
                return $ocLazyLoad.load({
                    name: 'app',
                    insertBefore: '#ng_load_plugins_before', // load the above css files before '#ng_load_plugins_before'
                    files: [
												
						assets + '/global/plugins/datatables/all.min.js',
						assets + '/global/scripts/datatable.js',
						
						assets + '/global/plugins/bootbox/bootbox.min.js',

                        assets + '/global/plugins/bootstrap-datepicker/css/datepicker3.css',
                        assets + '/global/plugins/bootstrap-datepicker/js/bootstrap-datepicker.js',
						
                        'js/controllers/queryInvoice.js',
                        'js/controllers/selectClientCtrl.js',
                    ] 
                });
            }]
        }
    })

        .state('pendingInvoice', {
            url: "/pendingInvoice",
            templateUrl: "views/queryInvoice.html",
            data: {pageTitle: '訂單檢索系統', pageSubTitle: ''},
            controller: "queryInvoiceCtrl",
            resolve: {
                deps: ['$ocLazyLoad', function($ocLazyLoad) {
                    return $ocLazyLoad.load({
                        name: 'app',
                        insertBefore: '#ng_load_plugins_before', // load the above css files before '#ng_load_plugins_before'
                        files: [

                            assets + '/global/plugins/datatables/all.min.js',
                            assets + '/global/scripts/datatable.js',

                            assets + '/global/plugins/bootbox/bootbox.min.js',

                            assets + '/global/plugins/bootstrap-datepicker/css/datepicker3.css',
                            assets + '/global/plugins/bootstrap-datepicker/js/bootstrap-datepicker.js',

                            'js/controllers/queryInvoice.js',
                            'js/controllers/selectClientCtrl.js',
                        ]
                    });
                }]
            }
        })

        .state('queryProduct', {
            url: "/queryProduct",
            templateUrl: "views/queryProduct.html",
            data: {pageTitle: '產品檢索系統', pageSubTitle: ''},
            controller: "queryProductCtrl",
            resolve: {
                deps: ['$ocLazyLoad', function($ocLazyLoad) {
                    return $ocLazyLoad.load({
                        name: 'app',
                        insertBefore: '#ng_load_plugins_before', // load the above css files before '#ng_load_plugins_before'
                        files: [

                            assets + '/global/plugins/datatables/all.min.js',
                            assets + '/global/scripts/datatable.js',

                            assets + '/global/plugins/bootstrap-datepicker/css/datepicker3.css',
                            assets + '/global/plugins/bootstrap-datepicker/js/bootstrap-datepicker.js',

                            'js/controllers/queryProductCtrl.js',

                        ]
                    });
                }]
            }
        })


        .state('queryCommission', {
            url: "/queryCommission",
            templateUrl: "views/queryCommission.html",
            data: {pageTitle: '產品檢索系統', pageSubTitle: ''},
            controller: "queryCommissionCtrl",
            resolve: {
                deps: ['$ocLazyLoad', function($ocLazyLoad) {
                    return $ocLazyLoad.load({
                        name: 'app',
                        insertBefore: '#ng_load_plugins_before', // load the above css files before '#ng_load_plugins_before'
                        files: [

                            assets + '/global/plugins/datatables/all.min.js',
                            assets + '/global/scripts/datatable.js',

                            assets + '/global/plugins/bootstrap-datepicker/css/datepicker3.css',
                            assets + '/global/plugins/bootstrap-datepicker/js/bootstrap-datepicker.js',

                            'js/controllers/queryCommissionCtrl.js',

                        ]
                    });
                }]
            }
        })

    // - update invoice via barcode
    .state('updateStatusViaReportId', {
        url: "/updateStatusViaReportId",
        templateUrl: "views/updateStatusViaReportId.html",            
        data: {pageTitle: '訂單檢索系統', pageSubTitle: ''},
        controller: "queryInvoiceCtrl",
        resolve: {
            deps: ['$ocLazyLoad', function($ocLazyLoad) {
                return $ocLazyLoad.load({
                    name: 'app',
                    insertBefore: '#ng_load_plugins_before', // load the above css files before '#ng_load_plugins_before'
                    files: [
                        'js/controllers/updateStatusViaReportId.js',
                    ] 
                });
            }]
        }
    })
    
    // - invoice status manager
    .state('invoiceStatusManager', {
        url: "/invoiceStatusManager",
        templateUrl: "views/invoiceStatusManager.html",            
        data: {pageTitle: '訂單流程管理系統', pageSubTitle: ''},
        controller: "invoiceFlowCtrl",
        resolve: {
            deps: ['$ocLazyLoad', function($ocLazyLoad) {
                return $ocLazyLoad.load({
                    name: 'app',
                    insertBefore: '#ng_load_plugins_before', // load the above css files before '#ng_load_plugins_before'
                    files: [
                        assets + '/global/plugins/icheck/skins/all.css',
                        assets + '/global/plugins/icheck/icheck.min.js',
                        assets + '/global/plugins/bootbox/bootbox.min.js',

                        'js/controllers/invoiceFlowCtrl.js',
                    ] 
                });
            }]
        }
    })
    
    // -- report factory
    .state('reportSelection', {
        url: "/reportSelection",
        templateUrl: "views/reportSelection.html",            
        data: {pageTitle: '選擇報告', pageSubTitle: ''},
        controller: "reportSelectionCtrl",
        resolve: {
            deps: ['$ocLazyLoad', function($ocLazyLoad) {
                return $ocLazyLoad.load({
                    name: 'app',
                    insertBefore: '#ng_load_plugins_before', // load the above css files before '#ng_load_plugins_before'
                    files: [
                        'js/controllers/reportSelectionCtrl.js',
                    ] 
                });
            }]
        }
    })
    
    .state('reportFactory', {
        url: "/reportFactory",
        templateUrl: "views/reportFactory.html",            
        data: {pageTitle: '報告系統', pageSubTitle: ''},
        controller: "reportFactoryCtrl",
        resolve: {
            deps: ['$ocLazyLoad', function($ocLazyLoad) {
                return $ocLazyLoad.load({
                    name: 'app',
                    insertBefore: '#ng_load_plugins_before', // load the above css files before '#ng_load_plugins_before'
                    files: [
                        'js/controllers/reportFactoryCtrl.js',
                        assets + '/global/plugins/bootbox/bootbox.min.js', 
						assets + '/global/plugins/bootstrap-datepicker/css/datepicker3.css',
						assets + '/global/plugins/bootstrap-datepicker/js/bootstrap-datepicker.js',
                    ] 
                });
            }]
        }
    })


        .state('reportvansell', {
            url: "/reportvansell",
            templateUrl: "views/reportvansell.html",
            data: {pageTitle: '預載單', pageSubTitle: ''},
            controller: "reportvansellCtrl",
            resolve: {
                deps: ['$ocLazyLoad', function($ocLazyLoad) {
                    return $ocLazyLoad.load({
                        name: 'app',
                        insertBefore: '#ng_load_plugins_before', // load the above css files before '#ng_load_plugins_before'
                        files: [
                            'js/controllers/reportvansellCtrl.js',
                            assets + '/global/plugins/bootbox/bootbox.min.js',
                            assets + '/global/plugins/bootstrap-datepicker/css/datepicker3.css',
                            assets + '/global/plugins/bootstrap-datepicker/js/bootstrap-datepicker.js',
                        ]
                    });
                }]
            }
        })

        .state('reportPrintlog', {
            url: "/reportPrintlog",
            templateUrl: "views/reportPrintlog.html",
            data: {pageTitle: '列印記錄', pageSubTitle: ''},
            controller: "reportPrintlogCtrl",
            resolve: {
                deps: ['$ocLazyLoad', function($ocLazyLoad) {
                    return $ocLazyLoad.load({
                        name: 'app',
                        insertBefore: '#ng_load_plugins_before', // load the above css files before '#ng_load_plugins_before'
                        files: [
                            'js/controllers/reportPrintlogCtrl.js',
                             assets + '/global/plugins/bootbox/bootbox.min.js',

                            assets + '/global/plugins/datatables/all.min.js',
                            assets + '/global/scripts/datatable.js',

                            assets + '/global/plugins/bootstrap-datepicker/css/datepicker3.css',
                            assets + '/global/plugins/bootstrap-datepicker/js/bootstrap-datepicker.js',
                        ]
                    });
                }]
            }
        })

        .state('dailyReport', {
            url: "/dailyReport",
            templateUrl: "views/dailyReport.html",
            data: {pageTitle: '每日成本', pageSubTitle: ''},
            controller: "dailyReport",
            resolve: {
                deps: ['$ocLazyLoad', function($ocLazyLoad) {
                    return $ocLazyLoad.load({
                        name: 'app',
                        insertBefore: '#ng_load_plugins_before', // load the above css files before '#ng_load_plugins_before'
                        files: [
                            'js/controllers/dailyReport.js',

                            assets + '/css/dataTable/bootstrap.min.css',
                            assets + '/css/dataTable/dataTables.bootstrap.css',
                            assets + '/js/dataTable/jquery.dataTables.min.js',
                            assets + '/js/dataTable/dataTables.bootstrap.js',

                            assets + '/global/plugins/bootstrap-datepicker/css/datepicker3.css',
                            assets + '/global/plugins/bootstrap-datepicker/js/bootstrap-datepicker.js',
                        ]
                    });
                }]
            }
        })

    // -- push to print function
    .state('pushToPrint', {
        url: "/push-to-print",
        templateUrl: "views/pushToPrint.html",            
        data: {pageTitle: '訂單列印', pageSubTitle: ''},
        controller: "pushToPrintCtrl",
        resolve: {
            deps: ['$ocLazyLoad', function($ocLazyLoad) {
                return $ocLazyLoad.load({
                    name: 'app',
                    insertBefore: '#ng_load_plugins_before', // load the above css files before '#ng_load_plugins_before'
                    files: [
                        assets + '/global/plugins/bootbox/bootbox.min.js',
                        'js/controllers/pushToPrintCtrl.js',
                    ] 
                });
            }]
        }
    })
    
    
    .state('customerMaintenance', {
        url: "/customerMaintenance",
        templateUrl: "views/customerListing.html",            
        data: {pageTitle: '客戶設定', pageSubTitle: ''},
        controller: "customerMaintenanceCtrl",
        resolve: {
            deps: ['$ocLazyLoad', function($ocLazyLoad) {
                return $ocLazyLoad.load({
                    name: 'app',
                    insertBefore: '#ng_load_plugins_before', // load the above css files before '#ng_load_plugins_before'
                    files: [
                        assets + '/css/dataTable/style.css',
						assets + '/global/plugins/datatables/all.min.js',
						assets + '/global/scripts/datatable.js',
                        assets + '/global/plugins/bootbox/bootbox.min.js',
                        assets + '/global/plugins/jquery-inputmask/jquery.inputmask.bundle.min.js',

                        'js/controllers/customerMaintenanceCtrl.js',
                        'js/controllers/selectGroupCtrl.js',
                    ] 
                });
            }]
        }
    })

        .state('groupMaintenance', {
            url: "/groupMaintenance",
            templateUrl: "views/groupListing.html",
            data: {pageTitle: '集團設定', pageSubTitle: ''},
            controller: "groupMaintenanceCtrl",
            resolve: {
                deps: ['$ocLazyLoad', function($ocLazyLoad) {
                    return $ocLazyLoad.load({
                        name: 'app',
                        insertBefore: '#ng_load_plugins_before', // load the above css files before '#ng_load_plugins_before'
                        files: [
                            assets + '/global/plugins/datatables/all.min.js',
                            assets + '/global/scripts/datatable.js',
                            assets + '/global/plugins/bootbox/bootbox.min.js',
                            'js/controllers/groupMaintenanceCtrl.js',
                        ]
                    });
                }]
            }
        })

    .state('productMaintenance', {
        url: "/productMaintenance",
        templateUrl: "views/productListing.html",            
        data: {pageTitle: '產品設定', pageSubTitle: ''},
        controller: "productMaintenanceCtrl",
        resolve: {
            deps: ['$ocLazyLoad', function($ocLazyLoad) {
                return $ocLazyLoad.load({
                    name: 'app',
                    insertBefore: '#ng_load_plugins_before', // load the above css files before '#ng_load_plugins_before'
                    files: [
						assets + '/global/plugins/datatables/all.min.js',
						assets + '/global/scripts/datatable.js',
                        assets + '/global/plugins/bootbox/bootbox.min.js',

                        'js/controllers/productMaintenanceCtrl.js',
                    ] 
                });
            }]
        } 
    })
    
    .state('productDepartment', {
        url: "/productDepartmentMaintenance",
        templateUrl: "views/productDepartmentListing.html",            
        data: {pageTitle: '產品類別設定', pageSubTitle: ''},
        controller: "productDepartment",
        resolve: {
            deps: ['$ocLazyLoad', function($ocLazyLoad) {
                return $ocLazyLoad.load({
                    name: 'app',
                    insertBefore: '#ng_load_plugins_before', // load the above css files before '#ng_load_plugins_before'
                    files: [
						assets + '/global/plugins/datatables/all.min.js',
						assets + '/global/scripts/datatable.js',

                        'js/controllers/productDepartment.js',
                    ] 
                });
            }]
        }
    })
    
    
    .state('invoicePrintMaintenance', {
        url: "/invoicePrintMaintenance",
        templateUrl: "views/ipfListing.html",            
        data: {pageTitle: '訂單設定', pageSubTitle: ''},
        controller: "invoicePrintMaintenanceCtrl",
        resolve: {
            deps: ['$ocLazyLoad', function($ocLazyLoad) {
                return $ocLazyLoad.load({
                    name: 'app',
                    insertBefore: '#ng_load_plugins_before', // load the above css files before '#ng_load_plugins_before'
                    files: [
						//assets + '/global/plugins/datatables/all.min.js',
					//	assets + '/global/scripts/datatable.js',
						assets + '/global/plugins/bootstrap-datepicker/css/datepicker3.css',
						assets + '/global/plugins/bootstrap-datepicker/js/bootstrap-datepicker.js',
                        assets + '/css/dataTable/bootstrap.min.css',
                        assets + '/css/dataTable/dataTables.bootstrap.css',
                        assets + '/js/dataTable/dataTables.bootstrap.js',
                        assets + '/js/dataTable/jquery.dataTables.min.js',


                        'js/controllers/invoicePrintMaintenanceCtrl.js',
                    ] 
                });
            }]
        }
    })
    
    // User Maintenance Part
    // Listing:
    .state('userListing', {
        url: "/user-maintenance/listing",
        templateUrl: "views/staffListing.html",            
        data: {pageTitle: '用戶設定', pageSubTitle: ''},
        controller: "staffMaintenanceCtrl",
        resolve: {
            deps: ['$ocLazyLoad', function($ocLazyLoad) {
                return $ocLazyLoad.load({
                    name: 'app',
                    insertBefore: '#ng_load_plugins_before', // load the above css files before '#ng_load_plugins_before'
                    files: [
						//assets + '/global/plugins/datatables/all.min.js',
						//assets + '/global/scripts/datatable.js',

                        assets + '/css/dataTable/bootstrap.min.css',
                        assets + '/css/dataTable/dataTables.bootstrap.css',
                        assets + '/js/dataTable/jquery.dataTables.min.js',
                        assets + '/js/dataTable/dataTables.bootstrap.js',
						assets + '/global/plugins/bootstrap-datepicker/css/datepicker3.css',
						assets + '/global/plugins/bootstrap-datepicker/js/bootstrap-datepicker.js',
                        assets + '/global/plugins/bootbox/bootbox.min.js',

                        'js/controllers/staffMaintenance.js',
                    ] 
                });
            }]
        }
    })
    // Profile:
    .state('userProfile', {
        url: "/user-maintenance/form",
        templateUrl: "views/staffForm_complete.html",            
        data: {pageTitle: '用戶設定', pageSubTitle: ''},
        controller: "staffMaintenanceCtrl",
        
        resolve: {
            deps: ['$ocLazyLoad', function($ocLazyLoad) {
                return $ocLazyLoad.load({
                    name: 'app',
                    insertBefore: '#ng_load_plugins_before', // load the above css files before '#ng_load_plugins_before'
                    files: [
						assets + '/global/plugins/datatables/all.min.js',
						assets + '/global/scripts/datatable.js',
						assets + '/global/plugins/bootstrap-datepicker/css/datepicker3.css',
						assets + '/global/plugins/bootstrap-datepicker/js/bootstrap-datepicker.js',
                        'js/controllers/staffMaintenance.js',
                    ] 
                });
            }]
        }
    })

        // Finance:
        .state('newCheque', {
            url: "/finance-newCheque",
            templateUrl: "views/cheque_form.html",
            data: {pageTitle: '支票入帳', pageSubTitle: ''},
            controller: "financeController",

            resolve: {
                deps: ['$ocLazyLoad', function($ocLazyLoad) {
                    return $ocLazyLoad.load({
                        name: 'app',
                        insertBefore: '#ng_load_plugins_before', // load the above css files before '#ng_load_plugins_before'
                        files: [
                            assets + '/global/plugins/bootstrap-datepicker/css/datepicker3.css',
                            assets + '/global/plugins/bootstrap-datepicker/js/bootstrap-datepicker.js',
                            assets + '/global/plugins/fuelux/js/spinner.min.js',
                            assets + '/dependencies/jquery.cookie.min.js',
                            assets + '/global/plugins/jquery-inputmask/jquery.inputmask.bundle.min.js',
                            assets + '/global/plugins/bootstrap-maxlength/bootstrap-maxlength.min.js',

                            assets + '/global/plugins/datatables/all.min.js',
                            assets + '/global/scripts/datatable.js',

                            'js/controllers/financeController.js',
                            'js/controllers/selectClientCtrl.js',
                            'js/controllers/selectGroupCtrl.js',
                        ]
                    });
                }]
            }
        })

        .state('financeCashGetClearance', {
            url: "/financeCashGetClearance",
            templateUrl: "views/financeCashGetClearance.html",
            data: {pageTitle: '支票入帳(現金客)', pageSubTitle: ''},
            controller: "financeCashGetClearanceController",

            resolve: {
                deps: ['$ocLazyLoad', function($ocLazyLoad) {
                    return $ocLazyLoad.load({
                        name: 'app',
                        insertBefore: '#ng_load_plugins_before', // load the above css files before '#ng_load_plugins_before'
                        files: [
                            assets + '/global/plugins/bootstrap-datepicker/css/datepicker3.css',
                            assets + '/global/plugins/bootstrap-datepicker/js/bootstrap-datepicker.js',
                            assets + '/global/plugins/fuelux/js/spinner.min.js',
                            assets + '/dependencies/jquery.cookie.min.js',
                            assets + '/global/plugins/jquery-inputmask/jquery.inputmask.bundle.min.js',
                            assets + '/global/plugins/bootstrap-maxlength/bootstrap-maxlength.min.js',

                            assets + '/global/plugins/datatables/all.min.js',
                            assets + '/global/scripts/datatable.js',

                            'js/controllers/financeCashGetClearanceController.js',
                            'js/controllers/selectClientCtrl.js',
                            'js/controllers/selectGroupCtrl.js',
                        ]
                    });
                }]
            }
        })

        .state('clientClearance', {
            url: "/finance-clientClearance",
            templateUrl: "views/payment_clientClearance.html",
            data: {pageTitle: '財務相關', pageSubTitle: ''},
            controller: "financeController",

            resolve: {
                deps: ['$ocLazyLoad', function($ocLazyLoad) {
                    return $ocLazyLoad.load({
                        name: 'app',
                        insertBefore: '#ng_load_plugins_before', // load the above css files before '#ng_load_plugins_before'
                        files: [
                            assets + '/global/plugins/bootstrap-datepicker/css/datepicker3.css',
                            assets + '/global/plugins/bootstrap-datepicker/js/bootstrap-datepicker.js',

                            assets + '/global/plugins/datatables/all.min.js',
                            assets + '/global/scripts/datatable.js',

                            assets + '/global/plugins/bootstrap-maxlength/bootstrap-maxlength.min.js',

                            'js/controllers/financeController.js',

                        ]
                    });
                }]
            }
        })

        .state('chequeListing', {
            url: "/chequeListing",
            templateUrl: "views/chequeListing.html",
            data: {pageTitle: '支票列表', pageSubTitle: ''},
            controller: "financeController",
            resolve: {
                deps: ['$ocLazyLoad', function($ocLazyLoad) {
                    return $ocLazyLoad.load({
                        name: 'app',
                        insertBefore: '#ng_load_plugins_before', // load the above css files before '#ng_load_plugins_before'
                        files: [
                            assets + '/css/dataTable/style.css',

                            assets + '/global/plugins/bootstrap-datepicker/css/datepicker3.css',
                            assets + '/global/plugins/bootstrap-datepicker/js/bootstrap-datepicker.js',

                            assets + '/global/plugins/datatables/all.min.js',
                            assets + '/global/scripts/datatable.js',
                            assets + '/global/plugins/bootbox/bootbox.min.js',
                            'js/controllers/financeController.js',
                            'js/controllers/selectClientCtrl.js',

                        ]
                    });
                }]
               }
        })
		
		

        .state('customerCashListing', {
            url: "/customerCashListing",
            templateUrl: "views/customerCashListing.html",
            data: {pageTitle: '現金客列表', pageSubTitle: ''},
            controller: "financeCashController",
            resolve: {
                deps: ['$ocLazyLoad', function($ocLazyLoad) {
                    return $ocLazyLoad.load({
                        name: 'app',
                        insertBefore: '#ng_load_plugins_before', // load the above css files before '#ng_load_plugins_before'
                        files: [

                            assets + '/global/plugins/bootbox/bootbox.min.js',
                            assets + '/global/plugins/datatables/all.min.js',
                            assets + '/global/scripts/datatable.js',

                            'js/controllers/financeCashController.js',
                            'js/controllers/selectClientCtrl.js',

                            assets + '/global/plugins/bootstrap-datepicker/css/datepicker3.css',
                            assets + '/global/plugins/bootstrap-datepicker/js/bootstrap-datepicker.js',
                        ]
                    });
                }]
            }
        })
        
         .state('supplierMain', {
        url: "/supplierMain", // header.html need it
        templateUrl: "views/supplierListing.html",            
        data: {pageTitle: '供應商管理', pageSubTitle: ''},
        controller: "supplierMain", //Js controller
        resolve: {
            deps: ['$ocLazyLoad', function($ocLazyLoad) {
                return $ocLazyLoad.load({
                    name: 'app',
                    insertBefore: '#ng_load_plugins_before', // load the above css files before '#ng_load_plugins_before'
                    files: [
                     assets + '/global/plugins/bootstrap-datepicker/css/datepicker3.css',
                        assets + '/global/plugins/bootstrap-datepicker/js/bootstrap-datepicker.js',



                        assets + '/css/dataTable/style.css',
                        assets + '/global/plugins/datatables/all.min.js',
                        assets + '/global/scripts/datatable.js',

                        'js/controllers/supplierMain.js' // Js controller
                      
                    ] 
                });
            }]
        }
    })
    
      .state('countryController', {
        url: "/countryController",
        templateUrl: "views/countryListing.html",            
        data: {pageTitle: '國家', pageSubTitle: ''},

        controller: "countryController",
        resolve: {
            deps: ['$ocLazyLoad', function($ocLazyLoad) {
                return $ocLazyLoad.load({
                    name: 'app',
                    insertBefore: '#ng_load_plugins_before', // load the above css files before '#ng_load_plugins_before'
                    files: [
			 assets + '/global/plugins/bootstrap-datepicker/css/datepicker3.css',
                        assets + '/global/plugins/bootstrap-datepicker/js/bootstrap-datepicker.js',



        assets + '/css/dataTable/bootstrap.min.css',
                        assets + '/css/dataTable/dataTables.bootstrap.css',
                        assets + '/js/dataTable/jquery.dataTables.min.js',
                        assets + '/js/dataTable/dataTables.bootstrap.js',
  
                        'js/controllers/countryController.js',

                    ] 
                });
            }]
        }
    }) 
    
          .state('currencyController', {
        url: "/currencyController",
        templateUrl: "views/currencyListing.html",            
        data: {pageTitle: '貨幣', pageSubTitle: ''},

        controller: "currencyController",
        resolve: {
            deps: ['$ocLazyLoad', function($ocLazyLoad) {
                return $ocLazyLoad.load({
                    name: 'app',
                    insertBefore: '#ng_load_plugins_before', // load the above css files before '#ng_load_plugins_before'
                    files: [
			 assets + '/global/plugins/bootstrap-datepicker/css/datepicker3.css',
                        assets + '/global/plugins/bootstrap-datepicker/js/bootstrap-datepicker.js',

        assets + '/css/dataTable/bootstrap.min.css',
                        assets + '/css/dataTable/dataTables.bootstrap.css',
                        assets + '/js/dataTable/jquery.dataTables.min.js',
                        assets + '/js/dataTable/dataTables.bootstrap.js',

                        'js/controllers/currencyController.js',

                    ] 
                });
            }]
        }
    }) 
	
	  
     .state('PoMain', {
        url: "/PoMain",
        templateUrl: "views/poForm.html",            
        data: {pageTitle: '採購單', pageSubTitle: ''},

        controller: "PoMain",
        resolve: {
            deps: ['$ocLazyLoad', function($ocLazyLoad) {
                return $ocLazyLoad.load({
                    name: 'app',
                    insertBefore: '#ng_load_plugins_before', // load the above css files before '#ng_load_plugins_before'
                    files: [
                        assets + '/global/plugins/bootstrap-datepicker/css/datepicker3.css',
                        assets + '/global/plugins/bootstrap-datepicker/js/bootstrap-datepicker.js',
                        assets + '/global/plugins/fuelux/js/spinner.min.js',
                        assets + '/dependencies/jquery.cookie.min.js',
                        assets + '/global/plugins/jquery-inputmask/jquery.inputmask.bundle.min.js',
                        assets + '/global/plugins/bootstrap-maxlength/bootstrap-maxlength.min.js',
                        assets + '/global/plugins/bootbox/bootbox.min.js',
                        
                        'js/controllers/PoMain.js',
                        'js/controllers/selectSupplierControl.js',
                         'js/controllers/selectProductCtrl.js',
                         
                  //      'js/controllers/selectProductCtrl.js',
                    ] 
                });
            }]
        }
    }) 
    
     .state('shipping', {
        url: "/shipping",
        templateUrl: "views/shippingForm.html",            
        data: {pageTitle: '船務管理', pageSubTitle: ''},

        controller: "shipping",
        resolve: {
            deps: ['$ocLazyLoad', function($ocLazyLoad) {
                return $ocLazyLoad.load({
                    name: 'app',
                    insertBefore: '#ng_load_plugins_before', // load the above css files before '#ng_load_plugins_before'
                    files: [
                        assets + '/global/plugins/bootstrap-datepicker/css/datepicker3.css',
                        assets + '/global/plugins/bootstrap-datepicker/js/bootstrap-datepicker.js',
                        assets + '/global/plugins/fuelux/js/spinner.min.js',
                        assets + '/dependencies/jquery.cookie.min.js',
                        assets + '/global/plugins/jquery-inputmask/jquery.inputmask.bundle.min.js',
                        assets + '/global/plugins/bootstrap-maxlength/bootstrap-maxlength.min.js',
                        assets + '/global/plugins/bootbox/bootbox.min.js',
                        
                        'js/controllers/shipping.js',
                   //     'js/controllers/selectSupplierControl.js',
                        'js/controllers/selectPoControl.js', 
                        'js/controllers/selectShipControl.js',
                    ] 
                });
            }]
        }
    }) 
    
      .state('searchship', {
        url: "/searchship",
        templateUrl: "views/searchship.html",            
        data: {pageTitle: '搜尋船務', pageSubTitle: ''},

        controller: "searchship",
        resolve: {
            deps: ['$ocLazyLoad', function($ocLazyLoad) {
                return $ocLazyLoad.load({
                    name: 'app',
                    insertBefore: '#ng_load_plugins_before', // load the above css files before '#ng_load_plugins_before'
                    files: [
                   	 assets + '/global/plugins/bootstrap-datepicker/css/datepicker3.css',
                        assets + '/global/plugins/bootstrap-datepicker/js/bootstrap-datepicker.js',



        assets + '/css/dataTable/bootstrap.min.css',
                        assets + '/css/dataTable/dataTables.bootstrap.css',
                        assets + '/js/dataTable/jquery.dataTables.min.js',
                        assets + '/js/dataTable/dataTables.bootstrap.js',
                        
                        'js/controllers/searchship.js',
                        'js/controllers/selectSupplierControl.js',
                      //  'js/controllers/selectPoControl.js', 
                  //      'js/controllers/selectProductCtrl.js',
                    ] 
                });
            }]
        }
    }) 
    
      .state('ships', {
        url: "/ships",
        templateUrl: "views/shippingSchedule.html",            
        data: {pageTitle: '船務管理列表', pageSubTitle: ''},

       controller: "ships",
        resolve: {
            deps: ['$ocLazyLoad', function($ocLazyLoad) {
                return $ocLazyLoad.load({
                    name: 'app',
                    insertBefore: '#ng_load_plugins_before', // load the above css files before '#ng_load_plugins_before'
                    files: [
                   	 assets + '/global/plugins/bootstrap-datepicker/css/datepicker3.css',
                        assets + '/global/plugins/bootstrap-datepicker/js/bootstrap-datepicker.js',



        assets + '/css/dataTable/bootstrap.min.css',
                        assets + '/css/dataTable/dataTables.bootstrap.css',
                        assets + '/js/dataTable/jquery.dataTables.min.js',
                        assets + '/js/dataTable/dataTables.bootstrap.js',
                        
                        'js/controllers/ships.js',

                    ] 
                });
            }]
        }
    }) 
    
     .state('receiveCtrl', {
        url: "/receiveCtrl",
        templateUrl: "views/receive.html",            
        data: {pageTitle: '收貨管理列表', pageSubTitle: ''},

       controller: "receiveCtrl",
        resolve: {
            deps: ['$ocLazyLoad', function($ocLazyLoad) {
                return $ocLazyLoad.load({
                    name: 'app',
                    insertBefore: '#ng_load_plugins_before', // load the above css files before '#ng_load_plugins_before'
                    files: [
                   	 assets + '/global/plugins/bootstrap-datepicker/css/datepicker3.css',
                        assets + '/global/plugins/bootstrap-datepicker/js/bootstrap-datepicker.js',



        assets + '/css/dataTable/bootstrap.min.css',
                        assets + '/css/dataTable/dataTables.bootstrap.css',
                        assets + '/js/dataTable/jquery.dataTables.min.js',
                        assets + '/js/dataTable/dataTables.bootstrap.js',
                        
                        'js/controllers/receiveCtrl.js',
                        'js/controllers/selectShip.js',

                    ] 
                });
            }]
        }
    }) 
	
	     .state('receiveList', {
        url: "/receiveList",
        templateUrl: "views/receiveList.html",            
        data: {pageTitle: '收貨列表', pageSubTitle: ''},

       controller: "receiveList",
        resolve: {
            deps: ['$ocLazyLoad', function($ocLazyLoad) {
                return $ocLazyLoad.load({
                    name: 'app',
                    insertBefore: '#ng_load_plugins_before', // load the above css files before '#ng_load_plugins_before'
                    files: [
                   	 assets + '/global/plugins/bootstrap-datepicker/css/datepicker3.css',
                        assets + '/global/plugins/bootstrap-datepicker/js/bootstrap-datepicker.js',


                        assets + '/css/dataTable/style.css',
                        assets + '/global/plugins/datatables/all.min.js',
                        assets + '/global/scripts/datatable.js',
                        
                        'js/controllers/receiveList.js',
                        'js/controllers/repack.js',
                    ] 
                });
            }]
        }
    }) 
    
    
    



}]);

/* Init global settings and run the app */
app.run(["$rootScope", "settings", "$state", function($rootScope, settings, $state) {
    $rootScope.$state = $state; // state to be accessed from view
}]);