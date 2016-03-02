<?php

class containerController extends BaseController {


    public function getfullContainerInfo(){

        $shippingId = Input::get('shippingId');
        $containerId = Input::get('containerId');

        $shippingitems = shippingitem::with(['containerproduct'=>function($q){
            $q->with('product');
        }])->with(['shipping' => function ($query) {
            $query->with('Supplier','purchaseOrder');
        }])->where('containerId',$containerId)->where('shippingId',$shippingId)->first()->toArray();


        return Response::json($shippingitems);
    }


    public function searchContainer() {
        $mode = Input::get('mode');
        $filter = Input ::get('filterData');
        $current_sorting = $filter['current_sorting'];
        $sorting = "containerId";
        if (!$filter['sorting'] == '') {
            $sorting = $filter['sorting'];
        }

        if ($mode == 'collection') {

            $shippingitems = containerproduct::with(['container'=>function($q){
                $q->with(['shipping'=>function($q1){
                   $q1->with('supplier');
                }]);
            }])->with('product')->orderby($sorting, $current_sorting);





            //Dont add get() here
            return Datatables::of($shippingitems)
                            ->addColumn('link', function ($shi) {
                                if($shi->container->sale_method == 2)
                                    return '<a href="/#/trading?shippingId='.$shi->container->shippingId.'&containerId='.$shi->container->containerId.'" class="btn btn-xs default"><i class="fa fa-search"></i>Trade</a>';
                                else
                                    return '';
                            })->make(true);
        }

      //  return Response::json($ship);
    }

    public function jsonGetSingleShip() {
        $shippingId = Input::get('shippingId');

        $base = Shipping::where('shippingId', $shippingId);

        $shipping = Shipping::getFullShippment($base);


        $returnInformation = [
            'shipping' => array_values($shipping['shipping'])[0],
            'shippingItem' => array_values($shipping['shipping'])[0]['shippingitem'],
        ];
        return Response::json($returnInformation);
    }

    public function deleteShip() {
        $shippingId = Input::get('shippingId');
        $shipment = new shippingMan($shippingId);
        return Response::json($shipment->setDeleteShip());
    }

    public function loadShip() {
        $id = Input::get('id');
        $ship = Shipping::where('shippingId', $id)->with('Shippingitem')->get();
        return Response::json($ship);
    }

}
