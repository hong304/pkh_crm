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
              'image' => 'http://yatfai-f.cyrustc.net/assets/temp_photo/'.$photos[rand(0, 2)].'.png',  
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

}