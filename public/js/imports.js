$(document).ready(function () {
    function toggleListGuest(input, list, filterInput) {
        input.on("click", function () {
            list.show();
        });

        $(document).click(function (event) {
            if (
                !$(event.target).closest(input).length &&
                !$(event.target).closest(filterInput).length
            ) {
                list.hide();
            }
        });

        var applyFilter = function () {
            var value = filterInput.val().toUpperCase();
            list.find("li").each(function () {
                var text = $(this).find("a").text().toUpperCase();
                $(this).toggle(text.indexOf(value) > -1);
            });
        };

        input.on("keyup", applyFilter);
        filterInput.on("keyup", applyFilter);
    }

    //nhà cung cấp
    toggleListGuest(
        $("#provider_name"),
        $("#listProvider"),
        $("#searchProvider")
    );
    //lấy tên nhà cung cấp và id
    $('a[name="search-info"]').on('click', function() {
        const dataId = $(this).attr('id');
        const dataName = $(this).data('name');
        $("#provider_id").val(dataId);
        $("#provider_name").val(dataName);
    });
});