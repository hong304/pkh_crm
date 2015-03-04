<?php

require_once 'google/appengine/api/cloud_storage/CloudStorageTools.php';
use google\appengine\api\cloud_storage\CloudStorageTools;
use google\appengine\api\taskqueue\PushTask;

class QueueController extends BaseController {

    public function generatePrintInvoiceImage()
    {
        $e = Invoice::where('invoiceId', Input::get('invoiceId'))->first();
        $image = new InvoiceImage();
        
        // generate print version
        $files = $image->generate(Input::get('invoiceId'), true)->saveAll();
        $j = 0;
        $file = [];
        
        foreach($files as $f)
        {
            //$files[$j]['url'] = CloudStorageTools::getImageServingUrl($f['fullpath'], ['size'=>0]);
            $file['print_url'][$j] = $files[$j]['url'] = CloudStorageTools::getImageServingUrl($f['fullpath'], ['size'=>0]);
            $file['print_storage'][$j] = $f['fullpath'];
            $j++;
        }
    
        
        $e->invoicePrintImage = serialize($file);
        $e->save();
    
        exit('completed');
    }
    
    public function generatePreviewInvoiceImage()
    {  
        $e = Invoice::where('invoiceId', Input::get('invoiceId'))->first();
        
        $image = new InvoiceImage();
        
        // generate preview version
        $files = $image->generate(Input::get('invoiceId'))->saveAll();
        $j = 0;
        $file = [];
        
        foreach($files as $f)
        {
            //$files[$j]['url'] = CloudStorageTools::getImageServingUrl($f['fullpath'], ['size'=>0]);
            $file['preview_url'][$j] = $files[$j]['url'] = CloudStorageTools::getImageServingUrl($f['fullpath'], ['size'=>0]);
            $file['preview_storage'][$j] = $f['fullpath'];
            $j++;
        }
        
        
        $e->invoicePreviewImage = serialize($file);
        $e->save();
        
        exit('completed');
    }
    
    public function generateInvoicePDF()
    {
        syslog(LOG_DEBUG, print_r(['user'=>Auth::user(), 'server'=> $_SERVER, 'get'=>$_GET, 'post'=>$_POST], true));
        Auth::onceUsingId("27");
        
        $oldQs = PrintQueue::where('invoiceId', Input::get('invoiceId'))
                            ->wherein('status', ['queued', 'fast-track'])
                            ->get();
        if($oldQs)
        {
            foreach($oldQs as $oldQ)
            {
                $oldQ->status = "dead:regenerated";
                $oldQ->save();
            }
        }
        
        $pdf = new InvoicePdf();
        //$pdf_file = $pdf->generate(Input::get('invoiceId'))->save();
        $pdf_file = $pdf->generate(Input::get('invoiceId'));
        
        // update invoice entry
        if($pdf_file['zoneId'] != "")
        {
            $x = Invoice::where('invoiceId', Input::get('invoiceId'))->first();
            $x->invoicePrintPDF = $pdf_file['path'];
            $x->save();
            
            $q = new PrintQueue();
            $q->file_path = CloudStorageTools::getPublicUrl($pdf_file['path'], false);
            $q->target_path = $x->zoneId;
            $q->insert_time = time();
            $q->status = "queued";
            $q->invoiceId = Input::get('invoiceId');
            
            /*
             * if it is a print instant job, mark the status as send-pending
             * instantly initiate a queue to send the job from server to printer
            */
            
            if(Input::get('printInstant') == true)
            {
                $q->target_time = time();
            }
            elseif(Input::get('printBatch'))
            {
                $q->target_time = strtotime("tomorrow 3am");
            }
            
            $q->created_by = Input::get('instructor');
            $q->save();
        }
        
        
        // queue if instant print job
        /*
        if(Input::get('printInstant'))
        {
            $task = new PushTask('/queue/send-print-job-to-printer.queue', ['jobId' => $q->job_id]);
            $task_name = $task->add('invoice-printing-factory');
        }
        */
     }
     
     public function sendPrintJobToPrinter()
     {
         $jobId = Input::get('jobId');
         
         $printer = new InvoicePrinter();
         $printer->sendJobToPrinter([$jobId]);
     }
    
}