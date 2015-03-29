<?php
use Illuminate\Database\Eloquent\SoftDeletingTrait;
class data_invoice extends Eloquent  {


	
	public function data_invoiceitem()
	{
	    return $this->hasMany('data_invoiceitem');
	}

	public function newCollection(array $models = array())
	{
	
	    foreach($models as $model)
	    {
	        
	        // full deliveryDate
	       // $model->deliveryDate_date = date("Y-m-d", $model->deliveryDate);
	        

	        // status text
	       // $model->invoiceStatusText = Config::get('invoiceStatus.' . $model->invoiceStatus . '.descriptionChinese');
	        
	        // calculate invoice total
	        
	        if(isset($model['data_invoiceitem']))
	        {
	            $model->total_amount = 0;
	            foreach($model['data_invoiceitem'] as $item)
	            {
	                $model->total_amount += $item->productQty * $item->productPrice * (100-$item->productDiscount)/100;
	            }
	        }
	        
	    }
	
	
	    return new Collection($models);
	}

}