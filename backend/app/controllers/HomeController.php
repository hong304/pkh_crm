<?php

class HomeController extends BaseController {

    public function jsonDashboard()
    {
        $photos = [
            'D-01-001', 'D-01-006', 'D-01-007'
        ];
        for($i = 1; $i <= 8; $i++)
        {
            $products[] = [
              'name' => '',
              'image' => $_SERVER['frontend'].'/assets/temp_photo/'.$photos[rand(0, 2)].'.png',
            ];
        }
        /*
        for($i = 1; $i <= 20; $i++)
        {
            $clients[] = [
                'rank' => $i,
                'tel' => '88888888',
                'name' => 'ABC Company Limited',
                'today' => true,
                'tomorrow' => false,
            ];
        }
        */
        $clients = Customer::where('deliveryZone', Session::get('zone'))->with('Zone')->limit(15)->get();
        $zoneDetail = UserZone::select('zoneId')->where('userId', Auth::user()->id)->with('zoneDetail')->get();
        $current_zone = Zone::where('zoneId',Session::get('zone'))->first();
        foreach($zoneDetail as $z)
        {
            $zones[] = ['id'=>$z->zoneId, 'name'=>$z->zoneDetail->zoneName];
        }
        
        $returnInfo = [
            'products' => $products,
            'client' => $clients->toArray(),
            'zones' => $zones,
            'current_zone' => $current_zone->zoneName,
        ];
        return Response::json($returnInfo);
    }

    public function generalPickingStatus(){
        $mode = Input::get('mode');
        $info = Input::get('info');

        if($mode == 'get')
            $data = pickingListVersionControl::where('zone',$info['zone']['zoneId'])->where('date',$info['date'])->first();

        if($mode == 'post')
        {

            $f1 = false;
            $f9 = false;
            $deliveryDate = strtotime($info['date']);

            $info_data = Invoice::where('deliveryDate',$deliveryDate)->where('zoneId',$info['zone']['zoneId'])->where('invoiceStatus',2)->get();

            foreach($info_data as $v){
                 $q[]= $v->invoiceId;
            }

            $ii = InvoiceItem::wherein('invoiceId',$q)->get();

            foreach ($ii as $v){
                if($v->productLocation == 1){
                    $f1 = true;
                }

                if($v->productLocation == 9){
                   $f9 = true;
                }
            }


            $data = Invoice::where('deliveryDate',$deliveryDate)->where('zoneId',$info['zone']['zoneId'])->where('invoiceStatus',2)->with('InvoiceItem')->update(array('invoiceStatus' => 4));



            $user = pickingListVersionControl::where('date',$info['date'])->where('zone',$info['zone']['zoneId'])->first();
            if($user == null){
                $newp =  new pickingListVersionControl();
                if($f1)
                    $newp->f1_version = 1;
                if($f9)
                    $newp->f9_version = 1;
                $newp->date = $info['date'];
                $newp->zone = $info['zone']['zoneId'];
                $newp->save();
            }else{
                if($f1)
                    $user->f1_version += 1;
                if($f9)
                    $user->f9_version += 1;
                $user->save();
            }

        }
        return Response::json($data);

    }

}