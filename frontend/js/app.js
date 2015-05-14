/* Define the environment according to the windows setting */
//hi test
if(window.location.hostname != "frontend.sylam.net")
{
	var endpoint = '//yatfai.cyrustc.net';
	var assets = '//yatfai-f.cyrustc.net/assets';
}
else
{
	var endpoint = '//backend.sylam.net/';
	var assets = '//frontend.sylam.net/assets';
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

       /* if(!data.user)
        {
        	window.location.href = $scope.endpoint + '/credential/auth';
        }*/
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
       $interval($scope.getNotification, 15000);
        $interval($scope.broadCastNotification, 500);
        
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

            if(data.logintime != parseInt(data.db_logintime)){
                alert('你已被登出')
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
    
        
    .state('newOrder', {
        url: "/newOrder",
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
						assets + '/global/plugins/datatables/all.min.js',
						assets + '/global/scripts/datatable.js',
                        assets + '/global/plugins/bootbox/bootbox.min.js',
                        'js/controllers/customerMaintenanceCtrl.js',
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
						assets + '/global/plugins/datatables/all.min.js',
						assets + '/global/scripts/datatable.js',
						assets + '/global/plugins/bootstrap-datepicker/css/datepicker3.css',
						assets + '/global/plugins/bootstrap-datepicker/js/bootstrap-datepicker.js',
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
						assets + '/global/plugins/datatables/all.min.js',
						assets + '/global/scripts/datatable.js',
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
                            'js/controllers/financeController.js',
                            'js/controllers/selectClientCtrl.js',
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
                            assets + '/global/plugins/datatables/all.min.js',
                            assets + '/global/scripts/datatable.js',
                            assets + '/global/plugins/bootbox/bootbox.min.js',
                            'js/controllers/financeController.js',
                            'js/controllers/selectClientCtrl.js',
                        ]
                    });
                }],
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


                            'js/controllers/financeCashController.js',
                            'js/controllers/selectClientCtrl.js',

                            assets + '/global/plugins/bootstrap-datepicker/css/datepicker3.css',
                            assets + '/global/plugins/bootstrap-datepicker/js/bootstrap-datepicker.js',
                        ]
                    });
                }],
            }
        })



}]);

/* Init global settings and run the app */
app.run(["$rootScope", "settings", "$state", function($rootScope, settings, $state) {
    $rootScope.$state = $state; // state to be accessed from view
}]);