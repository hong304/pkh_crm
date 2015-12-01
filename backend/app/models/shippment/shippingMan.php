<?php

/* 
 * To change this license header, choose License Headers in Project Properties.
 * To change this template file, choose Tools | Templates
 * and open the template in the editor.
 */
class shippingMan
{
    public $action;
    public $newShipId;
    public function __construct($shippingId = false)
    {
        $this->action = $shippingId ? 'update' : 'create';
        if($this->action == "create")
        {
            $this->generateId();
            $this->sh = new Shipping();
        }else if($this->action == "update")
        {
            $this->sh = Shipping :: where('shippingId' , $shippingId)->firstOrFail();
            $this->newShipId = $shippingId;
        }
        
    }
    
    public function generateId()
    {
        $shipment = Shipping::select('shippingId')->orderby('shippingId','desc')->get();
        if(count($shipment) == 0)
        {
            $this->newShipId = 10000;
        }else 
        {
            $this->newShipId = (int)$shipment[0]['shippingId'] + 1;
        }
        
        return  $this->newShipId;
    }
    
   public function prepare_ship()
    {
        if($this->action == 'create')
	{
	    $this->sh->shippingId = $this->newShipId;
	    $this->sh->poCode = $this->temp_ship_information['poCode'];
            $this->sh->supplierCode = $this->temp_ship_information['supplierCode'];
            $this->sh->carrier= $this->temp_ship_information['carrier'];
            $this->sh->etaDate= $this->temp_ship_information['etaDate'];
            if($this->temp_ship_information['actualDate'] !== '')
                $this->sh->actualDate= $this->temp_ship_information['actualDate'];
            $this->sh->departure_date= $this->temp_ship_information['departure_date'];
            $this->sh->vessel= $this->temp_ship_information['vessel'];
            $this->sh->voyage= $this->temp_ship_information['voyage'];
            $this->sh->bl_number= $this->temp_ship_information['bl_number'];
            $this->sh->pol = $this->temp_ship_information['pol'];
            $this->sh->pod = $this->temp_ship_information['pod'];
            $this->sh->container_numbers = $this->temp_ship_information['container_numbers'];
            $this->sh->fsp = $this->temp_ship_information['fsp'];
            $this->sh->remark = $this->temp_ship_information['remark'];
            $this->sh->status = 1;
            $this->sh->feight_payment = $this->temp_ship_information['feight_payment'];
            $this->sh->shipCompany = $this->temp_ship_information['shipCompany'];
            $this->sh->created_by = Auth::user()->id;
            $this->sh->updated_by = Auth::user()->id;
	    $this->sh->created_at = time();
	    $this->sh->updated_at = time();
        }else if($this->action == 'update')
        {
            $this->sh->poCode = $this->temp_ship_information['poCode'];
            $this->sh->supplierCode = $this->temp_ship_information['supplierCode'];
            $this->sh->carrier= $this->temp_ship_information['carrier'];
            $this->sh->etaDate= $this->temp_ship_information['etaDate'];
            if($this->temp_ship_information['actualDate'] !== '')
                $this->sh->actualDate= $this->temp_ship_information['actualDate'];
            $this->sh->departure_date= $this->temp_ship_information['departure_date'];
            $this->sh->vessel= $this->temp_ship_information['vessel'];
            $this->sh->voyage= $this->temp_ship_information['voyage'];
            $this->sh->bl_number= $this->temp_ship_information['bl_number'];
            $this->sh->pol = $this->temp_ship_information['pol'];
            $this->sh->pod = $this->temp_ship_information['pod'];
            $this->sh->container_numbers = $this->temp_ship_information['container_numbers'];
            $this->sh->fsp = $this->temp_ship_information['fsp'];
            $this->sh->remark = $this->temp_ship_information['remark'];
            $this->sh->status = $this->temp_ship_information['status'];
            $this->sh->shipCompany = $this->temp_ship_information['shipCompany'];
            $this->sh->feight_payment = $this->temp_ship_information['feight_payment'];
              $this->sh->updated_by = Auth::user()->id;
                $this->sh->updated_at = time();
        }
    }
	
	public function setDeleteShip()
	{
		$this->sh->status = 99;
		$this->sh->save();
		 return [
                'result' => true,
                'action' => 'deleted',
    	        'shipCode' => $this->newShipId,
    	    ];
	}
        
	

	
    
    public function prepare_items() // use to clear the item which does not contain containerId
    {

         foreach($this->items as $k=>$v)
         {
             if($v['containerId'] != '')
             {
                 $this->items[$k] = $v;
             } else
	     {
	         unset($this->items[$k]);
	      }
         }
    }

