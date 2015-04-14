<?php

/*
 * Status Changes:
 * 1. Queued
 * 2. Noticed
 * 3. Sent
 * 4. Fired
 * 5. Neutralized
 */
class PrintQueueController extends BaseController {

    
    public function jsonGetUnprintJobs()
    {
        Auth::onceUsingId("27");
        
        $returnCustom = [];
        $action = Input::get('action');
        
        if($action == 'update')
        {
            DB::table('PrintQueue')->wherein('job_id', explode(';', Input::get('ids')))->update(array('status' => Input::get('status')));
        }
        else
        {
            $jobs = PrintQueue::wherein('status', ['queued', 'fast-track'])->where('target_time', '<', time())->OrderBy('file_path','desc')->get();
            $returnCustom = [
                'currentTimeStamp' => time(),
                'jobs' => $jobs,
            ];
        }
        
        
        return Response::json($returnCustom);
    }

    //queryInvoice - 列印記錄
    public function instantPrint()
    {
        $jobId = Input::get('jobId');
        $job = PrintQueue::where('job_id', $jobId)->first();
        $job->target_time = time();
        $job->status = "fast-track"; 
        $job->save();       
    }
    
    public function getAllPrintJobsWithinMyZone()
    {
        // list jobs that are created since 3 days ago. 
        
        $jobs = PrintQueue::wherein('target_path', explode(',', Auth::user()->temp_zone))
                            ->where('insert_time', '>', strtotime("3 days ago"))
                            ->where('status','!=','dead:regenerate')
                            ->with('staff')->leftJoin('Invoice', function($join) {
                                    $join->on('PrintQueue.invoiceId', '=', 'Invoice.invoiceId');
                                })

            ->where(function($query){
                $query->where('Invoice.invoiceStatus','2')
                    ->orwhere('Invoice.invoiceStatus','4');
            })
            /*->with(['Invoice'=>function($q){
                                    $q->where('invoiceStatus','2');
                                }])*/
                            ->orderBy('insert_time', 'desc')
                            ->get();
     //  pd($jobs);

        return Response::json($jobs);
    }
    
    public function printAllPrintJobsWithinMyZone()
    {
        $count = PrintQueue::select('invoiceId', DB::raw('count(*) as total'))->wherein('target_path', explode(',', Auth::user()->temp_zone))->wherein('status',['queued','fast-track'])
            ->groupBy('invoiceId')->having('total','>',1)
            ->get();

        if($count){
            foreach($count as $v){
                $delete = PrintQueue::where('invoiceId',$v->invoiceId)->orderBy('insert_time','desc')->first();
                $delete->delete();
            }
        }

        $affected_jobs = PrintQueue::wherein('target_path', explode(',', Auth::user()->temp_zone))->update(['target_time'=>time()]);
        return Response::json(['affected'=>$affected_jobs]);
    }
    
    public function rePrint()
    {
        $invoiceId = Input::get('invoiceId');

        $class = New InvoiceManipulation();
        $class->generatePrintInvoiceImage($invoiceId);
        $class->generateInvoicePDF($invoiceId,Auth::user()->id);

        /*
        $task = new PushTask('/queue/generate-print-invoice-image.queue', ['invoiceId' => $invoiceId]);
        $task_name = $task->add('generate-invoice-image');
        
        $task = new PushTask('/queue/generate-invoice-pdf.queue',
            [
                'invoiceId' => $invoiceId,
                'printInstant' => true,
                'printBatch' => false,
                'instructor' => Auth::user()->id,
            ]);
        $task_name = $task->add('invoice-printing-factory');
        */
    
    }
    
}