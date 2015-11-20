<?php

class InvoiceManipulation {

    public $invoiceId = "";
    public $status = true;
    public $message = "";
    public $action = "";
    public $im = "";
    public $approval = false;
    
    public function __construct($invoiceId = false, $timer = false)
    {
        $this->action = $invoiceId ? 'update' : 'create';
                
        if($this->action == 'create')
        {
            $this->im = new Invoice();
        }
        elseif($this->action == 'update')
        {
            // check if this invoice exists
            $this->im = Invoice::where('invoiceId', $invoiceId)->firstOrFail();
            
            if($this->im->invoiceStatus == 99)
            {
                
                $this->status = false;
                $this->message = "This invoice has been suspended and logically forbid to edit. This request has been recorded in audit log.";
            }
            
            $this->invoiceId = $invoiceId;
        }
    }
    
    public function generateInvoiceId()
	{
	    $invoiceLength = 6;
	    
	    $prefix = date("\Iym-");
	    $lastInvoice = Invoice::withTrashed()->where('invoiceId', 'like', $prefix.'%')->limit(1)->orderBy('invoiceId', 'Desc')->first();
	    	    
	    if(count($lastInvoice) > 0)
	    {
	        // extract latter part
	        $i = explode('-', $lastInvoice->invoiceId);
	        $nextId = (int) $i[1] + 1;
	        $nextInvoiceDate = $prefix . str_pad($nextId, $invoiceLength, '0', STR_PAD_LEFT);
	    }
	    else
	    {
	        $nextInvoiceDate = $prefix.str_pad('1', $invoiceLength, '0', STR_PAD_LEFT);
	    }
	    
	    $this->invoiceId = $nextInvoiceDate;
	    
	    return $this;	    
	}
	
	private function __standardizeDateYmdTOUnix($date)
	{
	    $date = explode('-', $date);
	    $date = strtotime($date[2].'-'.$date[1].'-'.$date[0]);
	    return $date;
	}
	
	public function setInvoice($e)
	{
	    # First to validate all fields
//	    $orderrules_idd_date_override = Auth::user()->can('allow_create_sameday_invoice') ? 'after:yesterday' : 'after:today';
        
        // updated on 2015.03.11 by Cyrus ref PKHC-18 to branch PKHC-18-allow-back-day-order
        
	    $orderrules = [
	        'clientId' => ['required', 'exists:Customer,customerId'],
	        'invoiceDate' => ['required'],
	        'deliveryDate' => ['required'],
	        'dueDate' => ['required'],
	        'status' => [''],
	        'referenceNumber' => [''],
	        'zoneId' => ['numeric'],
	        'route' => ['numeric'],
	        'address' => ['required'],
	    ];
	    
	    $orderValidation = Validator::make($e, $orderrules);
	    if($orderValidation->fails())
	    {
	        // if invoice is problematic, kill the user
	        $this->status = false;
	        $this->message = $orderValidation->messages()->first();
	    }
	    
	    $this->temp_invoice_information = $e;
	    
	    return $this;
	}
	
	public function setItem($dbid = false, $productId, $productPrice, $productQtyUnit,$productLocation, $productQty, $productDiscount = false, $productRemark = false, $deleted,$productPacking)
	{
	    $this->items[] = [
	        'dbid' => $dbid,
	        'productId' => $productId,
	        'productPrice' => $productPrice,
	        'productQtyUnit' => $productQtyUnit,
            'productLocation' => $productLocation,
            'productPacking' => $productPacking,
	        'productQty' => $productQty,
	        'productDiscount' => $productDiscount,
	        'productRemark' => $productRemark,
	        'deleted' => $deleted,
	    ];
	    
	    return $this;
	}
	
