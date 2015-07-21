
<table class="table table-bordered table-hover" style="font-size:15px;">
    <thead>
    <tr role="row" class="heading">
        <th width="20%">Invoice Qty</th>
        <th width="20%">Invoice Amount</th>
        <th width="20%">amount per invoice</th>
        <th width="20%">zoneId</th>
        <th width="20%">zoneName</th>

    </tr>
    </thead>
    <tbody>
    <?php foreach($data['zone'] as $row):?>
        <tr>
            <td><?php echo $row->qty; ?></td>
            <td><?php echo "$ ".number_format($row->amount); ?></td>
            <td><?php echo "$ ".number_format($row->amount/$row->qty); ?></td>
            <td><?php echo $row->zoneId; ?></td>
            <td><?php echo $row->zoneName; ?></td>

        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<table class="table table-bordered table-hover" style="font-size:15px;">
    <thead>
        <tr role="row" class="heading">
            <th width="20%">Updated amount</th>
            <th width="20%">staff Name</th>

        </tr>
    </thead>
    <tbody>
        <?php foreach($data['updated_amount'] as $row):?>
            <tr>
                <td><?php echo $row->updated_amount; ?></td>
                <td><?php echo $row->name; ?></td>
 </tr>
         <?php endforeach; ?>
    </tbody>
</table>

<table class="table table-bordered table-hover" style="font-size:15px;">
    <thead>
    <tr role="row" class="heading">
        <th width="20%">Created amount</th>
        <th width="20%">staff Name</th>

    </tr>
    </thead>
    <tbody>
    <?php foreach($data['created_amount'] as $row):?>
        <tr>
            <td><?php echo $row->created_amount; ?></td>
            <td><?php echo $row->name; ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>


