<?php


class CurrencyManipulation {

    private $_id = '';
    public $action = "";
    public function __construct($id = false)
    {
        $this->action = $id ? 'update' : 'create';
                
        if($this->action == 'create')
        {
           // $this->generateId();
           // $this->_id = $currencyObject;
            $this->im = new Currency();
            $this->im->created_by = Auth::user()->id;
            
        }
        elseif($this->action == 'update')
        {
            $this->im = Currency::where('id', $id)->firstOrFail();
            $this->im->updated_by = Auth::user()->id;
            
          //  $this->_id = $id;
        }
    }
    

	
	public function save1($info)
	{

	    $fields = ['currencyId', 'currencyName'];
	    
            if(Auth::user()->can('allow_edit'))
            {
                    foreach($fields as $f)
                     {
                         $this->im->$f = $info[$f];
                     }
            }
	
	   // $this->im->countryId = $info['deliveryZone']['zoneId'];
	  //  $this->im->customerId = $this->_customerId;
	 //   $this->im->status = $info['status']['value'];
        //    $this->im->shift = $info['shift']['value'];
        //    $this->im->updated_by = Auth::user()->id;
           
	    $this->im->save();
	    
	    return $this->action;
	    
	}
}