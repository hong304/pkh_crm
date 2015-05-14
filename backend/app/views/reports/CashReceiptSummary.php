<table class="table table-bordered table-hover" style="font-size:15px;">
    <thead>
    <tr role="row" class="heading">
        <th width="100%" colspan="4">已收款項</th>
    </tr>
    </thead>

    <thead>
        <tr role="row" class="heading">
            <th width="20%">訂單</th>
            <th width="50%">客戶</th>
            <th width="15%">應收金額</th>
            <th width="15%">累計</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach($data as $row):?> 
            <tr>
                <td><?php echo $row['invoiceNumber']; ?></td>
                <td><?php echo $row['name']; ?></td>
                <td>HK$ <?php echo $row['amount']; ?></td>
                <td>HK$ <?php echo $row['accumulator']; ?></td>
            </tr>  
         <?php endforeach; ?>.
         <tr>
            <td colspan="4" style="text-align:right;">
                <span style="font-weight:bold;font-size:15px;">總計: HK$<?php echo end($data)['accumulator']; ?></span>
            </td>
         <tr>
    </tbody>
</table>

<table class="table table-bordered table-hover" style="font-size:15px;">
    <thead>
    <tr role="row" class="heading">
        <th width="100%" colspan="4">未收款項</th>
    </tr>
    </thead>

    <thead>
    <tr role="row" class="heading">
        <th width="20%">訂單</th>
        <th width="50%">客戶</th>
        <th width="15%">應收金額</th>
        <th width="15%">累計</th>
    </tr>
    </thead>
    <tbody>
    <?php foreach($backaccount as $row):?>
        <tr>
            <td><?php echo $row['invoiceNumber']; ?></td>
            <td><?php echo $row['name']; ?></td>
            <td>HK$ <?php echo $row['amount']; ?></td>
            <td>HK$ <?php echo $row['accumulator']; ?></td>
        </tr>
    <?php endforeach; ?>.
    <tr>
        <td colspan="4" style="text-align:right;">
            <span style="font-weight:bold;font-size:15px;">總計: HK$<?php echo end($backaccount)['accumulator']; ?></span>
        </td>
    <tr>
    </tbody>
</table>
