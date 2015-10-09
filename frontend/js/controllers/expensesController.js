'use strict';


app.controller('expensesController', function($scope, $rootScope, $http, SharedService, $location, $timeout, $interval) {

    var today = new Date();
    var plus = today.getDay() == 6 ? 3 : 2;
    var currentDate = new Date(new Date().getTime() + 24 * 60 * 60 * 1000 * plus);
    var start_date = new Date(new Date().getTime() - 24 * 60 * 60 * 1000 * 1);

    var ymonth = start_date.getMonth() + 1;
    var yyear = start_date.getFullYear();
    var yday = start_date.getDate();


    $scope.$on('$viewContentLoaded', function() {
        Metronic.initAjax();
        $scope.systeminfo = $rootScope.systeminfo;
        $scope.buttonText = '提交';
        $('.date-picker').datepicker({
            rtl: Metronic.isRTL(),
            orientation: "left",
            autoclose: true
        });

        $('.date-picker').datepicker( "setDate" , yyear + '-' + ymonth + '-' + yday );

    });

    $scope.$watch(function() {
        return $rootScope.systeminfo;
    }, function() {
        $scope.systeminfo = $rootScope.systeminfo;
    }, true);

$scope.submit = function(){
    $scope.buttonText = '提交中...';
    $http({
        method: 'POST',
        url: endpoint + '/addExpenses.json',
        data: {filterData: $scope.expenses}
    }).success(function (res) {
        $scope.buttonText = '提交成功';
        $("#submitbutton").prop("disabled",true);
    });
}

});