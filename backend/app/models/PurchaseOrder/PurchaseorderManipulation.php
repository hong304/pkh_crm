<?php

class PurchaseorderManipulation
{
    public $newPoCode = "";
    
    public function __construct($poCode = false)
    {
        $this->action = $poCode ? 'update' : 'create';
        if($this->action == 'create')
        {
           // $this->po->created_by = Auth::user()->id;
            $this->po = new Purchaseorder();
            $this->generateId();
        }else if($this->action == 'update')
        {
            //$this->po->updated_by = Auth::user()->id;
            $this->po = Purchaseorder::where('poCode', $poCode)->firstOrFail();
             if($this->po->poStatus == 99)
            {
                
                $this->status = false;
                $this->message = "This porchase order has been suspended and logically forbid to edit. This request has been recorded in audit log.";
            }
            
            $this->newPoCode = $poCode;
        }
    }
    public function generateId()
    {
        $poIdArray = Purchaseorder :: select('poCode')->orderBy('poCode','desc')->first();
        if($poIdArray == "")
        {
           // $this->newPoCode = "P00001";
            $this->newPoCode = "100001";
        }
        else 
        {
            $poIdArray = $poIdArray->toArray();
            $poId = $poIdArray['poCode'];
           // $pattern = "/[a-zA-Z]/";
         //   $num = preg_replace($pattern,'',$poId);
            $realNum = (int)$poId + 1;
         //   $this->newPoCode =  "P".str_pad($realNum, 5, "0", STR_PAD_LEFT); 
            $this->newPoCode = $realNum;
        }
        return $this->newPoCode;
    } 
    

    public function setItem($dbid = false,$productId, $productPrice, $productQtyUnit, $productQty, $itemdiscount,$itemdiscount1, $itemdiscount2, $itemallowance , $itemallowance1 ,$itemallowance2 ,$deleted, $currencyId,$remark)
	{
  
	    $this->items[] = [
                'dbid' => $dbid,
	        'productId' => $productId,
	        'unitprice' => $productPrice,
	        'productQtyUnit' => $productQtyUnit,
	        'productQty' => $productQty,
                'discount_1' => $itemdiscount,
                'discount_2'=> $itemdiscount1,
                'discount_3'=>$itemdiscount2,
                'allowance_1'=>$itemallowance,
                'allowance_2'=>$itemallowance1,
                'allowance_3' => $itemallowance2,
                'currencyId' => $currencyId,
	        'deleted' => $deleted,
                'remark' =>$remark,
	    ];
            
            
      
	    return $this;
	}

       
        
        private function __prepareItems()
	{
	    // prepare product information
	    $itemcodes = array_pluck($this->items, 'productId'); // Get values of productIds from the array
	    $raw = Product::wherein('productId', $itemcodes)->get();  // Get all products
	    $products = [];
	    foreach($raw as $p)
	    {
	        $products[$p->productId] = $p;
	    }
	 
	    // prepare existing items information
	    $dbids = array_pluck($this->items, 'dbid');
      
	    $raw = Poitem::wherein('id', $dbids)->get();
	   $invitem = [];
	    foreach($raw as $e)
	    {
	       $invitem[$e->id] = $e->toArray();
	    }
	   // check every single item
	    foreach($this->items as $key=>$i)
	    {
          
	        if($i['productId'] != "")
	        {
    	        $product = $products[$i['productId']];
    	        $selling_price = $i['sellingPrice'] = ($i['unitprice'] * (100-$i['discount_1'])/100 *  (100-$i['discount_2'])/100 * (100-$i['discount_3'])/100) - $i['allowance_1'] - $i['allowance_2'] - $i['allowance_3'];
    	        //$standard_price = $i['productStandardPrice'] = $product['productStdPrice_' . strtolower($i['productQtyUnit']['value'])];
    	        //$unit_name = $i['productUnitName'] = $product['productPackingName_' . strtolower($i['productQtyUnit']['value'])];
                $unit_name = $i['productUnitName'] = strtolower($i['productQtyUnit']['value']);
                $unit_nameMore = $i['productQtyUnit']['label'];
    	        // check if this dbid really exists
    	        if($i['dbid'] > 0 && !@$invitem[$i['dbid']])
    	        {
    	            $i['dbid'] = false;
    	        }
           
    	     
    	       
    	       
    	        // update master
    	        
    	        $this->items[$key] = $i;
	        }
	        else
	        {
	            unset($this->items[$key]);
	        }
	    }
	    return $this;
	        
            
        } 
        
     
        public function setInvoice($e)
	{
            $this->temp_invoice_information = $e;
	    return $this;
        }

