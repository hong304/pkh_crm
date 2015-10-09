<?php

class expensesController extends BaseController {


    public function addExpenses()
    {
        $filter = Input::get('filterData');
        $expenses = new expense();
        $expenses->zoneId = $filter['zone']['zoneId'];
        $expenses->deliveryDate = $filter['date'];
        $expenses->cost1 = $filter['cost1'];
        $expenses->cost2 = $filter['cost2'];
        $expenses->cost3 = $filter['cost3'];
        $expenses->cost4 = $filter['cost4'];
        $expenses->cost3_remark = $filter['cost3_remark'];
        $expenses->cost4_remark = $filter['cost4_remark'];
        $expenses->save();
    }
}