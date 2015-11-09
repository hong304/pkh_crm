<?php

class expensesController extends BaseController {


    public function addExpenses()
    {


        $filter = Input::get('filterData');

        if($filter['id']!=''){
            $expenses = expense::where('id',$filter['id'])->first();
        }else{
            $expenses = expense::where('deliveryDate',$filter['deliveryDate'])->where('zoneId',$filter['zone']['zoneId'])->first();
            if(count($expenses) == null)
                $expenses = new expense();
        }
        $expenses->zoneId = $filter['zone']['zoneId'];
        $expenses->deliveryDate = $filter['deliveryDate'];
        $expenses->cost1 = $filter['cost1'];
        $expenses->cost2 = $filter['cost2'];
        $expenses->cost3 = $filter['cost3'];
        $expenses->cost4 = $filter['cost4'];
        $expenses->cost3_remark = $filter['cost3_remark'];
        $expenses->cost4_remark = $filter['cost4_remark'];
        $expenses->save();
    }

    public function queryExpenses()
    {

        $mode = Input::get('mode');

        if ($mode == 'collection') {
            $filter = Input::get('filterData');

            if (!isset($filter['zone']['zoneId']))
                $filter['zone']['zoneId'] = '';

            $expenses = expense::select('*');

            // zone
            $permittedZone = explode(',', Auth::user()->temp_zone);

            if ($filter['zone']['zoneId'] != '') {
                // check if zone is within permission
                if (!in_array($filter['zone']['zoneId'], $permittedZone)) {
                    // *** status code to be updated
                    App::abort(404);
                } else {
                    $expenses->where('zoneId', $filter['zone']['zoneId']);
                }
            } else {
                $expenses->wherein('zoneId', $permittedZone);
            }

            $expenses->whereBetween('deliveryDate',[date('Y-m-d',strtotime($filter['deliverydate'])),date('Y-m-d',strtotime($filter['deliverydate2']))])->orderby('deliveryDate','desc')->orderby('zoneId','asc');


            return Datatables::of($expenses)
                ->addColumn('link', function ($expense) {
                    if (Auth::user()->can('edit_expenses'))
                        return '<span onclick="editExpenses(\'' . $expense->id . '\')" class="btn btn-xs default"><i class="fa fa-search"></i> 修改</span>';
                    else
                        return '';
                }) ->addColumn('updated_by_text', function ($expense) {
                            return $expense->updated_by_text;
                })->make(true);

        } elseif ($mode == 'single') {
            $expenses = expense::find(Input::get('id'));
            return Response::json($expenses);
        }


    }
}