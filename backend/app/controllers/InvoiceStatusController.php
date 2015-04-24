<?php

class InvoiceStatusController extends BaseController {

    public function jsonRetrieveAssociation()
    {
        $reportId = Input::get('reportId');


        $report_id = explode("-",$reportId);
        $newid = [];
        $stopID = ReportArchive::where('status_updated',true)->where('id','LIKE',$report_id[0].'%')->where('id','!=',$reportId)->lists('id');

//pd($stopID);

        for($i=$report_id[1];$i>0;$i--){
            $merge_id = $report_id[0].'-'.$i.'-'.$report_id[2];
            $association = ReportArchive::where('id', $merge_id)->Orderby('created_at','desc')->first();

            if(count($association)>0){
                if(in_array($association->id,$stopID))
                    break;
                $invoiceIds = json_decode(json_decode($association->associates, true, true));
                $newid = array_merge($newid,$invoiceIds);
            }
        }

       ReportArchive::where('id',$reportId)->update(['status_updated'=>'1']);

if($report_id[2]==9)
    $floor = 1;
else if ($report_id[2]==1)
    $floor = 9;

        $lastid = ReportArchive::where('id','LIKE',$report_id[0].'-%-'.$floor)->OrderBy('created_at','desc')->take(1)->lists('id');

        $report_id = explode("-",$lastid[0]);
        $stopID = ReportArchive::where('status_updated',true)->where('id','LIKE',$report_id[0].'%')->where('id','!=',$lastid[0])->lists('id');

        for($i=$report_id[1];$i>0;$i--){
            $merge_id = $report_id[0].'-'.$i.'-'.$report_id[2];
            $association = ReportArchive::where('id', $merge_id)->Orderby('created_at','desc')->first();

            if(count($association)>0){
                if(in_array($association->id,$stopID))
                    break;
                $invoiceIds = json_decode(json_decode($association->associates, true, true));
                $newid = array_merge($newid,$invoiceIds);
            }
        }

        ReportArchive::where('id',$lastid)->update(['status_updated'=>'1']);


        $newid = array_unique($newid);



        /*
               $invoiceIds = json_decode(json_decode($association->associates, true, true));
        */

     //   $invoiceIds = json_decode($association->associates);

        $invoices = Invoice::wherein('invoiceId', $newid)->with('client')->get();



        foreach($invoices as $invoice)
        {
            $invoice->nextStatus = InvoiceStatusManager::determinateNextStatus($invoice);
        }

        $returnCustom = [
            'userRole' => Auth::user()->role[0]->id,
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