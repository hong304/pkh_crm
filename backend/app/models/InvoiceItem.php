<?php

use Illuminate\Database\Eloquent\SoftDeletingTrait;

class InvoiceItem extends Eloquent  {

    use SoftDeletingTrait;
    
	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */
	protected $table = 'invoiceitem';
	protected $primaryKey = 'invoiceItemId';
	public $timestamps = false;
    protected $dates = ['deleted_at'];

	public static function boot()
	{
	    parent::boot();
	
	    InvoiceItem::updated(function($model)
	    {
         //   if($model->isDirty()){
         //       p($model->getDirty());
         //       pd($model->getOriginal());
         //   }
	        foreach($model->getDirty() as $attribute => $value){
	            $original= $model->getOriginal($attribute);
	            //echo "Changed $attribute from '$original' to '$value'<br/>";
	            if(!in_array($attribute, array('created_by', 'created_at', 'updated_at','productStandardPrice')))
	            {
    	            $x = new invoiceitemaudit();
    	            $x->referenceKey = $model->invoiceId;
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

    public function invoice(){
        return $this->belongsTo('Invoice','invoiceId','invoiceId');
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

               /*
            $inner = ($model->productPacking_inner) ? $model->productPacking_inner:1;
            $unit = ($model->productPacking_unit) ? $model->productPacking_unit:1;


            if($model->invoice->invoiceStatus == 98){
                if($model->productQtyUnit == 'carton')
                    $model->realNormalizedUnit =  $model->productQty*$inner*$unit*-1;
                if($model->productQtyUnit == 'inner')
                    $model->realNormalizedUnit = $model->productQty*$unit*-1;
                else
                    $model->realNormalizedUnit = $model->$productQty * -1;
            }else{
                if($model->productQtyUnit == 'carton')
                    $model->realNormalizedUnit = $model->productQty*$inner*$unit;
                if($model->productQtyUnit == 'inner')
                    $model->realNormalizedUnit = $model->productQty*$unit;
                else
                    $model->realNormalizedUnit = $model->$productQty;
            }
               */
            
        }
        
        
        return new Collection($models);
    }

    public function getFullNameAttribute()
    {
        return "$this->productId $this->productQtyUnit";
    }

    public function getRealQtyAttribute()
    {
        if($this->invoice->invoiceStatus == 98)
            return $this->productQty * -1;
    }

    public function getRealNormalizedUnitAttribute()
    {

        $inner = ($this->productDetail->productPacking_inner>0) ? $this->productDetail->productPacking_inner:1;
        $unit = ($this->productDetail->productPacking_unit>0) ? $this->productDetail->productPacking_unit:1;

        if($this->invoice->invoiceStatus == 98){
            if($this->productQtyUnit == 'carton')
                return $this->productQty*$inner*$unit*-1;
            else if($this->productQtyUnit == 'inner')
                return $this->productQty*$unit*-1;
            else
                return $this->productQty * -1;
        }else{
            if($this->productQtyUnit == 'carton')
                return $this->productQty*$inner*$unit;
            else if($this->productQtyUnit == 'inner')
                return $this->productQty*$unit;
            else
                return $this->productQty;
        }

    }
	
}