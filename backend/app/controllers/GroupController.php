<?php

class GroupController extends BaseController {

    public function checkGroup(){
        $filter = Input::get('filterData');
        return Response::json(customerGroup::where('name','LIKE',$filter['name'])->get());
}

} 