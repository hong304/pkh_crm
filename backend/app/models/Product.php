<?php
use Illuminate\Database\Eloquent\SoftDeletingTrait;

class Product extends Eloquent  {

    protected $primaryKey = 'productId';
    
	/**
	 * The database table used by the model.
	 *
	 * @var string
	 */

    use SoftDeletingTrait;

    protected $dates = ['deleted_at'];
	protected $table = 'product';
	

	
	public static function boot()
	{
	    parent::boot();

        Product::updated(function($model)
        {
            foreach($model->getDirty() as $attribute => $value){
                $original= $model->getOriginal($attribute);
                if(!in_array($attribute, array('created_by', 'created_at'))) {
                    $x = new TableAudit();
                    $x->referenceKey = $model->productId;
                    $x->table = "product";
                    $x->attribute = $attribute;
                    $x->data_from = $original;
                    $x->data_to = $value;
                    $x->created_by = Auth::user()->id;
                    $x->created_at_micro = microtime(true);
                    $x->save();
                }
            }
        });

	    Product::saving(function($e)
	    {
	        unset($e->productPacking);
	        unset($e->productPackingName);
	        unset($e->productStdPrice);
	        unset($e->productPackingInterval);
	        unset($e->productMinPrice);
	    });

	}


    public function getUpdatedByAttribute($attr) {
        return Config::get('userName.'.$attr);
    }

	public function InvoiceItem()
	{
	    return $this->belongsToMany('InvoiceItem');
	} 
	
	public static function compileProductStandardForm($products)
	{	    
	    if(!is_array($products))
	    {
	        $products = [$products];
	    }
	    
	    foreach($products as $p)
	    {
	        $product[$p['productId']] = $p;
	        $product[$p['productId']]['itemdiscount'] = 0;
	    }
	    
	    return $product;
	}
	
	public static function compileProductAddCustomerDiscount($products, $customerId = 0)
	{
	    if($customerId != 0)
	    {
	        $discountp = CustomerProductDiscount::where('customerId', $customerId)->get();
	        
	        if(count($discountp) > 0)
	        {
    	        foreach($discountp as $d)
    	        {
    	            $products[$d->productId]['itemdiscount'] = $d->discount;
    	        }
	        }
	    }
	    
	    return $products;
	}
	
	public function newCollection(array $models = array())
	{
        $newmodel = [];
	    foreach($models as $model)
	    {
	        	     
	        $model->productPacking = [
	           'carton' => $model->productPacking_carton,
	            'inner' => $model->productPacking_inner,
	            'unit' => $model->productPacking_unit,    
	        ];
	        
	        $model->productPackingName = [
	            'carton' => $model->productPackingName_carton,
	            'inner' => $model->productPackingName_inner,
	            'unit' => $model->productPackingName_unit,
	        ];
	        $model->productStdPrice = [
	            'carton' => $model->productStdPrice_carton,
	            'inner' => $model->productStdPrice_inner,
	            'unit' => $model->productStdPrice_unit,
	        ];
	        $model->productPackingInterval = [
	            'carton' => $model->productPackingInterval_carton,
	            'inner' => $model->productPackingInterval_inner,
	            'unit' => $model->productPackingInterval_unit,
	        ];
	        $model->productMinPrice = [
	            'carton' => $model->productMinPrice_carton,
	            'inner' => $model->productMinPrice_inner,
	            'unit' => $model->productMinPrice_unit,
	        ];



            $newmodel[$model->productId] = $model;

	    }
	    
	    
	    return new Collection($newmodel);
	}
	
	public function invoice()
	{
	    return $this->belongsToMany('Invoice');
	}

    public function getNormalizedUnitAttribute(){

        $carton = ($this->productPacking_carton) ? $this->productPacking_carton:1;
        $inner = ($this->productPacking_inner) ? $this->productPacking_inner:1;
        $unit = ($this->productPacking_unit) ? $this->productPacking_unit:1;

        return $carton*$inner*$unit;

    }


}