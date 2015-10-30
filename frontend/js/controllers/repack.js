'use strict';

Metronic.unblockUI();



app.controller('repack', function ($rootScope, $scope, $http, $timeout, SharedService, $location, $interval, $window, $state, $stateParams) {
    /* Register shortcut key */


    $scope.repack = {
        productId: '',
        productName: '',
        products: '',
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
     
    }

    $scope.receiveInclude = [];
    $scope.receive = {
        'receivingId': '',
        'good_qty': '',
    };


    $scope.$on('handleReUpdate', function () {
        $scope.repack.productId = SharedService.productId;
        $scope.repack.productName = SharedService.productName;
        $scope.repack.products = SharedService.products;

    });

    $scope.totalline = 0;

    $scope.addRows = function () {
        var j = $scope.totalline;
        $scope.selfdefine[j] = $.extend(true, {}, $scope.selfdefineS);
        $scope.selfdefine[j]['productId'] = '';
        $scope.selfdefine[j]['productName'] = '';
        $scope.selfdefine[j]['qty'] = '';
        $scope.selfdefine[j]['unit'] = '';
        $scope.selfdefine[j]['adjustId'] = '';
        $scope.selfdefine[j]['receivingId'] = '';
        $scope.totalline += 1;
    }


    $scope.submitRepack = function () {
        var accumulate = 0;
        for (var j = 0; j < $scope.selfdefine.length; j++) // Change the value of original product
        {
            if($scope.selfdefine[j].qty !== '')
                accumulate += parseInt($scope.selfdefine[j].qty);
        }
        nextOne($scope.repack.products, accumulate);
        

       // console.log($scope.receiveInclude);
          
        
    }
    
     $scope.searchProduct = function (value,i) 
     {
          var target = endpoint + '/preRepackProduct.json';
            $http.post(target, {productId:value})
            .success(function (res, status, headers, config) {
                if(typeof res == "object")
                {
                     var availableunit = [];
                    if(res.productPacking_unit >= 1)
                        availableunit = availableunit.concat([{value: 'unit', label: res.productPackingName_unit}]);
                    if(res.productPacking_inner >= 1)
                        availableunit = availableunit.concat([{value: 'inner', label: res.productPackingName_inner}]);
                    if(res.productPacking_carton >= 1)
                        availableunit = availableunit.concat([{value: 'carton', label: res.productPackingName_carton}]);
                    $scope.selfdefine[i]['availableunit'] = availableunit;
                    $scope.selfdefine[i]['unit'] = $scope.selfdefine[i]['availableunit'][0];
                    $scope.selfdefine[i]['qty'] = 1;
                    $scope.selfdefine[i]['productName'] = res.productName_chi;
                }
            });
     }
    


    function nextOne(ele, accumulate)
    {
        var accum = 0;
        var flag = true;
        var good_qtyStore = parseInt(accumulate);

        var storeTemp = 0;
        var totalStorage = 0;
        for (var y = 0; y < ele.length; y++)
        {
            totalStorage += ele[y].good_qty;
        }

        if(totalStorage < good_qtyStore)
        {
            alert("不夠貨包裝");
        } else
        {
           /* for (var x = 0; x < ele.length; x++)
            { 
                $scope.receiveInclude[x] = $.extend(true, {}, $scope.receive);
                if (ele[x].good_qty > 0)
                {
                    accum = ele[x].good_qty - good_qtyStore;
                    storeTemp = accum;
                    $scope.receiveInclude[x].receivingId = ele[x].receivingId;
                    if (accum > 0 || accum == 0)
                    {
                        $scope.receiveInclude[x].good_qty = accum;
                        return;
                    }
                    else if (accum < 0)
                    {
                        $scope.receiveInclude[x].good_qty = 0;
                        good_qtyStore = Math.abs(storeTemp);
                        continue;
                    }
                }else
                {
                    continue;
                }*/
                insertToAdjust($scope.selfdefine);
            }
            
            //Match new product to original branch

        //Remeber to send json to backend 
        //fix the bug please
     }
     
     function insertToAdjust(items)
     {
        if(items != "")
        {
            var target = endpoint + '/addAjust.json';
            $http.post(target, {items:items})
            .success(function (res, status, headers, config) {
                console.log(res);
                if(res.result)
                {
                    alert("已成功包裝");
                }
            });
        }
     }
     
 
     
});