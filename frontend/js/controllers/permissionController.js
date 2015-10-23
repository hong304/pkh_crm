'use strict';


app.controller('permissionController', function($scope, $rootScope, $http, SharedService, $location, $timeout, $interval) {

    $scope.permissionControl = [];
    $scope.permissionControl_def = {
        groupName: '',
        action : {
        }
    }

    $scope.filterData={
        'level' : ''
    }

    var querytarget = endpoint + '/getPermissionLists.json';
    var getUserGroup = endpoint + '/getUserGroup.json';

    $scope.$on('$viewContentLoaded', function() {
        Metronic.initAjax();
        $scope.systeminfo = $rootScope.systeminfo;

        $http.get(getUserGroup)
            .success(function(res, status, headers, config){
                if(res == false){
                   $location.path('/#/salesPanel');
                }
                $scope.usergroup = res;
                $scope.filterData.level = $scope.usergroup[0];
                $scope.updateDataSet();
            });
    });

    $scope.postPermission = function(){

        $http.post(querytarget, {mode:'posting',data:$scope.permissionControl})
            .success(function(res, status, headers, config){

            });
    }

    $scope.$watch(function() {
        return $rootScope.systeminfo;
    }, function() {
        $scope.systeminfo = $rootScope.systeminfo;
    }, true);

    $scope.updateDataSet = function(){


        $http.post(querytarget, {roleId:$scope.filterData.level})
        .success(function(res, status, headers, config){

            res.forEach(function(item,key) {
                var view = eval('item.action.view_'+item.name);
                var edit = eval('item.action.edit_'+item.name);
                var add = eval('item.action.add_'+item.name);
                var delete1 = eval('item.action.delete_'+item.name);


                $scope.permissionControl[key] = $.extend(true, {}, $scope.permissionControl_def);
                $scope.permissionControl[key]['action']['view_'+item.name] =  view;
                $scope.permissionControl[key]['action']['edit_'+item.name] = edit;
                $scope.permissionControl[key]['action']['add_'+item.name] = add;
                $scope.permissionControl[key]['action']['delete_'+item.name] = delete1;
                $scope.permissionControl[key]['groupName'] = item.groupName;
                $scope.permissionControl[key]['name'] = item.name;
                $scope.permissionControl[key]['roleId'] = item.roleId;
            });

                console.log($scope.permissionControl);
        });
    }


});