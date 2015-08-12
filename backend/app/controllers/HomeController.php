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
       // $clients = Customer::where('deliveryZone', Session::get('zone'))->with('Zone')->limit(15)->get();

        $clients = Invoice::select(DB::raw('COUNT(*) as count'),'customerName_chi','phone_1','today','tomorrow','Invoice.customerId')->leftjoin('Customer','Customer.customerId', '=', 'Invoice.customerId')->where('deliveryZone', Session::get('zone'))->groupBy('Invoice.customerId')->orderBy('count','desc')->limit(15)->get();

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

        if($mode == 'get'){
            $data['info'] = pickingListVersionControl::where('zone',$info['zone']['zoneId'])->where('date',$info['date'])->where('shift',$info['shift'])->first();
            $data['pending'] = Invoice::where('zoneId',$info['zone']['zoneId'])->where('deliveryDate',strtotime($info['date']))->where('shift',$info['shift'])->where('invoiceStatus',1)->where('version',false)->count();
            $data['normal'] = Invoice::where('zoneId',$info['zone']['zoneId'])->where('deliveryDate',strtotime($info['date']))->where('shift',$info['shift'])->where('invoiceStatus',2)->where('version',false)->count();
            $data['rejected'] = Invoice::where('zoneId',$info['zone']['zoneId'])->where('deliveryDate',strtotime($info['date']))->where('shift',$info['shift'])->where('invoiceStatus',3)->where('version',false)->count();
            $data['replenishment'] = Invoice::where('zoneId',$info['zone']['zoneId'])->where('deliveryDate',strtotime($info['date']))->where('shift',$info['shift'])->where('invoiceStatus',96)->where('version',false)->count();
            return Response::json($data);
        }

        if($mode == 'check'){

            //by pass checking due to first time general picking list
            if($info['info'] == null)
                return 1;



            $f9 = false;
            $deliveryDate = strtotime($info['info']['date']);
            // $info_data = Invoice::where('deliveryDate',$deliveryDate)->where('zoneId',$info['zone']['zoneId'])->whereIn('invoiceStatus',[1,2])->get();
            $info_data = Invoice::where('deliveryDate',$deliveryDate)->where('zoneId',$info['info']['zone'])->where('shift',$info['info']['shift'])->whereIn('invoiceStatus',[1,2,96])
                ->where('f9_picking_dl','!=',1)->where('version',true)->get();

            //user deleted or revised all invoices after general picking list, then return no data
                if(count($info_data) == 0)
                    return 1;

            //have data but only contain 1F items then return 1
            foreach($info_data as $v){
                $q[]= $v->invoiceId;
            }
            $ii = InvoiceItem::wherein('invoiceId',$q)->get();
            foreach ($ii as $v){
                if($v->productLocation == 9){
                    $f9 = true;
                }
            }

                $date = str_replace('-','',$info['info']['date']);
                $id = $date.$info['info']['zone'].'-'.$info['info']['f9_version'].'-9';
                $result = ReportArchive::where('id','LIKE',$id)->where('shift',$info['info']['shift'])->first();

                if($result || !$f9)
                    return 1;
                else
                    return 0;

        }
        if($mode == 'post')
        {
            $invoiceId = [];
            $f9 = false;
            $deliveryDate = strtotime($info['date']);

            $info_data = Invoice::where('deliveryDate',$deliveryDate)->where('zoneId',$info['zone']['zoneId'])->where('shift',$info['shift'])->whereIn('invoiceStatus',[1,2,96])
                ->where('version',false)->lists('invoiceId');

            $ii = InvoiceItem::wherein('invoiceId',$info_data)->get();


            $user = pickingListVersionControl::where('date',$info['date'])->where('zone',$info['zone']['zoneId'])->where('shift',$info['shift'])->first();

            foreach ($ii as $v){
                if($v->productLocation == 9){
                    $invoiceId[$v->invoiceId] = isset($user->f9_version)?$user->f9_version+1:'1';
                    $f9 = true;
                }else
                    if(!isset($invoiceId[$v->invoiceId]))
                        $invoiceId[$v->invoiceId] = 100;
            }

            if($user == null){
                $newp =  new pickingListVersionControl();
                if($f9)
                    $newp->f9_version = 1;
                $newp->date = $info['date'];
                $newp->zone = $info['zone']['zoneId'];
                $newp->shift = $info['shift'];
                $newp->save();
            }else{
                if($f9)
                    $user->f9_version += 1;
                $user->save();
            }

            foreach($invoiceId as $k => $v){
                // Invoice::where('deliveryDate',$deliveryDate)->where('zoneId',$info['zone']['zoneId'])->where('invoiceStatus',4)->update(['f9_picking_dl'=>1]);
                Invoice::where('deliveryDate',$deliveryDate)->where('zoneId',$info['zone']['zoneId'])->where('shift',$info['shift'])->where('invoiceId',$k)->update(['previous_status'=>DB::raw('invoiceStatus'),'version'=>$v]);
            }


           // Invoice::where('deliveryDate',$deliveryDate)->where('zoneId',$info['zone']['zoneId'])->whereIn('invoiceStatus',[1,2,96])->where('shift',$info['shift'])->with('InvoiceItem')
            // ->update(['previous_status'=>DB::raw('invoiceStatus'),'version' => true]);



            return Response::json($f9);
        }
       //

    }

}