$(document).ready(function () {
    // Hiển thị danh sách tên sản phẩm
    $(document).on("click", ".product_code", function (e) {
        e.stopPropagation();
        $(".list_product").hide();

        var clickedRow = $(this).closest("tr");
        var listProduct = clickedRow.find(".list_product");
        listProduct.toggle();
        // Lọc danh sách khi nhập vào product_code
        $(document).on("input", ".product_code", function () {
            var keyword = $(this).val().toLowerCase(); // Lấy từ khóa người dùng nhập
            var clickedRow = $(this).closest("tr");
            var listProduct = clickedRow.find(".list_product");

            listProduct.find("li").each(function () {
                var productName = $(this).find("span").text().toLowerCase(); // Lấy tên sản phẩm
                if (productName.includes(keyword)) {
                    $(this).show(); // Hiển thị nếu khớp từ khóa
                } else {
                    $(this).hide(); // Ẩn nếu không khớp
                }
            });

            listProduct.show(); // Hiển thị danh sách đã lọc
        });
    });

    $(document).on("click", ".warranty-input", function (e) {
        e.stopPropagation(); // Ngăn việc click lan rộng
        $(".warranty-dropdown").hide(); // Ẩn tất cả dropdown khác

        const $clickedRow = $(this).closest("tr");
        const $dropdown = $clickedRow.find(".warranty-dropdown");
        $dropdown.toggle(); // Hiển thị hoặc ẩn dropdown hiện tại

        // Lọc danh sách khi nhập vào name_warranty
        $(document).on("input", ".warranty-input", function () {
            const keyword = $(this).val().toLowerCase(); // Từ khóa tìm kiếm
            const $row = $(this).closest("tr");
            const $dropdown = $row.find(".warranty-dropdown");
            $(this).next(".check-icon").text("");
            $dropdown.find("li").each(function () {
                const warrantyName = $(this)
                    .find(".warranty-name")
                    .text()
                    .toLowerCase();
                if (warrantyName.includes(keyword)) {
                    $(this).show(); // Hiển thị nếu khớp từ khóa
                } else {
                    $(this).hide(); // Ẩn nếu không khớp
                }
            });

            $dropdown.show(); // Hiển thị danh sách đã lọc
        });
    });

    // Chọn một mục trong dropdown
    // $(document).on("click", ".dropdown-link", function (e) {
    //     e.preventDefault();
    //     const $clickedItem = $(this);
    //     const $row = $clickedItem.closest("tr");
    //     const nameWarranty = $clickedItem.data("name_warranty");
    //     const $input = $row.find(".warranty-input");
    //     const idWarranty = $clickedItem.data("id_warranty");
    //     const $inputIdWarranty = $row.find(".id_warranty");
    //     const id_seri = $clickedItem.data("seri");
    //     const $inputIdSeri = $row.find(".id_seri");
    //     const $dropdown = $row.find(".warranty-dropdown");

    //     $input.val(nameWarranty);
    //     $inputIdWarranty.val(idWarranty);
    //     $inputIdSeri.val(id_seri);

    //     $dropdown.hide();
    // });

    // Ẩn dropdown khi click ngoài
    $(document).on("click", function () {
        $(".warranty-dropdown").hide();
    });

    // Xử lý sự kiện khi người dùng chọn sản phẩm
    $(document).on("click", ".idProduct", function (e) {
        e.preventDefault();
        // Lấy giá trị sản phẩm từ danh sách
        var productCode = $(this).find("span").text();
        var productName = $(this).data("name");
        var productBrand = $(this).data("brand");
        var productId = $(this).data("id");
        var clickedRow = $(this).closest("tr");
        // Gán giá trị vào input
        clickedRow.find(".product_code").val(productCode);
        clickedRow.find(".product_name").val(productName);
        clickedRow.find(".brand").val(productBrand);
        clickedRow.find(".product_id").val(productId);
        // Ẩn danh sách
        clickedRow.find(".list_product").hide();
    });

    // Ẩn danh sách khi click bên ngoài
    $(document).on("click", function (e) {
        if (!$(e.target).closest(".product_code, .list_product").length) {
            $(".list_product").hide();
        }
    });
});

