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
                            ->with('staff')

                            ->orderBy('insert_time', 'desc')
                            ->get();
        
        return Response::json($jobs);
    }
    
    public function printAllPrintJobsWithinMyZone()
    {
        $affected_jobs = PrintQueue::wherein('target_path', explode(',', Auth::user()->temp_zone))
                            ->update(['target_time'=>time()]);
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