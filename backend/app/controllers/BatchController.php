<?php

require_once 'google/appengine/api/cloud_storage/CloudStorageTools.php';
use google\appengine\api\cloud_storage\CloudStorageTools;
use google\appengine\api\taskqueue\PushTask;

class BatchController extends BaseController {

    public function logBatchStatement($functionName, $startTime, $endTime, $timeUsed)
    {
        
    }
    
    /*
     * Batch: update table product_search_customer_map
     * Purpose: speed up product search suggestion per customer by using temporary table
     * Run: everyday 00:00
     */
    public function productSearchandCustomerMapClearance()
    {
        $start = microtime(true);
        DB::statement("TRUNCATE ProductSearch_Customer_Map;");
        $sql = "INSERT INTO
                	ProductSearch_Customer_Map
                (
                    SELECT 
                        inv.customerId, item.productId, sum(item.productQty) AS sumation
                    FROM
                        InvoiceItem as item
                    LEFT JOIN
                        Invoice as inv
                    ON 
                        item.invoiceId = inv.invoiceId
                    WHERE 
                        item.created_at >= ( NOW() - INTERVAL 30 DAY )
                    GROUP BY
                        inv.customerId, item.productId
                    ORDER BY
                    	inv.customerId ASC, sumation DESC
                )
                ;
                    ";
        DB::statement($sql);
        $end = microtime(true);
        //logBatchStatement('productSearchandCustomerMapClearance', $start, $end, $end-$start);
    }
    
    public function batchSendInvoiceToPrinter()
    {
        echo date("Y-m-d H:i:s", time());
        
        PrintQueue::select('job_id')->wherenull('complete_time')->where('target_time', '<', time())->chunk(50, function($q)
        {
            foreach($q as $invoice)
            {

                $ids[] = $invoice->job_id;
            }
            
            $printer = new InvoicePrinter();
            $printer->sendJobToPrinter($ids);
        });
        

    }
    
    
}