	private function __prepareItems()
	{
	    // prepare product information
	    $itemcodes = array_pluck($this->items, 'productId');
	    $raw = Product::wherein('productId', $itemcodes)->get();
	    $products = [];
	    foreach($raw as $p)
	    {
	        $products[$p->productId] = $p;
	    }
	    
	    // prepare existing items information
	    $dbids = array_pluck($this->items, 'dbid');
	    $raw = InvoiceItem::wherein('invoiceItemId', $dbids)->get();
	    $invitem = [];
	    foreach($raw as $e)
	    {
	        $invitem[$e->invoiceItemId] = $e->toArray();
	    }
	    
	    // check every single item
	    foreach($this->items as $key=>$i)
	    {
	        if($i['productId'] != "")
	        {
    	        $product = $products[$i['productId']];
    	        $selling_price = $i['sellingPrice'] = $i['productPrice'] * (100-$i['productDiscount'])/100;
    	        $standard_price = $i['productStandardPrice'] = $product['productStdPrice_' . strtolower($i['productQtyUnit']['value'])];
    	        $unit_name = $i['productUnitName'] = $product['productPackingName_' . strtolower($i['productQtyUnit']['value'])];
    	        
    	        // check if this dbid really exists
    	        if($i['dbid'] > 0 && !@$invitem[$i['dbid']])
    	        {
    	            $i['dbid'] = false;
    	        }
    	        
    	        // check product price
    	        
    	        // we might need approval. check if this item has been approved before
    	        // system (id = 27) approved indicate it is an automatic approve.
    	        // in any circumstance we need to validate it again    	        
    	        if((!Auth::user()->can('allow_by_pass_invoice_approval')) AND ($selling_price < $standard_price) AND  $i['deleted'] == '0')
    	        {
    	            
    	            // approved before?
    	            if(isset($invitem[$i['dbid']]) AND $invitem[$i['dbid']]['approvedSupervisorId'] != 0)
    	            {
    	                // has change?
    	                if($selling_price != $invitem[$i['dbid']]['productPrice'])
    	                {
    	                    $this->approval = true;
    	                    $i['approvedSupervisorId'] = 0;
    	                }
    	                else
    	                {
    	                    $i['approvedSupervisorId'] = $invitem[$i['dbid']]['approvedSupervisorId'];
    	                }
    	            }
    	            else
    	            {
    	                $this->approval = true;
    	                $i['approvedSupervisorId'] = 0;
    	            }
    	        }
    	        else
    	        {
    	            $i['approvedSupervisorId'] = 27;
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
	
	private function __prepareInvoices()
	{
	    if($this->action == 'create')
	    {
            Customer::where('customerId',$this->temp_invoice_information['clientId'])->update(['unlock'=>0]);

            if(isset($this->temp_invoice_information['invoiceNumber']))
                $this->invoiceId = $this->temp_invoice_information['invoiceNumber'];
           else
               $this->generateInvoiceId();



	        $this->im->invoiceId = $this->invoiceId;
	        $this->im->invoiceType = 'Salesman';
	        $this->im->zoneId = $this->temp_invoice_information['zoneId'];
            $this->im->receiveMoneyZone = $this->temp_invoice_information['zoneId'];
	        $this->im->customerId = $this->temp_invoice_information['clientId'];
            $this->im->invoiceRemark = $this->temp_invoice_information['invoiceRemark'];
	        $this->im->routePlanningPriority = $this->temp_invoice_information['route'];
	        $this->im->deliveryTruckId = 0;
	        $this->im->invoiceCurrency = 'HKD';
	        $this->im->customerRef = $this->temp_invoice_information['referenceNumber'];
	        $this->im->invoiceDate = $this->__standardizeDateYmdTOUnix($this->temp_invoice_information['deliveryDate']);
	        $this->im->deliveryDate = $this->__standardizeDateYmdTOUnix($this->temp_invoice_information['deliveryDate']);
	        $this->im->dueDate = $this->__standardizeDateYmdTOUnix($this->temp_invoice_information['dueDate']);
	        $this->im->paymentTerms = $this->temp_invoice_information['paymentTerms'];
            $this->im->shift = $this->temp_invoice_information['shift'];
	        $this->im->created_by = Auth::user()->id;
            $this->im->updated_by = Auth::user()->id;
	        $this->im->invoiceStatus = $this->determineStatus();
	        $this->im->invoiceDiscount = @$this->temp_invoice_information['discount'];
            $this->im->amount = $this->temp_invoice_information['amount'];
	        $this->im->created_at = time();
	        $this->im->updated_at = time();
	    }
	    elseif($this->action == 'update')
	    {
            $this->im->zoneId = $this->temp_invoice_information['zoneId'];
            $this->im->receiveMoneyZone = $this->temp_invoice_information['zoneId'];
            $this->im->customerId = $this->temp_invoice_information['clientId'];
            $this->im->routePlanningPriority = $this->temp_invoice_information['route'];
            $this->im->invoiceDiscount = $this->temp_invoice_information['discount'];
            $this->im->invoiceRemark = $this->temp_invoice_information['invoiceRemark'];
            $this->im->shift = $this->temp_invoice_information['shift'];
            $this->im->paymentTerms = $this->temp_invoice_information['paymentTerms'];
	        $this->im->customerRef = $this->temp_invoice_information['referenceNumber'];
	        $this->im->invoiceDate = $this->__standardizeDateYmdTOUnix($this->temp_invoice_information['deliveryDate']);
	        $this->im->deliveryDate = $this->__standardizeDateYmdTOUnix($this->temp_invoice_information['deliveryDate']);
	        $this->im->dueDate = $this->__standardizeDateYmdTOUnix($this->temp_invoice_information['dueDate']);
	        $this->im->invoiceStatus = $this->determineStatus();
            $this->im->updated_by = Auth::user()->id;
            $this->im->amount = $this->temp_invoice_information['amount'];

            if($this->im->version>0)
                Invoice::where('invoiceId',$this->invoiceId)->update(['f9_picking_dl'=>0,'revised' => 1,'version'=>0]);

	    }
	}
	
	public function determineStatus()
	{

        if($this->temp_invoice_information['status'] == '98')
            return '98';
        else if($this->temp_invoice_information['status'] == '97')
            return '97';
        else if($this->temp_invoice_information['status'] == '96')
            return '96';

        return $this->approval ? '1' : '2';

	    $isBackdayOrder = $this->__standardizeDateYmdTOUnix($this->temp_invoice_information['deliveryDate']) <= strtotime("today 00:00")-1;
	    
	    if($isBackdayOrder)
	    {
            return '2';
	      /*  switch($this->temp_invoice_information['paymentTerms'])
	        {
	            case '1':
	                return '30';
	                break;
	            case '2':
	                return '20';
	                break;
	            default :
	                return '0';
	        }*/
	    } else{
            return $this->approval ? '1' : '2';
	         }
	    
	    return '0';
	}

    public function saveInvoice(){
        try {
            $this->im->save();
        } catch (Illuminate\Database\QueryException $e) {
            $this->generateInvoiceId();
            $this->im->invoiceId = $this->invoiceId;
            $this->saveInvoice();
        }
    }

	public function save()
	{
	    // first validate and prepare items
	    $this->__prepareItems();
	    
	    // second prepare invoices
	    $this->__prepareInvoices();
	    
	    // if this requests has item, save all
	    if(count($this->items) > 0 && $this->status == true)
	    {
    	    // ok, save invoice first
            $this->saveInvoice();

    	    // save the client
    	    $client = Customer::where('customerId', $this->im->customerId)->first();
            $client->timestamps = false;
	        if($this->im->deliveryDate > strtotime("today 23:59"))
    	    {
    	        $client->tomorrow = '1';
    	    }
    	    elseif($this->im->deliveryDate >= strtotime("today 00:00"))
    	    {
    	        $client->today = '1';
    	    }
    	    
    	    $client->save();
    	    
    	    
    	    // then, save all items one by one
    	    foreach($this->items as $i)
    	    {

              //  pd($i);

    	        if($i['dbid'])
    	        {
    	            $item = InvoiceItem::where('invoiceItemId', $i['dbid'])->first();
                   // pd($item);
    	            //$item->updated_at = time();
    	        }
    	        else
    	        {
    	            $item = new InvoiceItem();
    	            $item->created_at = time();
    	        }

                $productMap = ProductSearchCustomerMap::where('productId',$i['productId'])->where('customerId',$this->im->customerId)->first();
               //pd($productMap);

                if($productMap==null){
                    $productMap = new ProductSearchCustomerMap();
                    $productMap->customerId = $this->im->customerId;
                    $productMap->productId = $i['productId'];
                    $productMap->sumation = 1;
                    $productMap->save();
                }else{
                    $productMap->sumation += 1;
                    $productMap->save();
                }

                //update last item price
                if($this->temp_invoice_information['status'] != '98' && $this->temp_invoice_information['status'] != '96' && $this->temp_invoice_information['status'] != '97'  && $i['deleted'] == '0'){

                    $lastitem = lastitem::where('customerId',$this->im->customerId)->where('productId',$i['productId'])->first();
                    if($lastitem==null){
                        $lastitem = new lastitem();
                        $lastitem->customerId = $this->im->customerId;
                        $lastitem->productId = $i['productId'];
                        $lastitem->org_price = $i['productStandardPrice'];
                        $lastitem->unit_level = $i['productQtyUnit']['value'];
                        $lastitem->unit_text = $i['productUnitName'];
                        $lastitem->price = $i['productPrice'];
                        $lastitem->qty = $i['productQty'];
                        $lastitem->discount = $i['productDiscount'];
                    }else{
                        $lastitem->unit_level = $i['productQtyUnit']['value'];
                        $lastitem->unit_text = $i['productUnitName'];
                        $lastitem->price = $i['productPrice'];
                        $lastitem->qty = $i['productQty'];
                        $lastitem->discount = $i['productDiscount'];
                        $lastitem->updated_at = time();;
                    }
                    $lastitem->deliveryDate = $this->temp_invoice_information['deliveryDate'];
                    $lastitem->save();
                }

    	        $item->invoiceId = $this->invoiceId;
    	        $item->productId = $i['productId'];
    	        $item->productQtyUnit = $i['productQtyUnit']['value'];
                $item->productLocation = $i['productLocation'];
    	        $item->productQty = $i['productQty'];
    	        $item->productPrice = $i['productPrice'];
    	        $item->productDiscount = $i['productDiscount'];
    	        $item->productRemark = $i['productRemark'];
    	        $item->productStandardPrice = $i['productStandardPrice'];
    	        $item->productUnitName = trim($i['productUnitName']);
    	        $item->approvedSupervisorId = $i['approvedSupervisorId'];

    	       /* if($i['dbid'] && $i['deleted'] == '1')
    	        {
    	            $item->delete();
    	        }
    	        else
    	       */
                if($i['dbid'])
                    if($item->isDirty()){
                        foreach($item->getDirty() as $attribute => $value) {
                              if (!in_array($attribute, array('backgroundcode'))) {

                                  if($i['productId'] == 218) {
                                      $invoiceitembatchs = invoiceitemBatch::where('invoiceItemId', $item->getOriginal('invoiceItemId'))->where('productId', $item->getOriginal('productId'))->get();
                                      foreach ($invoiceitembatchs as $k1 => $v1) {
                                          $receivings = Receiving::where('productId', $v1->productId)->where('receivingId', $v1->receivingId)->first();
                                          $receivings->good_qty += $v1->unit;
                                          $receivings->save();
                                      }
                                  }

                                  $item->delete();
                                  $item = new InvoiceItem();
                                  $item->invoiceId = $this->invoiceId;
                                  $item->productId = $i['productId'];
                                  $item->productQtyUnit = $i['productQtyUnit']['value'];
                                  $item->productLocation = $i['productLocation'];
                                  $item->productQty = $i['productQty'];
                                  $item->productPrice = $i['productPrice'];
                                  $item->productDiscount = $i['productDiscount'];
                                  $item->productRemark = $i['productRemark'];
                                  $item->productStandardPrice = $i['productStandardPrice'];
                                  $item->productUnitName = trim($i['productUnitName']);
                                  $item->approvedSupervisorId = $i['approvedSupervisorId'];
                                  $item->created_at = time();
                            }
                        }
                    }

    	       if($i['deleted'] == '0' && $i['productQty'] != 0)
    	        {
                    $item->save();

                    if($i['productId'] == 218){
                        $normalizedUnit = $this->normalizedUnit($i);
                        $packingSize = $this->packingSize($i);
                        $undeductUnit = $normalizedUnit;

                        if($undeductUnit < 0){
                            $receivings = Receiving::where('productId',$i['productId'])->orderBy('expiry_date','desc')->first();
                            $receivings->good_qty -= $undeductUnit;
                            $receivings->save();
                            $invoiceitembatchs = new invoiceitemBatch();
                            $invoiceitembatchs->invoiceItemId = $item->invoiceItemId;
                            $invoiceitembatchs->unit = $undeductUnit;
                            $invoiceitembatchs->productId = $i['productId'];
                            $invoiceitembatchs->receivingId = $receivings->receivingId;
                            $invoiceitembatchs->save();
                        }


                        while($undeductUnit > 0 ){
                            $receivings = Receiving::where('productId',$i['productId'])->where('good_qty','>=',$packingSize)->orderBy('expiry_date','asc')->first();

                            if($undeductUnit > $receivings->good_qty){
                                $ava_qty = ($receivings->good_qty - ($receivings->good_qty % $packingSize));
                                $undeductUnit -= $ava_qty;
                            }else{
                                $ava_qty = $undeductUnit;
                                $undeductUnit = 0;
                            }

                            $receivings->good_qty -= $ava_qty;
                            $receivings->save();

                            $invoiceitembatchs = new invoiceitemBatch();
                            $invoiceitembatchs->invoiceItemId = $item->invoiceItemId;
                            $invoiceitembatchs->unit = $ava_qty;
                            $invoiceitembatchs->productId = $i['productId'];
                            $invoiceitembatchs->receivingId = $receivings->receivingId;
                            $invoiceitembatchs->save();
                        }
                    }
    	        }
    	    }

           // $in = Invoice::where('invoiceId',$this->invoiceId)->with('invoiceItem')->first();
          //  $in->amount = $in->invoiceTotalAmount;
         //   $in->save();

    	    // prepare invoice image
            if($this->temp_invoice_information['print'])
    	        $this->__queueInvoiceImage();

    	    return [
                'action' => $this->action,
    	        'result' => $this->status,
    	        'status' => $this->im->invoiceStatus,
    	        'invoiceNumber' => $this->invoiceId,
    	        'message' => ($this->message == "" ? '' : $this->message),
    	    ];
	    }
	    
	    return [
	        'result' => false,
	        'status' => 0,
	        'invoiceNumber' => 0,
	        'invoiceItemIds' => 0,
	        'message' => ($this->message == "" ? '未有下單貨品' : $this->message),
	    ];
	    
	}

    public function generatePrintInvoiceImage($invoice_id)
    {
        $e = Invoice::where('invoiceId', $invoice_id)->first();
        $image = new InvoiceImage();

        // generate print version
        $files = $image->generate($invoice_id, true)->saveAll();

        $j = 0;
        $file = [];

        foreach($files as $f)
        {
            $file['deliveryDate'][$j] = $f['deliveryDate'];
           $file['print_storage'][$j] = $f['filename'];
          //  $file['print_storage'][$j] = $f['fullpath'];
            $j++;
        }


        $e->invoicePrintImage = serialize($file);
        $e->save();
        // pd($files);

    }



    public function generateInvoicePDF($invoice_id,$instructor)
    {
       // syslog(LOG_DEBUG, print_r(['user'=>Auth::user(), 'server'=> $_SERVER, 'get'=>$_GET, 'post'=>$_POST], true));
        Auth::onceUsingId("27");

       // $pdf = new InvoicePdf(); //convert png to pdf
      //  $pdf_file = $pdf->generate($invoice_id);



        // update invoice entry
       // if($pdf_file['zoneId'] != "")
      //  {
            $x = Invoice::where('invoiceId', $invoice_id)->first();
          //  $x->invoicePrintPDF = $pdf_file['path'];
         //   $x->save();



          //  $url = $_SERVER['backend'].substr($pdf_file['path'],-24);

            $oldQs = PrintQueue::where('invoiceId', $invoice_id)
                ->wherein('status', ['queued', 'fast-track','dead:pending'])
                ->get();
            if($oldQs)
            {
                foreach($oldQs as $oldQ)
                {
                    $oldQ->status = "dead:regenerated";
                    $oldQ->save();
                }
            }
           // $flag=false;
            $q = new PrintQueue();
            //$q->file_path = $url;
            $q->target_path = $x->zoneId;
            $q->insert_time = time();
            $q->invoiceStatus = $x->invoiceStatus;
            if($x->invoiceStatus == '1'){
                $q->status = "dead:pending";
            }else
                $q->status = "queued";
            $q->invoiceId = $invoice_id;

          /*  if($x->deliveryDate == strtotime(date( "Y-m-d H:i:s",mktime(0, 0, 0))) && date('G') < 12 && $x->invoiceStatus != '1') {
                $q->target_time = time();
                $q->status = "downloaded;passive";
                $flag = true;
            }else*/
                $q->target_time = strtotime("tomorrow 3am");

           // $q->created_by = $instructor;
            $q->save();

         /*   if($flag){
                $jobs = PrintQueue::where('invoiceId', $invoice_id)->lists('invoiceId');
                if($jobs){
                    $j = new PrintQueueController();
                    $j->mergeImage($jobs);
                }
            }*/

      //  }


        // queue if instant print job
        /*
        if(Input::get('printInstant'))
        {
            $task = new PushTask('/queue/send-print-job-to-printer.queue', ['jobId' => $q->job_id]);
            $task_name = $task->add('invoice-printing-factory');
        }
        */
    }

    private function __queueInvoiceImage()
	{

        $this->generatePrintInvoiceImage($this->invoiceId);

        $this->generateInvoicePDF($this->invoiceId,Auth::user()->id);

	}

    public function normalizedUnit($i){
        $inner = ($i['productPacking']['inner']) ? $i['productPacking']['inner']:1;
        $unit = ($i['productPacking']['unit']) ? $i['productPacking']['unit']:1;

        if($this->temp_invoice_information['status'] == 98){
            if($i['productQtyUnit']['value'] == 'carton')
                $real_normalized_unit =  $i['productQty']*$inner*$unit*-1;
            else if($i['productQtyUnit']['value'] == 'inner')
                $real_normalized_unit =   $i['productQty']*$unit*-1;
            else
                $real_normalized_unit =  $i['productQty'] * -1;
        }else{
            if($i['productQtyUnit']['value'] == 'carton')
                $real_normalized_unit =  $i['productQty']*$inner*$unit;
            else if($i['productQtyUnit']['value'] == 'inner')
                $real_normalized_unit =   $i['productQty']*$unit;
            else
                $real_normalized_unit =  $i['productQty'];
        }
        return $real_normalized_unit;
    }

    public function packingSize($i){

            $inner = ($i['productPacking']['inner']) ? $i['productPacking']['inner']:1;
            $unit = ($i['productPacking']['unit']) ? $i['productPacking']['unit']:1;

            if($this->temp_invoice_information['status'] == 98){
                if($i['productQtyUnit']['value'] == 'carton')
                    $real_normalized_unit =  $inner*$unit*-1;
                else if($i['productQtyUnit']['value'] == 'inner')
                    $real_normalized_unit =   $unit*-1;
                else
                    $real_normalized_unit =   $i['productQty'] * -1;
            }else{
                if($i['productQtyUnit']['value'] == 'carton')
                    $real_normalized_unit =  $inner*$unit;
                else if($i['productQtyUnit']['value'] == 'inner')
                    $real_normalized_unit =   $unit;
                else
                    $real_normalized_unit =  $i['productQty'];
            }
            return $real_normalized_unit;

    }
	
}