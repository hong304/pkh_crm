<?php

class poaduditManipaulation
{
    public $reference; 
    public function __construct($poCode)
    {
        $this->poau = new Poaudit();
        $this->poau->referenceKey = $poCode;
        $this->poau->created_by = Auth::user()->id;
        $this->poau->created_at = time();
        $this->reference = $poCode;
    }
    
    public function save()
    {
        try
        {
            $this->poau->save();
            return [
                'result' => true,
                'action' => 'create',
                'poCode' => $this->reference,
            ];
        }
        catch(Toddish\Verify\UserDeletedException $e)
        {
             return [
                'result' => false,
                'action' => 'create',
                'poCode' => $this->reference,
            ];
        }
      
    }
}