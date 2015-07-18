<?php



class ReportArchive extends Eloquent{
    
    protected $table = 'reportarchive';
    
    protected $with = ['user'];
    
    public function user()
    {
        return $this->hasOne('User', 'id', 'created_by');
    }

    public function zone(){
        return $this->hasOne('Zone','zoneId','zoneId');
    }
    

}