      	private function __preparePos()
	{
	    if($this->action == 'create')
	    {
                //db field name = input 
	        $this->po->poCode = $this->newPoCode;
	        $this->po->poStatus = '1';
	        $this->po->poRemark = $this->temp_invoice_information['poRemark'];
	        $this->po->allowance_2 = $this->temp_invoice_information['allowance_2'];
                $this->po->allowance_1 = $this->temp_invoice_information['allowance_1'];
                $this->po->discount_2 = $this->temp_invoice_information['discount_2'];
                $this->po->discount_1 = $this->temp_invoice_information['discount_1'];
	      //  $this->po->poReference = $this->temp_invoice_information['poReference'];
	        $this->po->poDate = $this->temp_invoice_information['poDate'];
                $this->po->etaDate = $this->temp_invoice_information['etaDate'];
               // $this->po->actualDate = $this->temp_invoice_information['actualDate'];
               // $this->po->receiveDate = $this->temp_invoice_information['receiveDate'];
                $this->po->supplierCode = $this->temp_invoice_information['supplierCode'];
	        $this->po->currencyId = $this->temp_invoice_information['currencyId'];
                $this->po->poAmount = $this->temp_invoice_information['totalAmount'];
	        $this->po->created_by = Auth::user()->id;
                $this->po->updated_by = Auth::user()->id;
	        $this->po->created_at = time();
	        $this->po->updated_at = time();
        
	    }
	    elseif($this->action == 'update')
	    {
	        $this->po->poStatus = $this->temp_invoice_information['poStatus'];
	        $this->po->poRemark = $this->temp_invoice_information['poRemark'];
	        $this->po->allowance_2 = $this->temp_invoice_information['allowance_2'];
                $this->po->allowance_1 = $this->temp_invoice_information['allowance_1'];
                $this->po->discount_2 = $this->temp_invoice_information['discount_2'];
                $this->po->discount_1 = $this->temp_invoice_information['discount_1'];
	        $this->po->poReference = $this->temp_invoice_information['poReference'];
	        $this->po->poDate = $this->temp_invoice_information['poDate'];
                $this->po->etaDate = $this->temp_invoice_information['etaDate'];
                $this->po->actualDate = $this->temp_invoice_information['actualDate'];
                $this->po->receiveDate = $this->temp_invoice_information['receiveDate'];
                $this->po->supplierCode = $this->temp_invoice_information['supplierCode'];
	        $this->po->currencyId = $this->temp_invoice_information['currencyId'];
                 $this->po->poAmount = $this->temp_invoice_information['totalAmount'];
                $this->po->updated_by = Auth::user()->id;
	        $this->po->updated_at = time();
           // Invoice::where('invoiceId',$this->invoiceId)->where('version',true)->update(['f9_picking_dl'=>0,'revised' => 1,'version'=>0]);

	    }
            return $this;
	}
        
        private function __standardizeDateYmdTOUnix($date)
	{
	    $date = explode('-', $date);
	    $date = strtotime($date[2].'-'.$date[1].'-'.$date[0]);
	    return $date;
	}
        

        
       /*  public function save($i)
    {
        $fields = ['supplierCode','poDate','etaDate','actualDate','receiveDate','discount_1','discount_2','allowance_1','allowance_2','poRemark','poStatus'];
        foreach($fields as $f)
        {
            $this->po->$f = $i[$f];
        }
        
        $this->po->poCode = $this->newPoCode;
        $this->po->save();
        return $this->newPoCode;
    }*/
        public function deleteSave()
        {
            $this->po->poStatus = 99;
            $this->po->save();
        }
        
        public function save()
	{
	    // first validate and prepare items
	    $this->__prepareItems();
	    
	    // second prepare invoices
	    $this->__preparePos();
              // ok, save invoice first
    	    
	    // if this requests has item, save all
	    if(count($this->items) > 0)
	    {
	        //dd($this->im->deliveryDate, strtotime("today 00:00"), strtotime("today 23:59"));
            $this->po->save();
    	    // then, save all items one by one
    	    foreach($this->items as $i)
    	    {
            
                if($i['dbid'])
    	        {
    	            $item = Poitem::where('id', $i['dbid'])->first();
    	            $item->updated_at = time();
                    $item->updated_by = Auth::user()->id;
    	        }
    	        else
    	        {
    	            $item = new Poitem();
    	            $item->created_at = $item->updated_at = time();
                    $item->updated_by = $item->created_by = Auth::user()->id;
    	        }
    	        
    	        $item->created_at = $item->updated_at = time();
    	        $item->poCode = $this->newPoCode;
    	        $item->productId = $i['productId'];
    	        $item->productQtyUnit = $i['productQtyUnit']['value'];
                $item->productUnitName = $i['productQtyUnit']['label'];
    	        $item->productQty = $i['productQty'];
                $item->discount_1 = $i['discount_1'];
                $item->discount_2 = $i['discount_2'];
                $item->discount_3 = $i['discount_3'];
                $item->allowance_1 = $i['allowance_1'];
                $item->allowance_2 = $i['allowance_2'];
                $item->allowance_3 = $i['allowance_3'];
                $item->currencyId = $i['currencyId'];
                $item->unitprice = $i['unitprice'];
                $item->remark = $i['remark'];
                
            
    	    //    $item->productStandardPrice = $i['productStandardPrice'];
    	    //    $item->productUnitName = $i['productUnitName'];
    	    //   $item->approvedSupervisorId = $i['approvedSupervisorId'];

    	       if($i['deleted'] == '0' && $i['productQty'] != 0)
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
    	        'poCode' => $this->newPoCode,
    	    ];
	    }
	    
	    return [
	        'result' => false,
	        'poCode' => 0,
                'message' => '未有下單貨品',
	    ];
	    
	}
        
        //Receive front end array ,then use foreach to loop via them , then save it one by one
}

