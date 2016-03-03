<?php

class containerController extends BaseController {


    public function getfullContainerInfo(){

        $container_id = Input::get('container_id');

        $shippingitems = container::with(['containerproduct'=>function($q){
            $q->with('product');
        }])->with(['shipping' => function ($query) {
            $query->with('Supplier','purchaseOrder');
        }])->where('id',$container_id)->first()->toArray();


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

            $shippingitems= container::select('containers.id','shippings.shippingId','sale_method','supplierName','container_size','carrier','vessel','containerId','containerproducts.productId','productName_chi','qty','container_actualDate','fsp')
                ->leftJoin('shippings','shippings.shippingId','=','containers.shippingId')
                ->leftJoin('suppliers','suppliers.supplierCode','=','shippings.supplierCode')
                ->leftJoin('containerproducts','containerproducts.container_id','=','containers.id')
                ->leftJoin('product','product.productId','=','containerproducts.productId');


            if($filter['containerId']!=''){
                $shippingitems->where('containerId',$filter['containerId']);
            }

            if($filter['shippingId']!=''){
                $shippingitems->where('shippingId',$filter['shippingId']);
            }

            if($filter['supplierName']!=''){
                $shippingitems->where('supplierName',$filter['supplierName']);
            }

            $shippingitems->wherebetween('actualDate',[$filter['etaDate'],$filter['etaDate2']])->orderby($sorting, $current_sorting);

            //Dont add get() here
            return Datatables::of($shippingitems)
                            ->addColumn('link', function ($shi) {
                                if($shi->sale_method == 2)
                                    return '<a href="/#/trading?container_id='.$shi->id.'" class="btn btn-xs default"><i class="fa fa-search"></i>Trade</a>';
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
