<?php


class InvoicePdf {
    
    public $pdf = "";
    public $invoiceId = "";
    public $zoneId = "";
    public $route = "";
    
    public function generate($invoiceId)
    {
        $this->invoiceId = $invoiceId;

        $invoiceImage = Invoice::select('invoicePrintImage', 'zoneId','routePlanningPriority')->where('invoiceId', $invoiceId)->first();
        $this->zoneId = $invoiceImage['zoneId'];
        $this->route = $invoiceImage['routePlanningPriority'];
        $image = unserialize($invoiceImage->invoicePrintImage);
        
        $pagesize = "A5";
        $pdf = new Fpdf();
        $section = 0; 
        
        try
        {
            for($i = 1; $i <= 2; $i++)
            {
                foreach($image['print_storage'] as $index => $url)
                {
                    
                    
                    if($section == 0 || $section  % 2 == 0)
                    {
                        $pdf->AddPage();
                        $y = 0;
                       // syslog(LOG_INFO, "Page Added");
                    }
                    
                 //   syslog(LOG_INFO, $url);
                 //   syslog(LOG_INFO, sprintf("i: %s, index: %s, section: %s", $i, $index, $section));
                    
                    
                    
                    $pdf->Image($url, 3, $y -2, 207, 0, 'PNG');
                    
                    // delete the image afterward
                   // @unlink($url);
                    
                    if($pagesize == "A5")
                    {
                        $y += 148;
                    }
                    else
                    {
                        $y = 0;
                    }
                    
                    $section++;
                    
                }
            }
            
        }
        catch(Exception $e)
        {
            
           // syslog(LOG_INFO, "Image File Not Ready\n" . print_r($invoiceImage->invoicePrintImage, true));
            App::abort(500);
        }
        $this->pdf = $pdf;
        
        $k = explode('-', $this->invoiceId);

        $temp_filename = $k[0].'-'.str_pad($this->route, 2, "0", STR_PAD_LEFT).'-'.$k[1];

        $filename = 'pdf/' . $temp_filename . '.pdf';

        //$path = storage_path().'/invoices_images/'. str_replace('I', '', $k[0]) .'/'.$filename;

        $path = public_path($filename);



        $this->pdf->Output($path, "F");
        
        return ['path'=>$path, 'zoneId'=>$this->zoneId];
        
        //return $this;
    }
    
    public function show()
    {
    
        $this->pdf->Output();
        exit;
    }
    
    public function save()
    {
        return true; 
        
        $k = explode('-', $this->invoiceId);
        $filename = 'pdf/' . $this->invoiceId . '.pdf';
        $path = storage_path().'/invoices_images/'. str_replace('I', '', $k[0]) .'/'.$filename;



        $this->pdf->Output($path, "F");
        
        return ['path'=>$path, 'zoneId'=>$this->zoneId];
        
    }
}