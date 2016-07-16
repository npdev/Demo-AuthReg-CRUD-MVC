<script type="text/javascript">
    $(document).ready(function () {
        $('#additem').click(function () {
            $.ajax({
                url: "/additem",
                dataType: "html",
                method: "POST",
                data: $("tr.add-form input[name]"),
                success: function (html) {
                    $("tr").eq(-2).after(html);
                }
            });
        });
        $(document).on('click', 'i.removeitem', function () {
            var itemId = $(this).closest("tr").data("itemid");
            $.ajax({
                url: "/remove",
                dataTypr: "html",
                method: "GET",
                data: {"id": itemId},
                success: function () {
                    $("tr[data-itemid=" + itemId + "]").remove();
                }
            });
        });
        $(document).on('click', 'i.edititem', function () {
            var item = $(this).closest("tr");
            var itemId = item.data("itemid");
            var data = {
                name: item.children("td[data-field=name]").text(),
                description: item.children("td[data-field=description]").text(),
                price: item.children("td[data-field=price]").text(),
            };
            $.ajax({
                url: "/edititem",
                dataType: "html",
                method: "POST",
                data: data,
                success: function (html) {
                    $("tr[data-itemid=" + itemId + "]").html(html);
                }
            });
        });
        $(document).on('click', "input.save", function () {
            var item = $(this).closest("tr");
            var data = {
                id: item.data("itemid"),
                name: item.find("input[name=name]").val(),
                description: item.find("input[name=description]").val(),
                price: item.find("input[name=price]").val()
            };
            $.ajax({
                url: "/saveediteditem",
                dataType: "html",
                method: "POST",
                data: data,
                success: function (html) {
                    item.replaceWith(html);
                }
            });
        });
    });
</script>
<table class="table table-bordered table-condensed">
    <tr>
        <th>Name</th>
        <th>Description</th>
        <th>Price</th>
        <th>Action</th>
    </tr>
    <?php
    if (!empty($data)) {
        foreach ($data as $key => $row) {
            $this->render('add-item.php', '', $row);
        }
    }
    ?>
    <tr class="add-form">
        <td><input type="text" name="name" placeholder="Name" form='data'></td>
        <td><input type="text" name="description" placeholder="Description" form='data'></td>
        <td><input type="text" name="price" placeholder="Price *.**" form='data'></td>
        <td><i id="additem" class='icon-plus'></i> </td>
    </tr>
</table>
