<?php

class InvoiceStatusController extends BaseController {

    public function jsonRetrieveAssociation()
    {
        $reportId = Input::get('reportId');




        $association = ReportArchive::where('id', $reportId)->Orderby('created_at','desc')->first();



       $invoiceIds = json_decode(json_decode($association->associates, true, true));


     //   $invoiceIds = json_decode($association->associates);

        $invoices = Invoice::wherein('invoiceId', $invoiceIds)->with('client')->get();




        foreach($invoices as $invoice)
        {
            $invoice->nextStatus = InvoiceStatusManager::determinateNextStatus($invoice);
        }

        $returnCustom = [
            'currentStatus' => $invoices[0]->invoiceStatus,
            'currentStatusText' => $invoices[0]->invoiceStatusText,
            'invoices' => $invoices,  
        ];
        return Response::json($returnCustom);
    }
    
    public function updateStatus()
    {
        $invoiceIncome = Input::get('steps');
        if(count($invoiceIncome) > 0)
        {
            $invoices = Invoice::wherein('invoiceId', array_keys($invoiceIncome))->get();
            
            foreach($invoices as $invoice)
            {
                $invoice->invoiceStatus = $invoiceIncome[$invoice->invoiceId];
                $invoice->save();
            }
        }
        return Response::json(['ok']);
        
    }

}