$(document).on("click", ".btn-add-item", function () {
    // Lấy tbody chứa các hàng sản phẩm
    const tbody = $("#tbody-product-data");

    // Đếm số lượng hàng hiện tại trong tbody
    const currentRowCount = tbody.find("tr.row-product").length;

    // Gán data-index dựa trên số lượng hàng hiện tại
    const newIndex = currentRowCount;
    var newRow = `
        <tr class="row-product bg-white" data-index="${newIndex}" data-product-code="" data-product-id="">
            <td class="border-right p-2 text-13 align-top border-bottom border-top-0">
                <button type="button" data-modal-id="modal-id" data-toggle="modal"
                    data-target="#modal-id"
                    class="btn-copy-item d-flex align-items-center h-100 py-1 px-2 rounded activity ml-3"
                    style="margin-right:10px">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                        fill="currentColor" class="bi bi-copy" viewBox="0 0 16 16">
                        <path fill-rule="evenodd"
                            d="M4 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1zM2 5a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1v-1h1v1a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h1v1z" />
                    </svg>
                </button>
            </td>
            <td class="border-right p-2 text-13 align-top border-bottom border-top-0 d-none">
                <input type="text" autocomplete="off" class="border-0 pl-1 pr-2 py-1 w-100 product_id height-32" readonly=""
                    name="product_id[${newIndex}][product_id]" value="">
            </td>
            <td class="border-right position-relative p-2 text-13 align-top border-bottom border-top-0">
                <input type="text" autocomplete="off" class="border-0 pl-1 pr-2 py-1 w-100 product_code height-32 bg-input-guest-blue" placeholder="Tìm mã hàng"
                     value="">
                <ul class="list_product bg-white position-absolute w-100 rounded shadow p-0 scroll-data"
                    style="z-index: 99;top: 75%;left: 1.5rem;display: none;">
                </ul>
            </td>
             <td class="border-right p-2 text-13 align-top border-bottom border-top-0">
                <input type="text" autocomplete="off"
                    class="border-0 pl-1 pr-2 py-1 w-100 product_name height-32"
                    readonly="" value="">
            </td>
            <td class="border-right p-2 text-13 align-top border-bottom border-top-0">
                <input type="text" autocomplete="off"
                    class="border-0 pl-1 pr-2 py-1 w-100 brand height-32" readonly=""
                    value="">
            </td>
            <td class="border-right p-2 text-13 align-top border-bottom border-top-0">
                <input type="text" autocomplete="off"
                    class="border-0 pl-1 pr-2 text-center py-1 w-100 height-32" readonly=""
                    value="1">
            </td>
            <td
                class="border-right p-2 text-13 align-top border-bottom border-top-0 position-relative d-flex align-items-center">
                <input type="text" autocomplete="off"
                    class="border-0 pl-1 pr-2 py-1 w-100 serial height-32 bg-input-guest-blue"
                    name="product_id[${newIndex}][serial]" data-index="${newIndex}" value="">
                <span class="check-icon-seri"></span>
                <span class="date-export"></span>
            </td>
            <td
                class="border-right p-2 text-13 align-top border-bottom border-top-0 product-cell position-relative">
                <input type="hidden" autocomplete="off"
                    class="border-0 pl-1 pr-2 py-1 w-100 id_seri height-32"
                    name="product_id[${newIndex}][id_seri][]" data-index="${newIndex}" value="">
                <input type="hidden" autocomplete="off"
                    class="border-0 pl-1 pr-2 py-1 w-100 id_warranty height-32"
                    name="product_id[${newIndex}][id_warranty][]" data-index="${newIndex}" value="">
                <input type="text" autocomplete="off"
                    class="border-0 pl-1 pr-2 py-1 w-100 warranty-input name_warranty height-32 bg-input-guest-blue"
                    name="product_id[${newIndex}][name_warranty][]" data-index="${newIndex}" value="">
                    <span class="check-icon"></span>
                <ul class='warranty-dropdown bg-white position-absolute w-100 rounded shadow p-0 scroll-data'
                    style='z-index: 99;top: 75%;display: none;'>
                </ul>
            </td>
            <td class="border-right p-2 text-13 align-top border-bottom border-top-0">
                <input type="text" autocomplete="off"
                    class="border-0 pl-1 pr-2 py-1 w-100 warranty height-32 bg-input-guest-blue"
                    name="product_id[${newIndex}][warranty][]" data-index="${newIndex}" value="">
            </td>
            <td class="border-right p-2 text-13 align-top border-bottom border-top-0">
                <input type="text" autocomplete="off"
                    class="border-0 pl-1 pr-2 py-1 w-100 note_seri height-32 bg-input-guest-blue"
                    name="product_id[${newIndex}][note_seri][]" data-index="${newIndex}" value="">
            </td>
            <td class="p-2 align-top border-bottom border-top-0 border-right">
             <svg class="delete-row" width="17" height="17" viewBox="0 0 17 17" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M13.1417 6.90625C13.4351 6.90625 13.673 7.1441 13.673 7.4375C13.673 7.47847 13.6682 7.5193 13.6589 7.55918L12.073 14.2992C11.8471 15.2591 10.9906 15.9375 10.0045 15.9375H6.99553C6.00943 15.9375 5.15288 15.2591 4.92702 14.2992L3.34113 7.55918C3.27393 7.27358 3.45098 6.98757 3.73658 6.92037C3.77645 6.91099 3.81729 6.90625 3.85826 6.90625H13.1417ZM9.03125 1.0625C10.4983 1.0625 11.6875 2.25175 11.6875 3.71875H13.8125C14.3993 3.71875 14.875 4.19445 14.875 4.78125V5.3125C14.875 5.6059 14.6371 5.84375 14.3438 5.84375H2.65625C2.36285 5.84375 2.125 5.6059 2.125 5.3125V4.78125C2.125 4.19445 2.6007 3.71875 3.1875 3.71875H5.3125C5.3125 2.25175 6.50175 1.0625 7.96875 1.0625H9.03125ZM9.03125 2.65625H7.96875C7.38195 2.65625 6.90625 3.13195 6.90625 3.71875H10.0938C10.0938 3.13195 9.61805 2.65625 9.03125 2.65625Z"
                        fill="#6B6F76"></path>
                </svg>
            </td>
        </tr>
        <tr class="bg-white row-warranty" data-index="${newIndex}" style="display: none" data-product-code="" data-product-id="">
            <td colspan="6" class="border-right p-2 text-13 align-top border-bottom border-top-0"></td>
            <td class="border-right p-2 text-13 align-top border-bottom border-top-0">
                <button type="button" class="btn-add-warranty btn">+</button>
            </td>
            <td colspan="3" class="border-right p-2 text-13 align-top border-bottom border-top-0"></td>
        </tr>
    `;

    // Thêm hàng mới vào <tbody>
    $("#tbody-product-data").append(newRow);

    // Tạo danh sách sản phẩm từ mảng products
    var productList = "";
    products.forEach((product) => {
        productList += `
            <li data-id="${product.id}">
                <a href="javascript:void(0);" class="text-dark d-flex justify-content-between p-2 idProduct w-100"
                    data-name="${product.product_name}" data-brand="${product.brand}" data-id="${product.id}">
                    <span class="w-50 text-13-black" style="flex:2">${product.product_code}</span>
                </a>
            </li>
        `;
    });

    // Gán danh sách vào ul.list_product
    $("#tbody-product-data").find("ul.list_product").last().html(productList);
});

