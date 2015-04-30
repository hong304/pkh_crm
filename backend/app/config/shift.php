<?php

return [
    '2' => [
        'statusCode'            => '2',
        'descriptionEnglish'    => 'Open',
        'descriptionChinese'    => '晚班',
        'explaination'          => 'This invoice is newly created and all products have been approved by supervising user or system. This invoice is pending to be included into picking list. Editing is allowed.',
        'triggered_by'          => '"Create New Invoice" Function',
    ],
    '1' => [
        'statusCode'            => '1',
        'descriptionEnglish'    => 'Pending Approval',
        'descriptionChinese'    => '早班',
        'explaination'          => 'This invoice is awaiting approval from supervising user. No predefined rule for system for automatic approval',
        'triggered_by'          => 'User\'s Approve',
    ],
];