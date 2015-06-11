﻿<?php

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
            $this->generateInvoiceId();
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
	
	public function setItem($dbid = false, $productId, $productPrice, $productQtyUnit,$productLocation, $productQty, $productDiscount = false, $productRemark = false, $deleted)
	{
	    $this->items[] = [
	        'dbid' => $dbid,
	        'productId' => $productId,
	        'productPrice' => $productPrice,
	        'productQtyUnit' => $productQtyUnit,
            'productLocation' => $productLocation,
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
	        $this->im->invoiceId = $this->invoiceId;
	        $this->im->invoiceType = 'Salesman';
	        $this->im->zoneId = $this->temp_invoice_information['zoneId'];
	        $this->im->customerId = $this->temp_invoice_information['clientId'];
            $this->im->invoiceRemark = $this->temp_invoice_information['invoiceRemark'];
	        $this->im->routePlanningPriority = $this->temp_invoice_information['route'];
            if($this->temp_invoice_information['status'] == '98' || $this->temp_invoice_information['status'] == '97')
                $this->im->return = 1;
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
	        $this->im->created_at = time();
	        $this->im->updated_at = time();
	    }
	    elseif($this->action == 'update')
	    {

            $this->im->zoneId = $this->temp_invoice_information['zoneId'];
            $this->im->customerId = $this->temp_invoice_information['clientId'];
            $this->im->routePlanningPriority = $this->temp_invoice_information['route'];
            $this->im->invoiceDiscount = $this->temp_invoice_information['discount'];
            $this->im->invoiceRemark = $this->temp_invoice_information['invoiceRemark'];
            $this->im->shift = $this->temp_invoice_information['shift'];

	        $this->im->customerRef = $this->temp_invoice_information['referenceNumber'];
	        $this->im->invoiceDate = $this->__standardizeDateYmdTOUnix($this->temp_invoice_information['deliveryDate']);
	        $this->im->deliveryDate = $this->__standardizeDateYmdTOUnix($this->temp_invoice_information['deliveryDate']);
	        $this->im->dueDate = $this->__standardizeDateYmdTOUnix($this->temp_invoice_information['dueDate']);
	        $this->im->invoiceStatus = $this->determineStatus();
            $this->im->updated_by = Auth::user()->id;

            Invoice::where('invoiceId',$this->invoiceId)->where('version',true)->update(['f9_picking_dl'=>0,'revised' => 1,'version'=>0]);

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
	
	public function save()
	{
	    // first validate and prepare items
	    $this->__prepareItems();
	    
	    // second prepare invoices
	    $this->__prepareInvoices();
	    
	    // if this requests has item, save all
	    if(count($this->items) > 0 && $this->status == true)
	    {
	        //dd($this->im->deliveryDate, strtotime("today 00:00"), strtotime("today 23:59"));
    	    // ok, save invoice first
    	    $this->im->save();
    	    
    	    // save the client
    	    $client = Customer::where('customerId', $this->im->customerId)->first();
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
    	        if($i['dbid'])
    	        {
    	            $item = InvoiceItem::where('invoiceItemId', $i['dbid'])->first();
    	            $item->updated_at = time();
    	        }
    	        else
    	        {
    	            $item = new InvoiceItem();
    	            $item->created_at = $item->updated_at = time();
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

    	        $item->invoiceId = $this->invoiceId;
    	        $item->productId = $i['productId'];
    	        $item->productQtyUnit = $i['productQtyUnit']['value'];
                $item->productLocation = $i['productLocation'];
    	        $item->productQty = $i['productQty'];
    	        $item->productPrice = $i['productPrice'];
    	        $item->productDiscount = $i['productDiscount'];
    	        $item->productRemark = $i['productRemark'];
    	        $item->productStandardPrice = $i['productStandardPrice'];
    	        $item->productUnitName = $i['productUnitName'];
    	        $item->approvedSupervisorId = $i['approvedSupervisorId'];

    	       /* if($i['dbid'] && $i['deleted'] == '1')
    	        {
    	            $item->delete();
    	        }
    	        else
    	       */
    	       if($i['deleted'] == '0')
    	        {
    	            $item->save();
    	        }
    	    }

            $in = Invoice::where('invoiceId',$this->invoiceId)->with('invoiceItem')->first();
            $in->amount = $in->invoiceTotalAmount;
            $in->save();

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
            $file['print_url'][$j] = $files[$j]['url'] = 'undefined.png';
            $file['print_storage'][$j] = $f['fullpath'];
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

	    /*if($_SERVER['env'] == 'production')
	    {
	        syslog(LOG_DEBUG, "New Push Queue to Print Invoice");
	        syslog(LOG_DEBUG, print_r(['user'=>Auth::user(), 'server'=> $_SERVER, 'get'=>$_GET, 'post'=>$_POST], true));
	        
	        $task = new PushTask('/queue/generate-print-invoice-image.queue', ['invoiceId' => $this->invoiceId]);
	        $task_name = $task->add('generate-invoice-image');
	        

	       // $task = new PushTask('/queue/generate-preview-invoice-image.queue', ['invoiceId' => $this->invoiceId]);
	       // $task_name = $task->add('generate-invoice-image');


	        $task = new PushTask('/queue/generate-invoice-pdf.queue', 
	            [
	                'invoiceId' => $this->invoiceId, 
	                'printInstant' => $this->im->deliveryDate < strtotime("today 23.59"), 
	                'printBatch' => $this->im->deliveryDate > strtotime("today 23:59"),
	                'instructor' => Auth::user()->id,	                
	            ]);
	        $task_name = $task->add('invoice-printing-factory');
	    }*/
	}
	
}