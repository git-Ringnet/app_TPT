<div class="modal fade" id="{{ $id }}" tabindex="-1" role="dialog" aria-labelledby="{{ $id }}"
    aria-hidden="true">
    <input type="hidden" name="modal_id" value="{{ $id }}">
    <div class="modal-dialog modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header justify-content-end">
                <div class="d-flex content__heading--right">
                    <div class="row m-0">
                        <a href="#">
                            <button type="button" data-dismiss="modal"
                                class="btn-destroy btn-light mx-1 d-flex align-items-center h-100">
                                <svg class="mx-1" width="16" height="16" viewBox="0 0 16 16" fill="none"
                                    xmlns="http://www.w3.org/2000/svg">
                                    <path fill-rule="evenodd" clip-rule="evenodd"
                                        d="M8 15C11.866 15 15 11.866 15 8C15 4.13401 11.866 1 8 1C4.13401 1 1 4.13401 1 8C1 11.866 4.13401 15 8 15ZM6.03033 4.96967C5.73744 4.67678 5.26256 4.67678 4.96967 4.96967C4.67678 5.26256 4.67678 5.73744 4.96967 6.03033L6.93934 8L4.96967 9.96967C4.67678 10.2626 4.67678 10.7374 4.96967 11.0303C5.26256 11.3232 5.73744 11.3232 6.03033 11.0303L8 9.06066L9.96967 11.0303C10.2626 11.3232 10.7374 11.3232 11.0303 11.0303C11.3232 10.7374 11.3232 10.2626 11.0303 9.96967L9.06066 8L11.0303 6.03033C11.3232 5.73744 11.3232 5.26256 11.0303 4.96967C10.7374 4.67678 10.2626 4.67678 9.96967 4.96967L8 6.93934L6.03033 4.96967Z"
                                        fill="black" />
                                </svg>
                                <p class="m-0 p-0 text-dark">Hủy</p>
                            </button>
                        </a>
                        <button type="" class="custom-btn d-flex align-items-center h-100 ml-1">
                            <svg class="mx-1" xmlns="http://www.w3.org/2000/svg" width="16" height="16"
                                viewBox="0 0 16 16" fill="none">
                                <path fill-rule="evenodd" clip-rule="evenodd"
                                    d="M8 15C11.866 15 15 11.866 15 8C15 4.13401 11.866 1 8 1C4.13401 1 1 4.13401 1 8C1 11.866 4.13401 15 8 15ZM11.7836 6.42901C12.0858 6.08709 12.0695 5.55006 11.7472 5.22952C11.4248 4.90897 10.9186 4.9263 10.6164 5.26821L7.14921 9.19122L5.3315 7.4773C5.00127 7.16593 4.49561 7.19748 4.20208 7.54777C3.90855 7.89806 3.93829 8.43445 4.26852 8.74581L6.28032 10.6427C6.82041 11.152 7.64463 11.1122 8.13886 10.553L11.7836 6.42901Z"
                                    fill="white" />
                            </svg>
                            <p class="m-0 p-0 submit-button">Xác nhận</p>
                        </button>
                    </div>
                </div>
            </div>
            <div class="content-wrapper2 px-0 py-0">
                {{-- Thông tin hàng --}}
                <div class="border">
                    <div>
                        <div class="bg-filter-search border-0 d-flex justify-content-center align-items-center"
                            style="height:40px">
                            <p class="font-weight-bold text-uppercase mb-0" style="line-height: 40px;">
                                THÔNG TIN HÀNG
                            </p>
                        </div>
                        <table class="info-product">
                            <thead class="border-custom border-bottom">
                                <tr class="">
                                    <th class="height-40 py-0 border pl-3" style="width:25%">Mã hàng</th>
                                    <th class="height-40 py-0 border pl-3" style="width:50%">Tên hàng</th>
                                    <th class="height-40 py-0 border pl-3" style="width:25%">Hãng</th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr class="height-40">
                                    <td class="text-13-black border py-0 pl-3">
                                        <input type="text" id="product_code_input" name="product_code_input"
                                            style="flex:2;" placeholder="Nhập thông tin"
                                            class="text-13-black w-100 border-0">
                                    </td>
                                    <td class="text-13-black border py-0 pl-3">
                                        <input type="text" id="product_name_input" name="product_name_input"
                                            style="flex:2;" placeholder="Nhập thông tin"
                                            class="text-13-black w-100 border-0">
                                    </td>
                                    <td class="text-13-black border py-0 pl-3">
                                        <input type="text" id="product_brand_input" name="product_brand_input"
                                            style="flex:2;" placeholder="Nhập thông tin"
                                            class="text-13-black w-100 border-0">
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                {{-- Thông tin SERIAL NUMBER --}}
                <div class="border">
                    <div>
                        <div class="bg-filter-search border-0 d-flex justify-content-center align-items-center"
                            style="height:40px">
                            <p class="font-weight-bold text-uppercase mb-0" style="line-height: 40px;">
                                THÔNG TIN SERIAL NUMBER
                            </p>
                        </div>
                        <table class="info-serial w-100">
                            <thead class="border-custom border-bottom">
                                <tr class="">
                                    <th class="height-40 py-0 border text-center" style="width:15%">STT</th>
                                    <th class="height-40 py-0 border pl-3" style="width:70%">Serial Number</th>
                                    <th class="height-40 py-0 border pl-3" style="width:15%"></th>
                                </tr>
                            </thead>
                            <tbody id="table-body">
                                <tr class="height-40">
                                    <td class="text-13-black border py-0 text-center">01</td>
                                    <td class="text-13-black border py-0 pl-3">
                                        <input type="text" id="form_code" name="form_code" style="flex:2;"
                                            placeholder="Nhập thông tin" class="text-13-black w-100 border-0">
                                    </td>
                                    <td class="text-13-black border py-0 text-center">
                                        <button class="btn btn-danger btn-sm delete-row"
                                            onclick="deleteRow(this)">Xoá</button>
                                    </td>
                                </tr>
                                <tr class="height-40">
                                    <td class="text-13-black border py-0 text-center">02</td>
                                    <td class="text-13-black border py-0 pl-3">
                                        <input type="text" id="form_code" name="form_code" style="flex:2;"
                                            placeholder="Nhập thông tin" class="text-13-black w-100 border-0">
                                    </td>
                                    <td class="text-13-black border py-0 text-center">
                                        <button class="btn btn-danger btn-sm delete-row"
                                            onclick="deleteRow(this)">Xoá</button>
                                    </td>
                                </tr>
                                <tr class="height-40">
                                    <td class="text-13-black border py-0 text-center">03</td>
                                    <td class="text-13-black border py-0 pl-3">
                                        <input type="text" id="form_code" name="form_code" style="flex:2;"
                                            placeholder="Nhập thông tin" class="text-13-black w-100 border-0">
                                    </td>
                                    <td class="text-13-black border py-0 text-center">
                                        <button class="btn btn-danger btn-sm delete-row"
                                            onclick="deleteRow(this)">Xoá</button>
                                    </td>
                                </tr>
                                <tr class="height-40">
                                    <td class="text-13-black border py-0 text-center">04</td>
                                    <td class="text-13-black border py-0 pl-3">
                                        <input type="text" id="form_code" name="form_code" style="flex:2;"
                                            placeholder="Nhập thông tin" class="text-13-black w-100 border-0">
                                    </td>
                                    <td class="text-13-black border py-0 text-center">
                                        <button class="btn btn-danger btn-sm delete-row"
                                            onclick="deleteRow(this)">Xoá</button>
                                    </td>
                                </tr>
                                <tr class="height-40">
                                    <td class="text-13-black border py-0 text-center">05</td>
                                    <td class="text-13-black border py-0 pl-3">
                                        <input type="text" id="form_code" name="form_code" style="flex:2;"
                                            placeholder="Nhập thông tin" class="text-13-black w-100 border-0">
                                    </td>
                                    <td class="text-13-black border py-0 text-center">
                                        <button class="btn btn-danger btn-sm delete-row"
                                            onclick="deleteRow(this)">Xoá</button>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        <div class="my-2 d-flex justify-content-between">
                            <span>
                                <button id="add-rows" class="border-0 bg-transparent pl-3">Thêm</button>
                                <input type="number" id="row-count" class="rounded d-inline-block p-0 text-center"
                                    style="width: 36px; height: 22px; font-size: 12px;" value="5">
                                dòng
                            </span>
                            <span class="mr-5">Số lượng : 3</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