$(document).on("click", ".btn-copy-item", function () {
    // Lấy tbody chứa các hàng sản phẩm
    const tbody = $("#tbody-product-data");
    let currentRow = $(this).closest("tr");
    let currentRowIndex = currentRow.data("index");
    // Đếm số lượng hàng hiện tại trong tbody
    const currentRowCount = tbody.find("tr.row-product").length;

    // Gán data-index dựa trên số lượng hàng hiện tại
    const newIndex = currentRowCount;
    var newRow = `
        <tr class="row-product bg-white" data-index="${newIndex}" data-product-code="" data-product-id="">
            <td class="border-right p-2 text-13 align-top border-bottom border-top-0">
                <button type="button" data-modal-id="modal-id" data-toggle="modal"
                    data-target="#modal-id"
                    class="btn-copy-item d-flex align-items-center h-100 py-1 px-2 rounded activity ml-3"
                    style="margin-right:10px">
                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                        fill="currentColor" class="bi bi-copy" viewBox="0 0 16 16">
                        <path fill-rule="evenodd"
                            d="M4 2a2 2 0 0 1 2-2h8a2 2 0 0 1 2 2v8a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2zm2-1a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1V2a1 1 0 0 0-1-1zM2 5a1 1 0 0 0-1 1v8a1 1 0 0 0 1 1h8a1 1 0 0 0 1-1v-1h1v1a2 2 0 0 1-2 2H2a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h1v1z" />
                    </svg>
                </button>
            </td>
            <td class="border-right p-2 text-13 align-top border-bottom border-top-0 d-none">
                <input type="text" autocomplete="off" class="border-0 pl-1 pr-2 py-1 w-100 product_id height-32" readonly=""
                    name="product_id[${newIndex}][product_id]" value="">
            </td>
            <td class="border-right position-relative p-2 text-13 align-top border-bottom border-top-0">
                <input type="text" autocomplete="off" class="border-0 pl-1 pr-2 py-1 w-100 product_code height-32 bg-input-guest-blue" placeholder="Tìm mã hàng"
                     value="">
                <ul class="list_product bg-white position-absolute w-100 rounded shadow p-0 scroll-data"
                    style="z-index: 99;top: 75%;left: 1.5rem;display: none;">
                </ul>
            </td>
             <td class="border-right p-2 text-13 align-top border-bottom border-top-0">
                <input type="text" autocomplete="off"
                    class="border-0 pl-1 pr-2 py-1 w-100 product_name height-32"
                    readonly="" value="">
            </td>
            <td class="border-right p-2 text-13 align-top border-bottom border-top-0">
                <input type="text" autocomplete="off"
                    class="border-0 pl-1 pr-2 py-1 w-100 brand height-32" readonly=""
                    value="">
            </td>
            <td class="border-right p-2 text-13 align-top border-bottom border-top-0">
                <input type="text" autocomplete="off"
                    class="border-0 pl-1 pr-2 text-center py-1 w-100 height-32" readonly=""
                    value="1">
            </td>
            <td
                class="border-right p-2 text-13 align-top border-bottom border-top-0 position-relative d-flex align-items-center">
                <input type="text" autocomplete="off"
                    class="border-0 pl-1 pr-2 py-1 w-100 serial height-32 bg-input-guest-blue"
                    name="product_id[${newIndex}][serial]" data-index="${newIndex}" value="">
                <span class="check-icon-seri"></span>
                <span class="date-export"></span>
            </td>
            <td
                class="border-right p-2 text-13 align-top border-bottom border-top-0 product-cell position-relative">
                <input type="hidden" autocomplete="off"
                    class="border-0 pl-1 pr-2 py-1 w-100 id_seri height-32"
                    name="product_id[${newIndex}][id_seri][]" data-index="${newIndex}" value="">
                <input type="hidden" autocomplete="off"
                    class="border-0 pl-1 pr-2 py-1 w-100 id_warranty height-32"
                    name="product_id[${newIndex}][id_warranty][]" data-index="${newIndex}" value="">
                <input type="text" autocomplete="off"
                    class="border-0 pl-1 pr-2 py-1 w-100 warranty-input name_warranty height-32 bg-input-guest-blue"
                    name="product_id[${newIndex}][name_warranty][]" data-index="${newIndex}" value="">
                    <span class="check-icon"></span>
                <ul class='warranty-dropdown bg-white position-absolute w-100 rounded shadow p-0 scroll-data'
                    style='z-index: 99;top: 75%;display: none;'>
                </ul>
            </td>
            <td class="border-right p-2 text-13 align-top border-bottom border-top-0">
                <input type="text" autocomplete="off"
                    class="border-0 pl-1 pr-2 py-1 w-100 warranty height-32 bg-input-guest-blue"
                    name="product_id[${newIndex}][warranty][]" data-index="${newIndex}" value="">
            </td>
            <td class="border-right p-2 text-13 align-top border-bottom border-top-0">
                <input type="text" autocomplete="off"
                    class="border-0 pl-1 pr-2 py-1 w-100 note_seri height-32 bg-input-guest-blue"
                    name="product_id[${newIndex}][note_seri][]" data-index="${newIndex}" value="">
            </td>
            <td class="p-2 align-top border-bottom border-top-0 border-right">
             <svg class="delete-row" width="17" height="17" viewBox="0 0 17 17" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M13.1417 6.90625C13.4351 6.90625 13.673 7.1441 13.673 7.4375C13.673 7.47847 13.6682 7.5193 13.6589 7.55918L12.073 14.2992C11.8471 15.2591 10.9906 15.9375 10.0045 15.9375H6.99553C6.00943 15.9375 5.15288 15.2591 4.92702 14.2992L3.34113 7.55918C3.27393 7.27358 3.45098 6.98757 3.73658 6.92037C3.77645 6.91099 3.81729 6.90625 3.85826 6.90625H13.1417ZM9.03125 1.0625C10.4983 1.0625 11.6875 2.25175 11.6875 3.71875H13.8125C14.3993 3.71875 14.875 4.19445 14.875 4.78125V5.3125C14.875 5.6059 14.6371 5.84375 14.3438 5.84375H2.65625C2.36285 5.84375 2.125 5.6059 2.125 5.3125V4.78125C2.125 4.19445 2.6007 3.71875 3.1875 3.71875H5.3125C5.3125 2.25175 6.50175 1.0625 7.96875 1.0625H9.03125ZM9.03125 2.65625H7.96875C7.38195 2.65625 6.90625 3.13195 6.90625 3.71875H10.0938C10.0938 3.13195 9.61805 2.65625 9.03125 2.65625Z"
                        fill="#6B6F76"></path>
                </svg>
            </td>
        </tr>
        <tr class="bg-white row-warranty" data-index="${newIndex}" style="display: none" data-product-code="" data-product-id="">
            <td colspan="6" class="border-right p-2 text-13 align-top border-bottom border-top-0"></td>
            <td class="border-right p-2 text-13 align-top border-bottom border-top-0">
                <button type="button" class="btn-add-warranty btn">+</button>
            </td>
            <td colspan="3" class="border-right p-2 text-13 align-top border-bottom border-top-0"></td>
        </tr>
    `;

    // Thêm hàng mới vào <tbody>
    const afterRow = $(`tr[data-index="${currentRowIndex}"]`).last();
    afterRow.after(newRow);

    // Copy dữ liệu từ hàng hiện tại sang hàng mới
    let productId = currentRow.find(".product_id").val();
    let productCode = currentRow.find(".product_code").val();
    let productName = currentRow.find(".product_name").val();
    let brand = currentRow.find(".brand").val();

    let insertedRow = $(`tr.row-product[data-index="${newIndex}"]`);
    insertedRow.find(".product_id").val(productId);
    insertedRow.find(".product_code").val(productCode);
    insertedRow.find(".product_name").val(productName);
    insertedRow.find(".brand").val(brand);

    insertedRow.addClass("row-highlight");
    // Xoá class sau 3 giây
    setTimeout(() => {
        insertedRow.removeClass("row-highlight");
    }, 5000);
    // Tạo danh sách sản phẩm từ mảng products
    var productList = "";
    products.forEach((product) => {
        productList += `
            <li data-id="${product.id}">
                <a href="javascript:void(0);" class="text-dark d-flex justify-content-between p-2 idProduct w-100"
                    data-name="${product.product_name}" data-brand="${product.brand}" data-id="${product.id}">
                    <span class="w-50 text-13-black" style="flex:2">${product.product_code}</span>
                </a>
            </li>
        `;
    });

    // Gán danh sách vào ul.list_product

    // Tìm ul.list_product trong hàng vừa thêm và gán danh sách
    insertedRow.find("ul.list_product").html(productList);
});


