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
	protected $table = 'Product';
	
	// use collection to protect this	
	public function __construct()
	{
	    /* 
	    if(!Auth::user()->can('view_product_cost'))
	    {
	        $this->guarded[] = 'productCost_unit';
	    }
	     */
	}
	
	public static function boot()
	{
	    parent::boot();
	
	    Product::saving(function($e)
	    {
	        unset($e->productPacking);
	        unset($e->productPackingName);
	        unset($e->productStdPrice);
	        unset($e->productPackingInterval);
	        unset($e->productMinPrice);
	    });
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
	        
	        //dd($model);
	        $newmodel[$model->productId] = $model;
	    }
	    
	    
	    return new Collection($newmodel);
	}
	
	public function invoice()
	{
	    return $this->belongsToMany('Invoice');
	}

}