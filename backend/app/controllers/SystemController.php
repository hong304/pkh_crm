<?php

use Toddish\Verify\Models\Permission;

class SystemController extends BaseController {
    
    public $currency = array (
            'HKD' => '港幣',
        );
    
    public function jsonSystem()
    {
        foreach($this->currency as $key=>$value)
        {
            $currency[] = $key . ' - ' . $value;
        }
        
        
        if(!Auth::check())
        {
            $permissionList = [];
            $zone = [];   
            $productgroup = []; 
        }
        else 
        {
            $user = Auth::user();
            
            // permission
            $vperm = Permission::all();
            foreach($vperm as $perm)
            {
                $permissionList[$perm->name] = $user->can($perm->name);
            }



            // zone
            $z = Zone::wherein('zoneId', explode(',', Auth::user()->temp_zone))->get()->toArray();

            //customer group
            $c = customerGroup::all()->toArray();


            $currentzone = '';
            foreach($z as $cz)
            {
                if($cz['zoneId'] == Session::get('zone'))
                {
                    $currentzone = $cz['zoneName'];
                }
            }
            
            // product group
            $productgroups = ProductGroup::all();
            foreach($productgroups as $pg)
            {
                $productgroup[] = [
                    'groupid' => $pg->productDepartmentId . '-' . $pg->productGroupId . '-',
                    'groupname' => $pg->productDepartmentName . '-' . $pg->productGroupName,
                ]; 
            }
            
        }


        $holidays =  holiday::where('year',date("Y"))->first();

        $h_array = explode(",", $holidays->date);
        foreach($h_array as &$v){
            $md = explode("-",$v);
            $m = str_pad($md[0], 2, '0', STR_PAD_LEFT);
            $d = str_pad($md[1], 2, '0', STR_PAD_LEFT);
            $v = $m.'-'.$d;
        }

       $broadcastMessages = broadcastMessage::whereHas("broadcastMessageRead", function($q) {
            $q->where('user_id',Auth::user()->id);
        }, '<', 1)->get();
        
        $systeminfo = [
          'status' => 'on',
          'user' => Auth::check() ? Auth::user() : Auth::check(),  
          'username' => Auth::check() ? Auth::user()->username : '',
          'realusername' => Auth::check() ? Auth::user()->name : '',
          'currencylist' => $currency,
          'permission' => $permissionList,
          'availableZone' => $z,
          'currentzone' => $currentzone,
          'productgroup' => $productgroup,
            'customerGroup' => $c,
            'holiday' => $h_array,
            'broadcastMessage' => $broadcastMessages,
          //'invoiceStatus' => Config::get('invoiceStatus'),
         ];

        return Response::json($systeminfo);
    }