$(document).on("click", ".btn-add-warranty", function () {
    // Lấy hàng hiện tại của nút vừa bấm
    const currentRow = $(this).closest("tr");
    const index = currentRow.data("index"); // Lấy data-index
    // Lấy thông tin data-product-code và data-product-id nếu cần
    const productCode = currentRow.attr("data-product-code");
    const productId = currentRow.attr("data-product-id");
    const seri = currentRow.attr("data-seri");
    // Tạo hàng mới
    const newRow = $(`
        <tr class="row-warranty bg-white" data-index="${index}" data-product-code="${productCode}" data-product-id="${productId}">
            <td colspan="6" class="border-right p-2 text-13 align-top border-bottom border-top-0">
              <input type="hidden" autocomplete="off"
                class="border-0 pl-1 pr-2 py-1 w-100 product_code height-32"
                placeholder="Tìm mã hàng"
                value="${productCode}"
                readonly>
            <input type="hidden" autocomplete="off"
                class="border-0 pl-1 pr-2 py-1 w-100 serial height-32"
                value="${seri}">
            <input type="hidden" autocomplete="off"
                class="border-0 pl-1 pr-2 py-1 w-100 product_id height-32"
                value="${productId}">
            </td>
            <td
                class="border-right p-2 text-13 align-top border-bottom border-top-0 product-cell position-relative">
                <input type="hidden" autocomplete="off"
                    class="border-0 pl-1 pr-2 py-1 w-100 id_seri height-32"
                    name="product_id[${index}][id_seri][]" data-index="${index}" value="">
                <input type="hidden" autocomplete="off"
                    class="border-0 pl-1 pr-2 py-1 w-100 id_warranty height-32"
                    name="product_id[${index}][id_warranty][]" data-index="${index}" value="">
                <input type="text" autocomplete="off"
                    class="border-0 pl-1 pr-2 py-1 w-100 warranty-input name_warranty height-32 bg-input-guest-blue"
                    name="product_id[${index}][name_warranty][]" data-index="${index}" value="">
                    <span class="check-icon"></span>
                <ul class='warranty-dropdown bg-white position-absolute w-100 rounded shadow p-0 scroll-data'
                    style='z-index: 99;top: 75%;display: none;'>
                </ul>
            </td>
            <td class="border-right p-2 text-13 align-top border-bottom border-top-0">
                <input type="text" autocomplete="off"
                    class="border-0 pl-1 pr-2 py-1 w-100 warranty height-32 bg-input-guest-blue"
                    name="product_id[${index}][warranty][]" data-index="${index}" value="">
            </td>
            <td class="border-right p-2 text-13 align-top border-bottom border-top-0">
                <input type="text" autocomplete="off"
                    class="border-0 pl-1 pr-2 py-1 w-100 note_seri height-32 bg-input-guest-blue"
                    name="product_id[${index}][note_seri][]" data-index="${index}" value="">
            </td>
            <td class="p-2 align-top border-bottom border-top-0 border-right">
            <svg class="delete-row" width="17" height="17" viewBox="0 0 17 17" fill="none"
                    xmlns="http://www.w3.org/2000/svg">
                    <path fill-rule="evenodd" clip-rule="evenodd"
                        d="M13.1417 6.90625C13.4351 6.90625 13.673 7.1441 13.673 7.4375C13.673 7.47847 13.6682 7.5193 13.6589 7.55918L12.073 14.2992C11.8471 15.2591 10.9906 15.9375 10.0045 15.9375H6.99553C6.00943 15.9375 5.15288 15.2591 4.92702 14.2992L3.34113 7.55918C3.27393 7.27358 3.45098 6.98757 3.73658 6.92037C3.77645 6.91099 3.81729 6.90625 3.85826 6.90625H13.1417ZM9.03125 1.0625C10.4983 1.0625 11.6875 2.25175 11.6875 3.71875H13.8125C14.3993 3.71875 14.875 4.19445 14.875 4.78125V5.3125C14.875 5.6059 14.6371 5.84375 14.3438 5.84375H2.65625C2.36285 5.84375 2.125 5.6059 2.125 5.3125V4.78125C2.125 4.19445 2.6007 3.71875 3.1875 3.71875H5.3125C5.3125 2.25175 6.50175 1.0625 7.96875 1.0625H9.03125ZM9.03125 2.65625H7.96875C7.38195 2.65625 6.90625 3.13195 6.90625 3.71875H10.0938C10.0938 3.13195 9.61805 2.65625 9.03125 2.65625Z"
                        fill="#6B6F76"></path>
                </svg>
            </td>
        </tr>`);
    currentRow.before(newRow);
    const $dropdownList = newRow.find(".warranty-dropdown");

    if (responseData[index]) {
        populateWarrantyDropdown(responseData[index], $dropdownList);
    } else {
        console.log(`Không có dữ liệu trong responseData cho index ${index}`);
    }
});

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
    toggleListGuest(
        $("#customer_name"),
        $("#listCustomer"),
        $("#searchCustomer")
    );
    $('a[name="search-info"]').on("click", function () {
        const dataId = $(this).attr("id");
        const dataName = $(this).data("name");
        const phone = $(this).data("phone");
        const address = $(this).data("address");
        const contact = $(this).data("contact");
        $("#customer_id").val(dataId);
        $("#customer_name").val(dataName);
        $('[name="phone"]').val(phone);
        $('[name="contact_person"]').val(contact);
        $('[name="address"]').val(address);
    });
});
flatpickr("#dateCreate", {
    locale: "vn",
    dateFormat: "d/m/Y",
    defaultDate: "today",
    onChange: function (selectedDates) {
        // Lấy giá trị ngày đã chọn
        if (selectedDates.length > 0) {
            const formattedDate = flatpickr.formatDate(
                selectedDates[0],
                "Y-m-d"
            );
            document.getElementById("hiddenDateCreate").value = formattedDate;
        } else {
            // Nếu không chọn thì mặc định là ngày hôm nay
            const today = new Date();
            const formattedToday = flatpickr.formatDate(today, "Y-m-d");
            document.getElementById("hiddenDateCreate").value = formattedToday;
        }
    },
});
// Hàm để đổ dữ liệu vào danh sách dropdown
function populateWarrantyDropdown(response, dropdownElement) {
    // Kiểm tra nếu response và danh sách warranty tồn tại
    if (response && response.warranty) {
        // Làm sạch danh sách dropdown trước khi thêm dữ liệu mới
        dropdownElement.empty();

        // Lặp qua từng item trong danh sách warranty và thêm vào dropdown
        response.warranty.forEach((item) => {
            const listItem = `
                <li data-id="${item.id}">
                    <a href="javascript:void(0);"
                        class="dropdown-link text-dark d-flex justify-content-between p-2 w-100"
                        data-name_warranty="${item.name_warranty}"
                        data-warranty="${item.warranty}"
                        data-id_warranty="${item.id}"
                        data-status="${item.status}"
                        data-seri="${item.sn_id}">
                        <span class="warranty-name text-13-black w-50" style="flex:2">${item.name_warranty}</span>
                    </a>
                </li>`;
            dropdownElement.append(listItem);
        });
    }
}
function showRowWarrantyByIndex(index) {
    const $rowWarranty = $(`.row-warranty[data-index="${index}"]`);
    if ($rowWarranty.length > 0) {
        $rowWarranty.show();
    }
}

