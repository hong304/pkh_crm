
<table class="table table-bordered table-hover" style="font-size:15px;">
    <thead>
        <tr role="row" class="heading">
            <th width="10%">訂單</th>
            <th width="70%">客戶</th>
            <th width="10%">應收金額</th>
            <th width="10%">累計</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($data as $row):?> 
            <tr>
                <td><?php echo $row['invoiceNumber']; ?></td>
                <td><?php echo $row['name']; ?></td>
                <td>HK$ <?php echo number_format($row['invoiceTotalAmount']); ?></td>
                <td>HK$ <?php echo number_format($row['accumulator']); ?></td>
            </tr>  
         <?php endforeach; ?>.
         <tr>
            <td colspan="4" style="text-align:right;">
                <span style="font-weight:bold;font-size:15px;">總計: HK$<?php echo number_format(end($data)['accumulator']); ?></span>
            </td>
         <tr>
    </tbody>
</table>
