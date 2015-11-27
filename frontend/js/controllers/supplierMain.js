'use strict';

function editSupplier(customerId)
{
	var scope = angular.element(document.getElementById("queryInfo")).scope();
    scope.$apply(function () {
    	scope.editSupplier(customerId);
    });
}

function delSupplier(id)
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




app.controller('supplierMain', function($scope, $rootScope, $http, SharedService, $location, $timeout, $interval) {
    var fetchDataTimer;
	var querytarget = endpoint + '/querySupplier.json';
        var q2 =  endpoint + '/checkSupplier.json';
	var iutarget = endpoint + '/maniSupplier.json';
        var getChoice = endpoint + '/getChoice.json';
        var getCurrency = endpoint + '/getCurrency.json';
      
         // loadChoice();
     function paymentChange()
     {
      $( document ).ready(function() {
                    $("#paymentgg").change(function() {
                        if($scope.customerInfo_def.payment == "Credit")
                        {
                            $("#creditDay,#creditLimit,#creditAmount").show();
                            
                        }else
                        {
                            $("#creditDay,#creditLimit,#creditAmount").hide();
                            $scope.customerInfo_def.creditDay = 0;
                            $scope.customerInfo_def.creditLimit = 0;
                             $scope.customerInfo_def.creditAmount = 0;
                        }
                  });

                   $scope.changeCountry = function() {
                           var storeId = $scope.customerInfo_def.supplierSuffix.replace(/[A-Za-z]*/,"");
                           $scope.customerInfo_def.supplierSuffix=  $scope.customerInfo_def.countryId + storeId;
                           if($scope.customerInfo_def.supplierSuffix == "undefined")
                           {
                               $scope.customerInfo_def.supplierSuffix= "";
                           }
                     
                  }
 
                 });
     }
	//Something to filter
	$scope.filterData = {
        'name': '',
        'id': '',
        'phone': '',
        'status': '100',
        'country' : '',
        'contact' : '',
        'sorting' :'',
        'current_sorting' :'asc',
	};

    $scope.submit = true;
	$scope.customerInfo_def = {
                        'supplierCodeOri' :'',
			'supplierCode' :'',
                        'supplierSuffix':'',
                        'supplierName' :' ',
                        'phone_1' :' ',
                        'phone_2' :' ',
                        'email' :' ',
                        'countryName' :' ',
                        'creditDay' :' ',
                        'creditLimit' :' ',
                        'status' :' ',
                        'contactPerson_1' :' ',
                        'contactPerson_2' :' ',
                        'link':' ',
                        'address1' :'',
                        'address' :'',
                        'address2' :'',
                        'fax_1': '',
                        'fax_2' :'',
                        'remark':'',
                        'location':'',
                        'supplierAbbre':'',
                       
	};
	$scope.customerInfo_copy = {
                        'supplierCodeOri' :'',
			'supplierCode' :'',
                        'supplierSuffix':'',
                        'supplierName' :' ',
                        'phone_1' :' ',
                        'phone_2' :' ',
                        'email' :' ',
                        'countryName' :' ',
                        'creditDay' :' ',
                        'creditLimit' :' ',
                        'status' :' ',
                        'contactPerson_1' :' ',
                        'contactPerson_2' :' ',
                        'link':' ',
                         'address1' :'',
                        'address' :'',
                        'address2' :'',
                        'fax_1': '',
                        'fax_2' :'',
                         'remark':'',
                          'location':'',
                          'supplierAbbre':'',
	};
   
	$scope.submitbtn = true;
	$scope.newId = "";
      
	$scope.storeAll = "";
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

    
    $scope.editSupplier = function(customerId)
    {
       
    	$scope.submitbtn = true;
        $scope.action = "update";
        $scope.newId = "";
//        $(".phone").inputmask("99999999");
        $scope.mySwitch = true;
    	$http.post(q2, {mode: "single", supplierCode: customerId})
    	.success(function(res, status, headers, config){    
         	//$scope.customerInfo = $.extend({}, res, $scope.customerInfo_def);
        $scope.customerInfo_def = res;
        $scope.customerInfo_def.supplierCodeStore = $scope.customerInfo_def.supplierCodeOri = $scope.customerInfo_def.supplierCode;
        $scope.customerInfo_def.supplierAbbre = $scope.customerInfo_def.supplierCodeStore.substring(0,2);
        $scope.customerInfo_def.supplierSuffix = $scope.customerInfo_def.supplierCodeStore.substring(2);
   
        ($scope.customerInfo_def.payment == "Cash") ?  $("#creditDay,#creditLimit,#creditAmount").hide() : $("#creditDay,#creditLimit,#creditAmount").show();


    		$("#supplierFormModal").modal({backdrop: 'static'});
                $("#supplierFormModal .modal-title").find("span").eq(0).show();
                $("#supplierFormModal .modal-title").find("span").eq(1).hide();

  
             paymentChange();
             $scope.customDate = res.format_date.date.split(" ")[0];
             // $("#format_date").html(res.format_date.date);
         
    	});
    	
    }


    $scope.checkIdexist = function(){

        $http.post(querytarget, {mode: "checkId", supplierCode: $scope.customerInfo.supplierCode})
            .success(function(res){
                $scope.productIdused = res;
                if($scope.productIdused == 1){
                    $scope.submit = false;
                }else
                    $scope.submit = true;

            });

    }

    $scope.click = function(event)
    {
         $scope.filterData.sorting = event.target.id;
    
            if ($scope.filterData.current_sorting == 'asc'){
                $scope.filterData.current_sorting = 'desc';
            }else{
               $scope.filterData.current_sorting = 'asc';
            }
                
         $scope.updateDataSet();
    }
    
    $scope.showselectgroup = function()
    {
        $("#selectGroupmodel").modal({backdrop: 'static'});
    }
    
    function loadChoice()
    {
         $http({
            method: 'GET',
            url: getChoice
        }).success(function (res) {
           var countryStore = "";
          
           for(var i = 0;i<res.length ;i++)
           {
               countryStore += "<option value ="+res[i].countryId+">" + res[i].countryName + "</option>";
           }
            $("#countrySelect").append(countryStore);
        });
        
         $http({
            method: 'GET',
            url: getCurrency
        }).success(function (res) {

         $scope.Model = res;
          console.log($scope.Model);
        });
            
    }

    $scope.addSupplier = function()
    {
         $scope.customerInfo_def = $scope.customerInfo_copy;     
         $scope.newId = "";
         $scope.action = "create";
         $scope.mySwitch = false;

    	$scope.submitbtn = true;
    	$scope.customerInfo = $.extend(true, {}, $scope.customerInfo_def);

//        $(".phone").inputmask("99999999");
    	$("#supplierFormModal").modal({backdrop: 'static'});
        $("#supplierFormModal .modal-title").find("span").eq(1).show();
        $("#supplierFormModal .modal-title").find("span").eq(0).hide();
        paymentChange();
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
        $scope.customerInfo_def.supplierCode =  $scope.customerInfo_def.supplierAbbre + $scope.customerInfo_def.supplierSuffix;
        if( $scope.action == "create")
        {
            $scope.customerInfo_def.supplierCodeOri = "";
             $scope.customerInfo_def.supplierCode = "";
        }
        
        if(!$scope.submit)
            alert('客户編號不能用');
       
             
    		$http.post(iutarget, {supplierinfo: $scope.customerInfo_def})
        	.success(function(res, status, headers, config){    
    
                        if(typeof res == "object")
                        {
                            $("#currencyFormModal").modal('hide');
        		    $scope.updateDataSet();
                            $scope.submitbtn = false;
                            $scope.newId = "供應商編號: " + res.id;
                            $scope.updateDataSet();
                        }else
                        {
                             alert(res);
                        }
        		
        
        		/*if(res.mode == 'update')
        		{
        			$("#customerFormModal").modal('hide');
        		}*/
        		
        		
        		
        		
        	});
    	}
    	
    
    
    $scope.updateZone = function()
    {
    	$scope.updateDataSet();
    }

    $scope.searchGroup = function(){
        $scope.updateDataSet();
    }

    $scope.searchSupplier = function()
    {
        $scope.updateDataSet();
    }
    
    $scope.capitalChar = function(value)
    {
        $scope.customerInfo_def.supplierAbbre = value.toUpperCase();
        /*if($scope.customerInfo_def.supplierAbbre.length == 2 || $scope.customerInfo_def.supplierAbbre.length == 0)
        {
            $scope.newRegex = new RegExp("[^" +  $scope.customerInfo_def.countryId + "0-9]", "g");
            $scope.customerInfo_def.supplierCode = $scope.customerInfo_def.supplierCode.replace($scope.newRegex,"");
            $scope.customerInfo_def.supplierCode = $scope.customerInfo_def.supplierAbbre + $scope.customerInfo_def.supplierCode;
        }    */
    }

      $scope.updateDataSet = function () {
        $(document).ready(function() {

            if(!$scope.firstload)
            {
                $("#datatable_ajax").dataTable().fnDestroy();
            }
            else
            {
                $scope.firstload = false;
            }


            $('#datatable_ajax').dataTable({

                // "dom": '<"row"f<"clear">>rt<"bottom"ip<"clear">>',

                "sDom": '<"row"<"col-sm-6"<"pull-left"p>><"col-sm-6"f>>rt<"row"<"col-sm-12"i>>',

                "bServerSide": true,
                
                "ajax": {
                    "url": querytarget, // ajax source
                    "type": 'POST',
                    "data": {mode: "collection",filterData: $scope.filterData},
                    "xhrFields": {withCredentials: true}
                },
                "iDisplayLength": 50,
                "pagingType": "full_numbers",
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
                            { "data": "supplierCode" ,"width": "10%"},
                            { "data": "supplierName","width": "10%" },
                            { "data": "status","width": "7%" },
                            { "data": "countryName","width": "7%" },
                            { "data": "phone_1" ,"width": "10%"},
                            { "data": "phone_2","width": "10%" },
                            { "data": "contactPerson_1","width": "10%" },
                            { "data": "contactPerson_2","width": "10%" },
                            { "data": "updated_at","width": "10%" },
                            { "data": "link","width": "8%" }
                ]

                

       });
       
        });
    };


});