// Xử lý chọn bảo hành từ danh sách dropdown
$(document).on("click", ".dropdown-link", function (e) {
    e.preventDefault();
    const $this = $(this);
    const $row = $this.closest("tr");

    const nameWarranty = $this.data("name_warranty");
    const idWarranty = $this.data("id_warranty");
    const idSeri = $this.data("seri");

    const $inputWarranty = $row.find(".warranty-input");
    const $inputIdWarranty = $row.find(".id_warranty");
    const $inputIdSeri = $row.find(".id_seri");
    const $checkIcon = $inputWarranty.siblings(".check-icon");
    const $dropdown = $row.find(".warranty-dropdown");

    // Đổ dữ liệu vào các ô input tương ứng
    $inputWarranty.val(nameWarranty);
    $inputIdWarranty.val(idWarranty);
    $inputIdSeri.val(idSeri);

    // Ẩn dropdown sau khi chọn
    $dropdown.hide();

    // Lấy giá trị kiểm tra
    const value_checked = getCheckedValue();
    // Gọi hàm kiểm tra
    checkSerials(value_checked.formType, idSeri, idWarranty, $checkIcon);
});

function getCheckedValue() {
    const formType = $('input[name="form_type"]:checked').val();
    return {
        formType: formType,
    };
}

function checkSerials(formType, serialData, warranty, checkIcon) {
    $.ajax({
        url: "/check-serials",
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
        data: {
            form_type: formType,
            serials: serialData,
            warranty: warranty,
        },
        success: function (response) {
            if (response.status === "success") {
                checkIcon.text("✔").css("color", "green");
            } else if (response.status === "error") {
                checkIcon.text("✖").css("color", "red");
            }
        },
        error: function () { },
    });
}

