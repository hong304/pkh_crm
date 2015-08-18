'use strict';


app.controller('permissionController', function($scope, $rootScope, $http, SharedService, $location, $timeout, $interval) {


    $scope.$on('$viewContentLoaded', function() {
        Metronic.initAjax();
        $scope.systeminfo = $rootScope.systeminfo;

        var querytarget = endpoint + '/getPermissionLists.json';

        $http.post(querytarget, {})
            .success(function(res, status, headers, config){
                //$scope.info = $.extend(true, {}, $scope.info_def);
                $scope.permissionControl = res;
                console.log(res);
            });

    });

    $scope.$watch(function() {
        return $rootScope.systeminfo;
    }, function() {
        $scope.systeminfo = $rootScope.systeminfo;
    }, true);



});