<?php
use Illuminate\Database\Eloquent\SoftDeletingTrait;
class Invoice extends Eloquent  {

    public $primaryKey = 'invoiceId';
    protected $table = 'Invoice';
	//public $timestamps = false;
	use SoftDeletingTrait;
	
	protected $dates = ['deleted_at'];
	protected $hidden = ['invoicePreviewImage', 'invoicePrintImage', 'invoicePrintPDF'];
	
	protected $with = ['zone'];

	public static function boot()
	{
	    parent::boot();
	
	    Invoice::saving(function($invoice)
        {
            unset($invoice->deliveryDate_date);
            unset($invoice->createdat_full);
            unset($invoice->invoiceStatusText);
            unset($invoice->zoneText);
            unset($invoice->invoiceTotalAmount);
            unset($invoice->backgroundcode);
        });
	    
	    Invoice::updated(function($model)
	    {
	        foreach($model->getDirty() as $attribute => $value){
	            $original= $model->getOriginal($attribute);
	            if(!in_array($attribute, array('created_by', 'created_at', 'updated_at','invoicePrintPDF','invoicePrintImage')))
	            {
	                $x = new TableAudit();
	                $x->referenceKey = $model->invoiceId;
	                $x->table = "Invoice";
	                $x->attribute = $attribute;
	                $x->data_from = $original;
	                $x->data_to = $value;
	                $x->created_by = (isset(Auth::user()->id) ? Auth::user()->id : 27);
	                $x->created_at_micro = microtime(true);
                    if($attribute != 'amount' || $original != null)
	                    $x->save();
	            }
	        }
	    });
	}
	
	
	
	public static function getFullInvoice($base, $zoneid = false)
	{
	    // get invoices and items
	    $invoices = $base;
	    
	    if($zoneid)
	    {
	        $invoices = $invoices->where('zoneId', $zoneid);
	    }

           $itemIds = array('箱','桶','排','樽','斤');

        $ids = "'" . implode("','", $itemIds) . "'";

	    $invoices = $invoices->with(['invoiceItem'=>function($query) use($ids){
            $query->orderByRaw(DB::raw("FIELD(productUnitName, $ids) DESC"));
        }])->with( 'client', 'staff')->get();


	   // $invoices = $invoices->with('invoiceItem', 'client', 'staff')->get();
	    $total = $invoices->count();
	    
	    // get product information
	    $productId = [];
	    if(count($invoices) > 0)
	    {
    	    foreach($invoices as $inv)
    	    {
    	        $invoiceTotal = 0;
    	        foreach($inv->invoiceItem as $item)
    	        {
    	            $productId[] = $item->productId;
    	            $invoiceTotal += $item->productQty * $item->productPrice * (100-$item->productDiscount)/100;
    	        }
    	        $inv->totalAmount = $invoiceTotal;
    	        
    	        Invoice::processInvoice($inv);
    	        
    	    }
    	    
    	    $products = Product::wherein('productId', $productId)->get();
    	    foreach($products as $product)
    	    {//dd($product->toArray());
    	        $newProductSet[$product->productId] = $product->toArray();
    	    }
    	    
    		foreach($invoices as $inv)
    	    {
    	        foreach($inv->invoiceItem as $item)
    	        {
    	            $item->productInfo = $newProductSet[$item->productId];
    	        }
    	    }
	    }
	    
	    $returnInfo = [
	        'count' => $total,
	        'invoices' => $invoices->toArray(),
	    ];
	       
	    return $returnInfo;
	}
	
	public static function categorizePendingInvoice($invoices)
	{
	    if(count($invoices['invoices']) > 0)
	    {
    	    $count = $invoices['count'];
    	    $invoices = $invoices['invoices'];
    	   
    	    foreach($invoices as $i)
    	    {
    	        
    	        $newInvoices[$i['client']['deliveryZone']]['count'] = (isset($newInvoices[$i['client']['deliveryZone']]['count']) ? $newInvoices[$i['client']['deliveryZone']]['count'] + 1 : 1);
    	        $newInvoices[$i['client']['deliveryZone']]['invoices'][] = $i;
    	        $newInvoices[$i['client']['deliveryZone']]['lastload'] = date("H:i");
    	    }
    	    
    	    if(count($invoices) > 0)
    	    {
    	        $zone = Zone::all();
    	        foreach($zone as $z)
    	        {
    	            if(isset($newInvoices[$z->zoneId]))
    	            {
    	               $newInvoices[$z->zoneId]['zoneName'] = $z->zoneName;
    	               $newInvoices[$z->zoneId]['zoneId'] = $z->zoneId;
    	            }
    	        }
    	    }
    	   
    	    $returnInfo = [
    	        'total' => $count,
    	        'categorized' => $newInvoices,
    	    ];
	    }
	    else
	    {
	        $returnInfo = [
	            'total' => 0,
	            'categorized' => array(),
	        ];
	    }
	    //dd($returnInfo);
	    return $returnInfo;
	}
	
	public static function processInvoice($invoice)
	{
	    //$invoice->invoiceDate = date("Y.m.d H:i", $invoice->invoiceDate);
	}
	
	public function invoiceItem()
	{
	    return $this->hasMany('InvoiceItem', 'invoiceId', 'invoiceId');
	}
	public function staff()
	{
	    return $this->hasOne('User', 'id', 'created_by');
	}
	
	public function client()
	{
	    return $this->hasOne('Customer', 'customerId', 'customerId');
	}
	
	public function products()
	{
	    return $this->belongsToMany('Product', 'InvoiceItem', 'invoiceId', 'productId');
	    // return $this->hasManyThrough('Product', 'InvoiceItem', 'productId', 'productId');
	}
	
	public function zone()
	{
	    return $this->hasOne('Zone', 'zoneId', 'zoneId');
	}
	
	public function printqueue()
	{
	    return $this->hasMany('PrintQueue', 'invoiceId', 'invoiceId');
	}
	
	public function newCollection(array $models = array())
	{
	
	    foreach($models as $model)
	    {
	        
	        // full deliveryDate
	        $model->deliveryDate_date = date("Y-m-d", $model->deliveryDate);
	        
	        // full created_at
	        $model->createdat_full = date("Y-m-d H:i:s", $model->created_at);
	        
	        // status text
	        $model->invoiceStatusText = Config::get('invoiceStatus.' . $model->invoiceStatus . '.descriptionChinese');

            //zone text
            $model->zoneText = Config::get('zoneName.'.$model->zoneId);
	        // calculate invoice total
	        
	        if(isset($model['invoiceItem']))
	        {
	            $model->invoiceTotalAmount = 0;
	            foreach($model['invoiceItem'] as $item)
	            {
	                $model->invoiceTotalAmount += $item->productQty * $item->productPrice * (100-$item->productDiscount)/100;
                    $model->invoiceTotalAmount = round($model->invoiceTotalAmount,1);
	            }
	        }
	        
	    }
	
	
	    return new Collection($models);
	}
	
	public function audit()
	{
	    return $this->hasMany('TableAudit', 'referenceKey', 'invoiceId');
	}

}