function checkbranchId(serials, product_id, className, checkIcon) {
    $.ajax({
        url: "/check-brands",
        method: "POST",
        headers: {
            "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
        },
        data: {
            serials: serials,
            product_id: product_id,
        },
        success: function (response) {
            console.log(response.message);
            // Xóa lớp màu cũ trước khi thêm lớp mới
            className.removeClass("internal errorinput bg-input-guest-blue");
            if (response.status === "success") {
                className
                    .addClass("internal")
                    .attr("title", "Sản phẩm này thuộc nội bộ");
            } else if (response.status === "external") {
                className
                    .addClass("bg-input-guest-blue")
                    .attr("title", "Sản phẩm này thuộc bên ngoài");
            } else {
                className
                    .addClass("errorinput")
                    .attr("title", response.message);
                checkIcon.text("✖").css("color", "red");
            }
        },
        error: function () {
            console.error("Lỗi khi kiểm tra dữ liệu.");
        },
    });
}

// Check for duplicate serials when input changes
let enteredSerials = [];

$(document).on('click', '.date-export i', (e) => {
    e.stopPropagation();
    const $dateExport = $(e.target).closest('.date-export');
    $dateExport.find('.date-popup').toggle();
});

$(document).on('click', (e) => {
    if (!$(e.target).closest('.date-export').length) {
        $('.date-popup').hide();
    }
});

