<?php

class QueueController extends BaseController {


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
    

     
     public function sendPrintJobToPrinter()
     {
        // $jobId = Input::get('jobId');
         
         $printer = new InvoicePrinter();
         $printer->sendJobToPrinter();
     }
    
}