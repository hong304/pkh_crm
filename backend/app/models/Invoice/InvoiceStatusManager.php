<?php

class InvoiceStatusManager {
    /*
     * Status :
     * 1: Open
     * 2: Approved
     * 3: Rejected
     */

    public $invoiceId = "";
    public $im = "";
    
    public function __construct($invoiceId)
    {
        if(!is_array($invoiceId))
        {
            $invoiceId = array($invoiceId);
        }
        
        $this->invoiceId = $invoiceId;
        $this->im = Invoice::wherein('invoiceId', $invoiceId)->with('invoiceItem')->get();
        
        return $this;
    }
    
    public function approve()
    {
        foreach($this->im as $i)
        {
            // first approve all non-approved items
            foreach($i->invoiceItem as $item)
            {
                // if this item has not yet been approved before, 
                // approve this item
                
                if($item->approvedSupervisorId == '0')
                {
                    $item->approvedSupervisorId = Auth::user()->id;
                }
                
                $item->save();
            }
            
            $i->invoiceStatus = '2';
            $i->save();

            PrintQueue::where('invoiceId', $this->invoiceId)->update(['status'=>'queued']);
        }
        return $this;
    }
    
    public function reject()
    {
        foreach($this->im as $i)
        {            
            $i->invoiceStatus = '3';
            $i->save();
        }
        return $this;
    }
	
    public static function determinateNextStatus($invoice)
    {


        $route = [
            '4' => [
               'default' => '11',
               'steps' => [
                   '11' => [
                       'invoiceStatus' => '11',
                       'invoiceStatusText' => Config::get('invoiceStatus.11.descriptionChinese'),
                   ],
               ], 
            ],
            '11' => [
                'default' => $invoice['client']->paymentTermId == '1' ? '30' : '20',
                'steps' => [
                    '30' => [
                        'invoiceStatus' => '30',
                        'invoiceStatusText' => Config::get('invoiceStatus.30.descriptionChinese'),
                    ],
                    '20' => [
                        'invoiceStatus' => '20',
                        'invoiceStatusText' => Config::get('invoiceStatus.20.descriptionChinese'),
                    ],
                    '21' => [
                        'invoiceStatus' => '21',
                        'invoiceStatusText' => Config::get('invoiceStatus.21.descriptionChinese'),
                    ],
                    '22' => [
                        'invoiceStatus' => '22',
                        'invoiceStatusText' => Config::get('invoiceStatus.22.descriptionChinese'),
                    ],
                    '23' => [
                        'invoiceStatus' => '23',
                        'invoiceStatusText' => Config::get('invoiceStatus.23.descriptionChinese'),
                    ],
                ],
            ],
            '20' => [
                'default' => '30',
                'steps' => [
                    '30' => [
                       // 'invoiceStatus' => '30',
                      //  'invoiceStatusText' => Config::get('invoiceStatus.30.descriptionChinese'),
                    ],
                    
                ],
            ],
            '30' => [
                'default' => '30',
                'steps' => [            
                ],
            ],
            '21' => [
                'default' => '21',
                'steps' => [
                ],
            ],
            '22' => [
                'default' => '22',
                'steps' => [
                ],
            ],
            '23' => [
                'default' => '22',
                'steps' => [
                ],
            ],
            
        ];
        
        $currentState = $invoice->invoiceStatus;



        return $route[$currentState];
    }
}