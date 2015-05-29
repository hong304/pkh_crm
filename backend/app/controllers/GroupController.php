<?php

class GroupController extends BaseController {

    public function checkGroup(){
        $filter = Input::get('filterData');
        return Response::json(customerGroup::where('name','LIKE',$filter['name'])->get());
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
        $this->im->status = $info['status']['value'];
        $this->im->save();

        return $this->im->id;

    }

    public function jsonQueryGroup()
    {


        $mode = Input::get('mode');

        if ($mode == 'collection') {
            $filter = Input::get('filterData');


            Paginator::setCurrentPage((Input::get('start') + 10) / Input::get('length'));
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

            // query

            $page_length = Input::get('length') <= 50 ? Input::get('length') : 50;
            $customer = $customer->paginate($page_length);


            foreach ($customer as $c) {
                if ($c->status == '1')
                    $c->status = '正常';
                else
                    $c->status = '暫停';

                if ($c->deleted_at != '') {
                    $c->delete = '';
                    $c->link = '';
                } else {
                    $c->delete = '<span onclick="delGroup(\'' . $c->id . '\')" class="btn btn-xs default"><i class="fa glyphicon glyphicon-remove"></i> 刪除</span>';
                    $c->link = '<span onclick="editGroup(\'' . $c->id . '\')" class="btn btn-xs default"><i class="fa fa-search"></i> 修改</span>';
                }

            }
        } elseif ($mode == 'single') {
            $customer = customerGroup::where('id', Input::get('GroupId'))->first();
        }

        return Response::json($customer);
    }



} 