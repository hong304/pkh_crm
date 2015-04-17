<?php

return [
    '2' => [
        'statusCode'            => '2',
        'descriptionEnglish'    => 'Open',
        'descriptionChinese'    => '正常',
        'explaination'          => 'This invoice is newly created and all products have been approved by supervising user or system. This invoice is pending to be included into picking list. Editing is allowed.',
        'triggered_by'          => '"Create New Invoice" Function',
    ],
    '1' => [
        'statusCode'            => '1',
        'descriptionEnglish'    => 'Pending Approval',
        'descriptionChinese'    => '等待枇核',
        'explaination'          => 'This invoice is awaiting approval from supervising user. No predefined rule for system for automatic approval',
        'triggered_by'          => 'User\'s Approve',
    ],
    '3' => [
        'statusCode'            => '3',
        'descriptionEnglish'    => 'Rejected',
        'descriptionChinese'    => '被拒絕',
        'explaination'          => 'This invoice is rejected from supervising user. Telesales is required to edit this invoice or request to void this invoice.',
        'triggered_by'          => 'User\'s Reject',
    ],
    '4' => [
        'statusCode'            => '4',
        'descriptionEnglish'    => 'In-Picking List',
        'descriptionChinese'    => '備貨中',
        'explaination'          => 'This invoice has been included into picking list. No more editing is allowed. Create another invoice if necessary.',
        'triggered_by'          => 'The picking list is generated manually or by system.',
    ],
    '11' => [
        'statusCode'            => '11',
        'descriptionEnglish'    => 'Goods Dispatched',
        'descriptionChinese'    => '發貨中',
        'explaination'          => 'Goods in this invoice has been dispatched from warehouse and in-transit for customer delivery',
        'triggered_by'          => 'SA to confirm "Zone Dispatch" function',
    ],
    '20' => [
        'statusCode'            => '20',
        'descriptionEnglish'    => 'Delivered',
        'descriptionChinese'    => '已收貨',
        'explaination'          => 'Invoice was received by destinated customer',
        'triggered_by'          => '',
    ],
    '21' => [
        'statusCode'            => '21', 
        'descriptionEnglish'    => 'Rejected',
        'descriptionChinese'    => '客戶拒收',
        'explaination'          => 'Customer rejected while goods delivery ',
        'triggered_by'          => '',
    ],
    '22' => [
        'statusCode'            => '22',
        'descriptionEnglish'    => 'No Show',
        'descriptionChinese'    => '客戶關門',
        'explaination'          => 'Customer NO SHOW while goods delivery',
        'triggered_by'          => '',
    ],
    '23' => [
        'statusCode'            => '23',
        'descriptionEnglish'    => 'Cancelled',
        'descriptionChinese'    => '途中取消',
        'explaination'          => 'Invoice was cancelled by Company while goods delivery',
        'triggered_by'          => 'SA to confirm "Cancellation"',
    ],
    '30' => [
        'statusCode'            => '30',
        'descriptionEnglish'    => 'Completed',
        'descriptionChinese'    => '完成',
        'explaination'          => 'Invoice delivered, payment (cash / cheque) received',
        'triggered_by'          => 'SA+ to confirm "Completed" status ',
    ],
    '99' => [
        'statusCode'            => '99',
        'descriptionEnglish'    => 'Deleted',
        'descriptionChinese'    => '已刪除',
        'explaination'          => 'This invoice has been voided (deleted). No further process is required. ',
        'triggered_by'          => 'Void Function',
    ],
    '98' => [
        'statusCode'            => '98',
        'descriptionEnglish'    => 'returned',
        'descriptionChinese'    => '退貨',
        'explaination'          => 'All items in the invoice has been returned.',
        'triggered_by'          => 'SA+',
    ],
    'dummy' => [
        'statusCode'            => '',
        'descriptionEnglish'    => '',
        'descriptionChinese'    => '',
        'explaination'          => '',
        'triggered_by'          => '',
    ],
];