$(document).on("blur", ".serial", function () {
    const currentInput = $(this);
    const currentValue = currentInput.val().trim();
    const product_id = currentInput.closest("tr").find(".product_id").val();
    const checkIcon = currentInput.siblings(".check-icon-seri");

    // Xóa icon nếu input rỗng
    checkIcon.text("");
    if (!currentValue) {
        currentInput.removeClass("internal external");
        currentInput.addClass("bg-input-guest-blue");
        return;
    }

    // Lấy danh sách tất cả serials hiện tại trong bảng
    let currentSerials = [];
    $(".serial").each(function () {
        let value = $(this).val().trim();
        if (value) {
            currentSerials.push(value);
        }
    });

    // Kiểm tra serial bị trùng trong danh sách
    let duplicateCount = currentSerials.filter(
        (value) => value === currentValue
    ).length;

    if (duplicateCount > 1) {
        checkIcon
            .text("✖")
            .css("color", "red")
            .attr("title", "Serial đã tồn tại trong danh sách!");
        return;
    }

    // Gửi request AJAX để kiểm tra serial nội bộ hoặc bên ngoài
    if (typeof checkbranchId === "function") {
        checkbranchId(currentValue, product_id, currentInput, checkIcon);
    }
    // Tự động click vào warranty-input sau khi blur
    const $row = currentInput.closest("tr");
    const $warrantyInput = $row.find(".warranty-input");
    if ($warrantyInput.length) {
        $warrantyInput.trigger("click");
    }
});

