<?php

//use Illuminate\Database\Eloquent\SoftDeletingTrait;

class InvoiceItem extends Eloquent  {

    //use SoftDeletingTrait;
    
	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'InvoiceItem';
	protected $primaryKey = 'invoiceItemId';
	public $timestamps = false; 
	
	public static function boot()
	{
	    parent::boot();
	
	    InvoiceItem::updated(function($model)
	    {
	        foreach($model->getDirty() as $attribute => $value){
	            $original= $model->getOriginal($attribute);
	            //echo "Changed $attribute from '$original' to '$value'<br/>";
	            if(!in_array($attribute, array('created_by', 'created_at', 'updated_at')))
	            {
    	            $x = new TableAudit();
    	            $x->referenceKey = $model->invoiceId;
    	            $x->table = "InvoiceItem";
    	            $x->attribute = $attribute;
    	            $x->data_from = $original;
    	            $x->data_to = $value;
    	            $x->created_by = Auth::user()->id;
    	            $x->created_at_micro = microtime(true);
    	            $x->save();
	            }
	        }
	    });
	    
	    InvoiceItem::saving(function($invoiceitem)
	    {
	        if(isset($invoiceitem->productUnitName))
	        {
	            $invoiceitem->productUnitName = str_replace([' '], '', $invoiceitem->productUnitName);
	        }
	        unset($invoiceitem->backgroundcode);
	    });
	}
	
	
	//protected $dates = ['deleted_at'];
	//protected $softDelete = true;

	public function productDetail()
	{
	    
	    return $this->hasOne('Product', 'productId', 'productId');
	}
	
	public function humunize($invoicesItem)
	{
	    if(is_array($invoicesItem))
	    {
	        
	    }
	}
	
    public function newCollection(array $models = array())
    {

        foreach($models as $model)
        {
            // if product details is provided, humanize the product unit name
            if(isset($model->productDetail))
            {
                switch($model->productQtyUnit)
                {
                    case 'carton':
                        $model->UnitName = $model->productDetail->productPackingName_carton;
                        break;
                    case 'inner':
                        $model->UnitName = $model->productDetail->productPackingName_inner;
                        break;
                    case 'unit':
                        $model->UnitName = $model->productDetail->productPackingName_unit;
                        break;
                    default:
                        $model->UnitName = $model->productDetail->productPackingName_carton;
                }
                
            }
            
            $model->backgroundcode = ($model->approvedSupervisorId == "0" ? "background:#FC7E8B" : "");
            
        }
        
        
        return new Collection($models);
    }
	
}