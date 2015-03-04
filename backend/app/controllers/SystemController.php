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
          'invoiceStatus' => Config::get('invoiceStatus'),   
        ];
        return Response::json($systeminfo);
    }

}