    public function save()
    {
       
        $this->prepare_ship();
        
        $this->prepare_items();
   
        if(count($this->items) > 0)
        {
            $this->sh->save();
     
            foreach($this->items as $i)
    	    {
                if($i['dbid'])
    	        {
    	            $item = Shippingitem::where('id', $i['dbid'])->first();
    	            $item->updated_at = time();
                    $item->updated_by = Auth::user()->id;

    	        }
    	        else
    	        {
    	            $item = new Shippingitem();
    	            $item->created_at = $item->updated_at = time();
                    $item->updated_by = $item->created_by = Auth::user()->id;
    	        }
    	        
    	        $item->created_at = $item->updated_at = time();
    	        $item->shippingId = $this->newShipId;
    	        $item->containerId = $i['containerId'];
    	        $item->container_Num = $i['container_Num'];
                $item->remark = $i['remark'];
    	       // $item->container_receiveDate = $i['container_receiveDate']; // Dont put receiveDate here , since db will default the 00-00-0000 here
                $item->container_size = $i['container_size'];
                $item->serial_no = $i['serial_no'];
                $item->container_weight = $i['container_weight'];
                $item->container_capacity = $i['container_capacity'];
                $item->sale_method = $i['sale_method'];
                $item->cost_00 = $i['cost_00'];
                $item->cost_01 = $i['cost_01'];
                $item->cost_02 = $i['cost_02'];
                $item->cost_03 = $i['cost_03'];
                $item->cost_04 = $i['cost_04'];
                $item->cost_05 = $i['cost_05'];
                $item->cost_06 = $i['cost_06'];
                $item->cost_07 = $i['cost_07'];
                $item->cost_08 = $i['cost_08'];
                $item->cost_09 = $i['cost_09'];
              
                
            
    	    //    $item->productStandardPrice = $i['productStandardPrice'];
    	    //    $item->productUnitName = $i['productUnitName'];
    	    //   $item->approvedSupervisorId = $i['approvedSupervisorId'];

    	       if($i['deleted'] == 0 && $i['containerId'] != "")
    	        {
    	            $item->save();
    	        }
    	    }

      //      $in = Purchaseorder ::where('poCode',$this->poCode)->with('invoiceItem')->first();
         //   $in->amount = $in->invoiceTotalAmount;
        //    $in->save();

    	    return [
                'result' => true,
                'action' => $this->action,
    	        'shipCode' => $this->newShipId,
    	    ];
        }
        
         return [
	        'result' => false,
	        'shipCode' => 0,
                'message' => '未有下單貨品',
	    ];
        
    }
    
    
    //make an array
    public function setItems($dbid,$containerId,$serial_no,$container_size,$container_Num,$container_weight,$container_capacity,$remark,$deleted,$sale_method,$cost_00,$cost_01,$cost_02,$cost_03,$cost_04,$cost_05,$cost_06,$cost_07,$cost_08,$cost_09)
    {
         $this->items[] = [
                'dbid' => $dbid,
	        'containerId' => $containerId,
                'serial_no' => $serial_no,
                'container_size' => $container_size,
                'container_Num' => $container_Num,
                'container_weight' => $container_weight,
                'container_capacity' => $container_capacity,
                'remark' => $remark,
                'deleted' => $deleted,
                'sale_method'=>$sale_method,
                'cost_00' => $cost_00,
                'cost_01' => $cost_01,
                'cost_02' => $cost_02,
                'cost_03' => $cost_03,
                'cost_04' => $cost_04,
                'cost_05' => $cost_05,
                'cost_06' => $cost_06,
                'cost_07' => $cost_07,
                'cost_08' => $cost_08,
                'cost_09' => $cost_09,
                
         ];
	    return $this->items;
    }
    
    public function setOtherItems($dbid,$container_receiveDate)
    {
        $this->itemsother[] = [
                'id' => $dbid,
	        'container_receiveDate' => $container_receiveDate,
              
         ];
        
	    return $this->itemsother;
    }
    
    public function saveOtherItems($conainerdbid)
    {
        $shippingItem = Shippingitem :: where('id',$conainerdbid)->first(); //check the dbid exists or not
        if(count($shippingItem->toArray()) > 0)
        {
    	    $shippingItem->updated_at = time();
            $shippingItem->updated_by = Auth::user()->id;
          //  $item->container_size = $i['container_size'];
            foreach($this->itemsother as $k=>$v)
            {
                foreach($v as $kk=>$vv)
                {
                    $shippingItem->$kk = $vv;
                }
            }
            $shippingItem->save();
            return [
	        'result' => true,
	        'containerdbid' => $conainerdbid,
	    ];
        }
           return [
	        'result' => false,
	        'containerdbid' => '',
	    ];
    }
    
    
     public function setShip($e)  //set shipping header
    {
        $this->temp_ship_information = $e;
	    return $this;
    }
	
    
  
    

}
