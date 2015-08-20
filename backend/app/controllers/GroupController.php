<?php

class GroupController extends BaseController {

    public function checkGroup(){
        $filter = Input::get('client_keyword');
        return Response::json(customerGroup::where('name','LIKE','%'.$filter['keyword'].'%')->where('id','LIKE','%'.$filter['id'].'%')->get());
}

    public function jsonManiulateGroup()
    {

        if (Input::get('mode') == 'del') {
            // pd(Input::get('customer_id'));
            $query = Customer::where('customerId', Input::get('customer_id'))->first();
            $query->delete();
            // p(Input::get('customer_id'));
            return [];
        }

        $i = Input::get('GroupInfo');

        $this->manipulate($i['id']);
        $id = $this->save($i);

        return Response::json(['mode' => ($i['id'] == $id ? 'update' : 'create'), 'id' => $id]);
    }


    public function manipulate($groupId = false)
    {
        $this->action = $groupId ? 'update' : 'create';

        if($this->action == 'create')
        {
            $this->im = new customerGroup();
            $this->im->created_by = Auth::user()->id;

        }
        elseif($this->action == 'update')
        {
            $this->im = customerGroup::where('id', $groupId)->firstOrFail();
            $this->im->updated_by = Auth::user()->id;

        }
    }

    public function save($info)
    {

        $fields = ['name','address', 'contact_1', 'contact_2', 'email', 'phone_1', 'phone_2','description'];

        foreach($fields as $f)
        {
            $this->im->$f = $info[$f];
        }
        $this->im->groupStatus = $info['groupStatus']['value'];
        $this->im->save();

        return $this->im->id;

    }

    public function jsonQueryGroup()
    {


        $mode = Input::get('mode');

        if ($mode == 'collection') {
            $filter = Input::get('filterData');

            $customer = customerGroup::select('*');


            $customer->where('name', 'LIKE', '%'.$filter['name'].'%');

            if ($filter['status'] == 99) {
                $customer->onlyTrashed();
            } else if ($filter['status'] != 100) {
                $customer->where('status', $filter['status']);
            }

            $customer->where(function ($query) use ($filter) {
                $query
                    ->where('name', 'LIKE', '%' . $filter['name'] . '%');
            });

            return Datatables::of($customer)
                ->addColumn('link', function ($produc) {
                    if(Auth::user()->can('edit_group'))
                        return '<span onclick="editGroup(\'' . $produc->id . '\')" class="btn btn-xs default"><i class="fa fa-search"></i> 修改</span>';
                    else
                        return '';
                })->editColumn('groupStatus',function($c){
                    if ($c->groupStatus == '1')
                        return '正常';
                    else
                        return '暫停';
            }) ->addColumn('delete', function ($produc) {
                    if(Auth::user()->can('delete_group'))
                        return '<span onclick="delGroup(\'' . $produc->id . '\')" class="btn btn-xs default"><i class="fa glyphicon glyphicon-remove"></i> 刪除</span>';
                    else
                        return '';
                })
                ->make(true);

        } elseif ($mode == 'single') {
            $customer = customerGroup::where('id', Input::get('GroupId'))->first();
        }

        return Response::json($customer);
    }



} 