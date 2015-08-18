'use strict';


app.controller('permissionController', function($scope, $rootScope, $http, SharedService, $location, $timeout, $interval) {

    $scope.permissionControl = [];
    $scope.permissionControl_def = {
        groupName: '',
        action : {
            view: '',
            edit: '',
            add: '',
            delete: ''
        }

    }
    var querytarget = endpoint + '/getPermissionLists.json';

    $scope.$on('$viewContentLoaded', function() {
        Metronic.initAjax();
        $scope.systeminfo = $rootScope.systeminfo;



        $http.post(querytarget, {})
            .success(function(res, status, headers, config){

                console.log(res);
                res.forEach(function(item,key) {
                    $scope.permissionControl[key] = $.extend(true, {}, $scope.permissionControl_def);
                    $scope.permissionControl[key]['action']['view'] = item.action.view;
                    $scope.permissionControl[key]['action']['edit'] = item.action.edit;
                    $scope.permissionControl[key]['action']['add'] = item.action.add;
                    $scope.permissionControl[key]['action']['delete'] = item.action.delete;
                    $scope.permissionControl[key]['groupName'] = item.groupName;
                    $scope.permissionControl[key]['name'] = item.name;
                });

                console.log($scope.permissionControl);
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



});