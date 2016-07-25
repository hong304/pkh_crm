<?php

class containerController extends BaseController {


    public function getfullContainerInfo(){

        if(Input::has('invoiceId'))
            $container_id = container::where('invoiceId',Input::get('invoiceId'))->first()->id;
        else
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

            $shippingitems= Container::select('shippings.etdDate','unitprice','containers.id','shippings.shippingId','poTradeTerm','sale_method','supplierName','container_size','carrier','vessel','containerId','containerproducts.productId','productName_chi','qty','container_actualDate','shippings.etaDate','fsp')
                ->leftJoin('shippings','shippings.shippingId','=','containers.shippingId')
                ->leftJoin('suppliers','suppliers.supplierCode','=','shippings.supplierCode')
                ->leftJoin('containerproducts','containerproducts.container_id','=','containers.id')
                ->leftJoin('product','product.productId','=','containerproducts.productId')
            ->leftJoin('purchaseorders','purchaseorders.poCode','=','shippings.poCode')
                ->leftJoin('poitems','poitems.poCode','=','purchaseorders.poCode');


            if($filter['containerId']!=''){
                $shippingitems->where('containerId',$filter['containerId']);
            }

            if($filter['shippingId']!=''){
                $shippingitems->where('shippingId',$filter['shippingId']);
            }

            if($filter['supplierName']!=''){
                $shippingitems->where('supplierName',$filter['supplierName']);
            }



            $shippingitems->wherebetween('shippings.etaDate',[$filter['etaDate'],$filter['etaDate2']])->orderby($sorting, $current_sorting);

            //Dont add get() here
            return Datatables::of($shippingitems)
                            ->addColumn('link', function ($shi) {
                                if($shi->sale_method == 2 && $shi->trade_way != '')
                                    return '<a href="/#/trading?container_id='.$shi->id.'" class="btn btn-xs default"><i class="fa fa-search"></i>Trade</a>';
                                else
                                    return 'EDIT';
                            })->addColumn('amount', function ($shi) {

                        return $shi->qty*$shi->unitprice;

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
