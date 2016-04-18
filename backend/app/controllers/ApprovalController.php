<?php

class ApprovalController extends BaseController {

    public function logBatchStatement($functionName, $startTime, $endTime, $timeUsed)
    {
        
    }

    public function getApprovalList(){
        $invoices = Invoice::with(['invoiceItem'=>function($q) {
            $q->with('productDetail');
        }])->with('customer')->
        where('invoiceStatus','1')->orderBy('zoneId')->get();
        return Response::json($invoices);
    }

    public function batchApproval()
    {


       $invoiceids = [];
        foreach(Input::get('target') as $v){
            $invoiceids[$v['invoiceId']] = 1;
        }

       // pd(Input::get('exception')['exception']);

        foreach(Input::get('exception') as $k => $v){
            if (array_key_exists($k, $invoiceids)) {
              unset($invoiceids[$k]);

            }
        }



        $invoicess = Invoice::wherein('invoiceId', array_keys($invoiceids))->with('invoiceItem')->get();




        foreach($invoicess as $i)
        {
            // first approve all non-approved items
            foreach($i->invoiceItem as $item)
            {
                // if this item has not yet been approved before,
                // approve this item

                if($item->approvedSupervisorId == '0')
                {
                    $item->approvedSupervisorId = Auth::user()->id;
                }

                $item->save();
            }
            $i->previous_status = $i->invoiceStatus;
            if($i->invoiceStatus == 1){
                $i->invoiceStatus = '2';
            }
            $i->save();

            // if($i->deliveryDate == strtotime(date( "Y-m-d H:i:s",mktime(0, 0, 0))) && date('G') < 12){
            //      PrintQueue::where('invoiceId', $this->invoiceId)->update(['status'=>'queued']);
            //  }

            PrintQueue::where('invoiceId', $i->invoiceId)->where('status','dead:pending')->update(['status'=>'queued','invoiceStatus'=>'2']);

        }
        //return $this;
    }

    /*
     * Batch: update table product_search_customer_map
     * Purpose: speed up product search suggestion per customer by using temporary table
     * Run: everyday 00:00
     */

    public function batchSendInvoiceToPrinter()
    {
        echo date("Y-m-d H:i:s", time());
        
        PrintQueue::select('job_id')->wherenull('complete_time')->where('target_time', '<', time())->chunk(50, function($q)
        {
            foreach($q as $invoice)
            {

                $ids[] = $invoice->job_id;
            }
            
            $printer = new InvoicePrinter();
            $printer->sendJobToPrinter($ids);
        });
        

    }
    
    
}