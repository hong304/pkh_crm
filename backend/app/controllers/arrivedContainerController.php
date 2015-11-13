<?php

class arrivedContainerController extends BaseController{
    
    public function jqueryGetArrived()
    {
       $mode = Input :: get('mode');
   
              $receiving = Receiving :: select('containerId')->with(['Shipping' =>function($query) {
                        $query->with('Shippingitem');
                    }])->with(['Shipping' =>function($query) {
                        $query->with('Supplier');
                    }])->with('product')->get()->toArray();
                    
              return Datatables::of($receiving)
              ->make(true);
               return Response::json($receiving);
    }
}