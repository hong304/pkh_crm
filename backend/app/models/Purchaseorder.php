<?php

class Purchaseorder extends Eloquent {

    public static function boot() {
        parent::boot();

// create a event to happen on updating
        static::updating(function($table) {
            $table->updated_by = Auth::user()->id;
        });

// create a event to happen on deleting
        static::deleting(function($table) {
            $table->deleted_by = Auth::user()->id;
        });

// create a event to happen on saving
        static::saving(function($table) {
            $table->created_by = Auth::user()->id;
        });
    }
    
       public function Poitem()
	{
	    return $this->hasMany('Poitem', 'poCode', 'poCode');
	}
        

        
        public function currency()
        {
            return $this->hasOne('Currency', 'currencyId', 'currencyId'); //Return one object only
        }
        
        public function shipping()
        {
            return $this->hasMany('Shipping', 'poCode', 'poCode'); //Return multiple shipping objects
        }
        
        public function product()
        {
            return $this->hasMany('Product', 'productId', 'productId'); //Return multiple shipping objects
        }

        public function supplier()
        {
            return $this->belongsTo('Supplier', 'supplierCode', 'supplierCode');
        }

        public function Receiving(){
            return $this->hasMany('Receiving','poCode','poCode');
        }
        

    public static function getFullPo($base) {
        // get invoices and items
        $po = $base;
        
        $itemIds = array('箱', '扎', '排', '桶');

        $ids = "'" . implode("','", $itemIds) . "'";

       
        $po = $po->with(['Poitem' => function ($query) use ($ids) {
                        $query->orderByRaw(DB::raw("FIELD(productQtyUnit, $ids) ASC"))->orderBy('productId', 'desc');
                    }])->with('supplier')->get();



        // $invoices = $invoices->with('invoiceItem', 'client', 'staff')->get();
        $total = $po->count();
      
        // get product informationzz
        $productId = [];
        if (count($po) > 0) {
            foreach ($po as $inv){
                $invoiceTotal = 0;
                foreach ($inv->poitem as $item) {
                    $productId[] = $item->productId;
                    $invoiceTotal += ($item->productQty * $item->unitprice * (100 - $item->discount_1) / 100 * (100 - $item->discount_2) / 100 * (100 - $item->discount_3) / 100) - $item->allowance_1 - $item->allowance_2 - $item->allowance_3;
                }
                $inv->totalAmount = $invoiceTotal;
         
            }
    
            $products = Product::wherein('productId', $productId)->get(); //remember to put wherein rather than where , since the prodductId array will not be queried

            foreach ($products as $product) {//dd($product->toArray());
                $newProductSet[$product->productId] = $product->toArray();
            }
           
            foreach ($po as $inv) {
                foreach ($inv->poitem as $item) {
                    $item->productInfo = $newProductSet[$product->productId];
                }
            }
        }

        $returnInfo = [
            'count' => $total,
            'pos' => $po->toArray(),
        ];


        return $returnInfo;
    }

}