    public function getDashboard(){
        $today              = strtotime("00:00:00");
        $yesterday          = strtotime("-1 day", $today);
        $tomorrow = strtotime("+1 day",$today);

        $total = [];

        $result = Invoice::select('amount','deliveryDate','zoneId')->whereBetween('deliveryDate',[$yesterday,$tomorrow])->wherein('invoiceStatus',['2','1','20','30'])->orderBy('zoneId')->orderBy('deliveryDate')->get();

        $nr = [];

        foreach($result as $v){
            $total['date'][$v->deliveryDate]['volume'] = (isset($total['date'][$v->deliveryDate]['volume'])?$total['date'][$v->deliveryDate]['volume']:0) + 1;

            if($v->deliveryDate == $today)
                $total['date'][$today]['against_percentage'] = number_format(($total['date'][$today]['volume']-$total['date'][$yesterday]['volume'])/$total['date'][$today]['volume']*100,1,'.',',').'%';

        }



            if($total['date'][$today]['volume'] > $total['date'][$yesterday]['volume']){
                $total['compare'] = 2;
            }else if($total['date'][$today]['volume'] < $total['date'][$yesterday]['volume']){
                $total['compare'] = 0;
            }else
                $total['compare'] = 1;


        foreach($result as $v){

            $nr['byZone'][$v->zoneId]['date'][$v->deliveryDate]['amount'] = (isset($nr['byZone'][$v->zoneId]['date'][$v->deliveryDate]['amount'])?$nr['byZone'][$v->zoneId]['date'][$v->deliveryDate]['amount']:0) + $v->amount;
            $nr['byZone'][$v->zoneId]['date'][$v->deliveryDate]['volume'] = (isset($nr['byZone'][$v->zoneId]['date'][$v->deliveryDate]['volume'])?$nr['byZone'][$v->zoneId]['date'][$v->deliveryDate]['volume']:0) + 1;

            if(isset($nr['byZone'][$v->zoneId]['date'][$today]['volume'])&&isset($nr['byZone'][$v->zoneId]['date'][$yesterday]['volume']))
                if($nr['byZone'][$v->zoneId]['date'][$today]['volume'] > $nr['byZone'][$v->zoneId]['date'][$yesterday]['volume'])
                    $nr['byZone'][$v->zoneId]['compare'] = 2;
                else if($nr['byZone'][$v->zoneId]['date'][$today]['volume'] < $nr['byZone'][$v->zoneId]['date'][$yesterday]['volume'])
                    $nr['byZone'][$v->zoneId]['compare'] = 0;
                else
                    $nr['byZone'][$v->zoneId]['compare'] = 1;

           // $nr['byTime'][$v->deliveryDate]['amount'] = (isset($nr['byTime'][$v->deliveryDate]['amount'])?$nr['byTime'][$v->deliveryDate]['amount']:0) + $v->amount;
           // $nr['byTime'][$v->deliveryDate]['volume'] = (isset($nr['byTime'][$v->deliveryDate]['volume'])?$nr['byTime'][$v->deliveryDate]['volume']:0) + 1;

            $nr['byZone'][$v->zoneId]['name'] = $v->zoneText;


                $nr['byZone'][$v->zoneId]['date'][$v->deliveryDate]['percentage'] = number_format($nr['byZone'][$v->zoneId]['date'][$v->deliveryDate]['volume']/$total['date'][$v->deliveryDate]['volume']*100,1,'.',',').'%';

            if($v->deliveryDate == $today)
                $nr['byZone'][$v->zoneId]['date'][$today]['against_percentage'] = number_format(($nr['byZone'][$v->zoneId]['date'][$today]['volume']-$nr['byZone'][$v->zoneId]['date'][$yesterday]['volume'])/$nr['byZone'][$v->zoneId]['date'][$today]['volume']*100,1,'.',',').'%';

        }



        $nr1 = array_chunk($nr['byZone'],5,true);

        $result = Invoice::select('deliveryDate','zoneId',DB::raw('count(*) as total'))->where('deliveryDate',$today)->wherein('invoiceStatus',['2','1','20','30'])->groupBy('zoneId')->orderBy(DB::raw('count(*)'),'desc')->get();

        $total1 = 0;
        foreach($result as $v){
            $total1 += $v->total;
        }

        $i =0;
      foreach($result as $v){
          $i++;
          if($i>10){
              if(!isset($top['other']['total'])){
                  $top['other']['total'] = 0;
              }

              $top['other'] = [
                  'zoneName'=>'Others',
                  'total' => $top['other']['total']+=$v->total,
                  'percentage' => number_format($top['other']['total']/$total1*100,1,'.',',').'%',
              ];
          }else{
              $top[$v->zoneId] = [
                  'zoneName' => $v->zoneText,
                  'total' => $v->total,
                  'percentage' => number_format($v->total/$total1*100,1,'.',',').'%',
              ];
          }
      }

        return View::make('dashboard')->with('nr',$nr1)->with('total',$total)->with('top',$top);

    }

    public function NormalizedUnit($productId,$qty,$unit){

            $v = Product::where('productId',$productId)->first();

            $inner = ($v['productPacking_inner']==false) ? 1:$v['productPacking_inner'];
            $unit_packing = ($v['productPacking_unit'] == false) ? 1 : $v['productPacking_unit'];

            if ($unit == 'carton') {
                $return = $qty*$inner*$unit_packing;
            }elseif($unit == 'inner') {
                $return = $qty*$unit_packing;
            }elseif($unit == 'unit') {
                $return = $qty;
            }

        return $return;
    }

    public function finalUnit($productId,$qty,$unit){

        $v = Product::where('productId',$productId)->first();

        $inner = ($v['productPacking_inner']==false) ? 1:$v['productPacking_inner'];
        $unit_packing = ($v['productPacking_unit'] == false) ? 1 : $v['productPacking_unit'];

        if ($unit == 'carton') {
            $return = $qty*$inner*$unit_packing;
        }elseif($unit == 'inner') {
            $return = $qty*$unit_packing;
        }elseif($unit == 'unit') {
            $return = $qty;
        }

        return $return;
    }
}