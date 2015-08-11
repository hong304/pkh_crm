<?php

class InvoiceUnloader {
    
    public $invoiceId = "";
    public $im = "";
    
    public function __construct($invoiceId)
    {
        if(!is_array($invoiceId))
        {
            $invoiceId = array($invoiceId);
        }
        
        $this->invoiceId = $invoiceId;
        $this->im = Invoice::wherein('invoiceId', $invoiceId)->with('invoiceitem')->get();
        
        return $this;
    }
    
    private function __standardizeDateYmdTOUnix($date)
    {
        $date = explode('-', $date);
        $date = strtotime($date[2].'-'.$date[1].'-'.$date[0]);
        return $date;
    }
    
    public function changeDate($newDate)
    {
        $newDate = $this->__standardizeDateYmdTOUnix($newDate);
        foreach($this->im as $i)
        {
            $i->invoiceDate = $newDate;
            $i->deliveryDate = $newDate;
            $i->dueDate = $newDate;
            $i->invoiceStatus = '1';
            $i->save();
        }
    }
    
    public function cancel()
    {
        foreach($this->im as $i)
        {
            $i->previous_status = $i->invoiceStatus;
            $i->invoiceStatus = '99';
            $i->deleted_at = date('Y-m-d H:i:s');
            $i->save();
        }
    }

    public function backToNormal()
    {
        foreach($this->im as $i)
        {
            $i->invoiceStatus = '2';
            $i->save();
        }
    }
    
	
}