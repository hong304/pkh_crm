<?php


class InvoicePrinter {
    
    public function sendJobToPrinter()
    {
        
       $this->sendJobViaFTP('341');

    }
    
    public function sendJobViaFTP($job_id)
    {
        $job = Printlog::where('job_id', $job_id)->first();

        $ftp_user_name = 'pkh';
        $ftp_user_pass = 'pkh2015';
        $ftp_server = 'pingkeehong.asuscomm.com';
        $conn_id = ftp_connect($ftp_server);
        ftp_login($conn_id, $ftp_user_name, $ftp_user_pass);

            DB::table('Printlogs')->where('job_id', $job->job_id)->update(['status'=>'sending']);
        
            if (@ftp_put($conn_id, str_pad($job->target_path, 3, '0', STR_PAD_LEFT).'/'.$job->job_id.'-'.$job->shift.'-'.$job->count.'.pdf', $job->file_path, FTP_ASCII)) {
                $updates = ['status'=>'downloaded;passive', 'complete_time'=>time()];
            } else {
                $updates = ['status'=>'fail'];
            }
             DB::table('Printlogs')->where('job_id', $job->job_id)->update($updates);
            //var_dump(DB::getQUeryLog());
        ftp_close($conn_id);
    }

}