$(document).ready(function () {
    $("#btn-get-unique-products").on("click", function (e) {
        e.preventDefault(); // Ngăn chặn form submit ngay lập tức

        const idcus = $("#customer_id").val();
        if (!idcus) {
            showAutoToast("warning", "Vui lòng chọn khách hàng!");
            $("#customer_name").click();
            return false;
        }

        if (
            $(".check-icon-seri, .check-icon").filter(function () {
                return $(this).text().trim() === "✖";
            }).length > 0
        ) {
            showAutoToast(
                "warning",
                "Dữ liệu không hợp lệ vui lòng kiểm tra lại"
            );
            return;
        }

        // Kiểm tra name_warranty
        // let hasEmptyWarranty = false;
        // $(".name_warranty").each(function () {
        //     if ($(this).val().trim() === "") {
        //         hasEmptyWarranty = true;
        //         return false; // break loop
        //     }
        // });

        // if (hasEmptyWarranty) {
        //     showAutoToast("warning", "Vui lòng nhập thông tin bảo hành");
        //     return;
        // }

        let serials = [];
        $(".serial").each(function () {
            let serial = $(this).val().trim();
            if (serial !== "") {
                serials.push(serial);
            }
        });

        if (serials.length === 0) {
            showAutoToast("warning", "Vui lòng nhập ít nhất một số serial.");
            return;
        }

        $.ajax({
            url: "/check-brands",
            type: "POST",
            data: { serials: serials },
            dataType: "json",
            headers: {
                "X-CSRF-TOKEN": $('meta[name="csrf-token"]').attr("content"),
            },
            success: function (response) {
                let valuesr = Object.values(response);

                // Loại bỏ các object có key là "undefined"
                let filteredValues = valuesr.filter((item, index) => Object.keys(response)[index] !== "undefined");

                // Lấy danh sách trạng thái
                let statuses = filteredValues.map((item) => item.status);

                console.log(filteredValues);

                let allInternal = statuses.every((status) => status === "success"); // Tất cả là nội bộ
                let allExternal = statuses.every((status) => status === "external"); // Tất cả là bên ngoài

                // 👉 CHỈ kiểm tra name_warranty với hàng nội bộ
                let hasEmptyWarranty = false;

                $(".row-product").each(function (index) {
                    let status = statuses[index];
                    if (status === "success") { // chỉ kiểm tra hàng nội bộ
                        let warranty = $(this).find(".name_warranty").val()?.trim() || "";
                        if (warranty === "") {
                            hasEmptyWarranty = true;
                            return false; // dừng vòng lặp
                        }
                    }
                });

                if (hasEmptyWarranty) {
                    showAutoToast("warning", "Vui lòng nhập thông tin bảo hành cho hàng nội bộ");
                    return;
                }

                // if (allInternal) {
                //     $("#branch_id").val(1); // Nội bộ
                // } else if (allExternal) {
                //     $("#branch_id").val(2); // Bên ngoài
                // } else {
                //     showAutoToast(
                //         "warning",
                //         "Tất cả serials phải là hàng nội bộ hoặc hàng bên ngoài. Vui lòng kiểm tra lại!"
                //     );
                //     return;
                // }

                $("#form-submit").submit();
            },
            error: function () {
                showAutoToast("warning", "Có lỗi, vui lòng thử lại");
            },
        });
    });
});
