<?php


class IPFManipulation {

    public function __construct($ipfId = false)
    {
        $this->action = $ipfId ? 'update' : 'create';
                
        if($this->action == 'create')
        {
            $this->im = new InvoicePrintFormat();
            
        }
        elseif($this->action == 'update')
        {
            $this->im = InvoicePrintFormat::where('ipfId', $ipfId)->firstOrFail();
            
        }
    }

    private function __standardizeDateYmdTOUnix($date)
    {
        $date = explode('-', $date);
        $date = strtotime($date[2].'-'.$date[1].'-'.$date[0]);
        return $date;
    }
	
	public function save($info)
	{
	    $fields = ['from', 'to', 'size', 'advertisement'];
	   
	    
	    $this->im->from = $this->__standardizeDateYmdTOUnix($info['from']);
	    $this->im->to = $this->__standardizeDateYmdTOUnix($info['to']);
	    $this->im->size = $info['size'];
	    $this->im->advertisement = $info['advertisement'];
	    
	    $this->im->save();
	    
	    return true;
	    
	}
}