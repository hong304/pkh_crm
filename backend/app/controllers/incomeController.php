<?php

class incomeController extends BaseController {


    public function addIncome()
    {


        $filter = Input::get('filterData');

        $system = new SystemController();

        if (strtotime($filter['deliveryDate'])<=strtotime($system->getPreviousDay(5))){
            return 'error code: 1987-Ax';
        }

        if($filter['id']!=''){
            $expenses = income::where('id',$filter['id'])->first();
        }else{
            $expenses = income::where('deliveryDate',$filter['deliveryDate'])->where('zoneId',$filter['zone']['zoneId'])->first();
            if(count($expenses) == null)
                $expenses = new income();
        }
        $expenses->zoneId = $filter['zone']['zoneId'];
        $expenses->deliveryDate = $filter['deliveryDate'];
        $expenses->notes = $filter['notes'];
        $expenses->coins = $filter['coins'];
        $expenses->save();
    }

    public function queryIncome()
    {

        $mode = Input::get('mode');

        if ($mode == 'collection') {
            $filter = Input::get('filterData');

            if (!isset($filter['zone']['zoneId']))
                $filter['zone']['zoneId'] = '';

            $expenses = income::select('*');

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

            $system = New SystemController();

            return Datatables::of($expenses)
                ->addColumn('link', function ($expense) use ($system) {
                    if (Auth::user()->can('edit_income') && strtotime($expense->deliveryDate) > strtotime($system->getPreviousDay(5)) )
                        return '<span onclick="editIncome(\'' . $expense->id . '\')" class="btn btn-xs default"><i class="fa fa-search"></i> 修改</span>';
                    else
                        return '';
                }) ->addColumn('updated_by_text', function ($expense) {
                            return $expense->updated_by_text;
                }) ->addColumn('total', function ($expense) {
                    return $expense->coins+$expense->notes;
                })->make(true);

        } elseif ($mode == 'single') {
            $expenses = income::find(Input::get('id'));
            return Response::json($expenses);
        }


    }
}