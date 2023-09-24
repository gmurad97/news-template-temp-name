<?php $this->load->view("admins/includes/HeadScripts"); ?>
<?php $this->load->view("admins/includes/Navbar"); ?>
<?php $this->load->view("admins/includes/Sidebar"); ?>
<div class="card">
    <div class="card-header fw-bold d-flex flex-row justify-content-between align-items-center">
        <div class="h5 text-success m-0">CATEGORIES LIST</div>
        <div>
            <a href="<?= base_url('admin/categories-create'); ?>" class="btn btn-outline-success">
                <i class="bi bi-plus-circle me-1"></i>
                Create
            </a>
        </div>
    </div>
    <div class="card-body">
        <?php if ($this->session->flashdata("categories_alert")) : ?>
            <div class="alert alert-<?= $this->session->flashdata('categories_alert')['alert_type']; ?> alert-dismissable fade show p-3" style="<?= $this->session->flashdata('categories_alert')['alert_bg_color']; ?>">
                <button type="button" class="btn-close float-end" data-bs-dismiss="alert"></button>
                <h4 class="alert-heading">
                    <i class="<?= $this->session->flashdata('categories_alert')['alert_icon']; ?> me-2"></i>
                    <?= $this->session->flashdata('categories_alert')['alert_heading_message']; ?>
                </h4>
                <hr>
                <p class="mb-0">
                    <strong class="fw-bold"><?= $this->session->flashdata('categories_alert')['alert_short_message']; ?> </strong>
                    <?= $this->session->flashdata('categories_alert')['alert_long_message']; ?>
                </p>
            </div>
        <?php endif; ?>
        <table class="table table-hover text-nowrap w-100" id="categories-datatable">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Image</th>
                    <th>Name</th>
                    <th>BG Color</th>
                    <th>Status</th>
                    <th>Control</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($categories_data)) : ?>
                    <?php
                    $id_counter = 1;
                    foreach ($categories_data as $category_data_item) :
                        $category_data_item_id = $category_data_item["c_uid"];
                        $category_data_item_data = json_decode($category_data_item["c_data"]);
                    ?>
                        <tr>
                            <td><?= $id_counter++; ?></td>
                            <td>
                                <a href="<?= base_url('file_manager/categories/') . $category_data_item_data->category_img; ?>" data-lity>
                                    <img width="64" height="64" style="object-fit: cover;" class="rounded-circle bg-white" src="<?= base_url('file_manager/categories/') . $category_data_item_data->category_img; ?>" title="<?= $category_data_item_data->category_name->en; ?>" alt="<?= $category_data_item_data->category_name->en; ?>">
                                </a>
                            </td>
                            <td>
                                <?= (is_null($category_data_item_data->category_name->en) || empty($category_data_item_data->category_name->en)) ? "NULL" : "<span class='fw-bold text-info'>[EN]</span> " . $category_data_item_data->category_name->en . "<br>"; ?>
                                <?= (is_null($category_data_item_data->category_name->ru) || empty($category_data_item_data->category_name->ru)) ? "NULL" : "<span class='fw-bold text-info'>[RU]</span> " . $category_data_item_data->category_name->ru . "<br>"; ?>
                                <?= (is_null($category_data_item_data->category_name->az) || empty($category_data_item_data->category_name->az)) ? "NULL" : "<span class='fw-bold text-info'>[AZ]</span> " . $category_data_item_data->category_name->az . "<br>"; ?>
                            </td>
                            <td>
                                <div class="rounded-circle w-25px h-25px" style="background-color: <?= $category_data_item_data->category_bg_color; ?> ;"></div>
                            </td>
                            <td>
                                <?php if ($category_data_item_data->category_status) : ?>
                                    <span class="badge bg-success p-2 w-75px text-uppercase">Active</span>
                                <?php else : ?>
                                    <span class="badge bg-danger p-2 w-75px text-uppercase">Inactive</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <nav class="nav flex-row">
                                    <a href="javascript:void(0);" class="nav-link disabled theme-info p-0">
                                        <i class="bi bi-eye fs-5"></i>
                                    </a>
                                    <a href="<?= base_url('admin/categories-edit/') . $category_data_item_id; ?>" class="nav-link theme-warning p-0 mx-3">
                                        <i class="bi bi-pencil-square fs-5"></i>
                                    </a>
                                    <a href="javascript:void(0);" class="nav-link theme-danger p-0" data-link="<?= base_url('admin/categories-delete/') . $category_data_item_id; ?>" data-bs-toggle="modal" data-bs-target="#category_modal_delete">
                                        <i class=" bi bi-trash fs-5"></i>
                                    </a>
                                </nav>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
            <tfoot>
                <tr>
                    <th>#</th>
                    <th>Image</th>
                    <th>Name</th>
                    <th>BG Color</th>
                    <th>Status</th>
                    <th>Control</th>
                </tr>
            </tfoot>
        </table>
        <!--DATA TABLE SCRIPTS & STYLES - START-->
        <link rel="stylesheet" href="<?= base_url('public/admin/assets/plugins/datatable/css/buttons.bootstrap5.min.css'); ?>">
        <link rel="stylesheet" href="<?= base_url('public/admin/assets/plugins/datatable/css/dataTables.bootstrap5.min.css'); ?>">
        <link rel="stylesheet" href="<?= base_url('public/admin/assets/plugins/datatable/css/responsive.bootstrap5.min.css'); ?>">
        <script src="<?= base_url('public/admin/assets/js/jquery-3.7.1.min.js'); ?>"></script>
        <script src="<?= base_url('public/admin/assets/plugins/datatable/js/jquery.dataTables.min.js'); ?>"></script>
        <script src="<?= base_url('public/admin/assets/plugins/datatable/js/dataTables.bootstrap5.min.js'); ?>"></script>
        <script src="<?= base_url('public/admin/assets/plugins/datatable/js/dataTables.buttons.min.js'); ?>"></script>
        <script src="<?= base_url('public/admin/assets/plugins/datatable/js/buttons.colVis.min.js'); ?>"></script>
        <script src="<?= base_url('public/admin/assets/plugins/datatable/js/buttons.flash.min.js'); ?>"></script>
        <script src="<?= base_url('public/admin/assets/plugins/datatable/js/buttons.html5.min.js'); ?>"></script>
        <script src="<?= base_url('public/admin/assets/plugins/datatable/js/buttons.print.min.js'); ?>"></script>
        <script src="<?= base_url('public/admin/assets/plugins/datatable/js/buttons.bootstrap5.min.js'); ?>"></script>
        <script src="<?= base_url('public/admin/assets/plugins/datatable/js/dataTables.responsive.min.js'); ?>"></script>
        <script src="<?= base_url('public/admin/assets/plugins/datatable/js/responsive.bootstrap5.min.js'); ?>"></script>
        <script>
            $("#categories-datatable").DataTable({
                dom: "<'row mb-3'<'col-sm-4'l><'col-sm-8 text-end'<'d-flex justify-content-end align-items-center'fB>>>t<'d-flex align-items-center'<'me-auto'i><'mb-0'p>>",
                lengthMenu: [10, 20, 30, 40, 50],
                responsive: true,
                buttons: [{
                        extend: "print",
                        className: "btn btn-sm btn-outline-default ms-2"
                    },
                    {
                        extend: "csv",
                        className: "btn btn-sm btn-outline-default"
                    }
                ],
                language: {
                    paginate: {
                        previous: "<i class=\"fa-solid fa-chevron-left\"></i>",
                        next: "<i class=\"fa-solid fa-chevron-right\"></i>",
                    }
                }
            });
        </script>
        <!--DATA TABLE SCRIPTS & STYLES - ENDED-->
    </div>
    <div class="card-arrow">
        <div class="card-arrow-top-left"></div>
        <div class="card-arrow-top-right"></div>
        <div class="card-arrow-bottom-left"></div>
        <div class="card-arrow-bottom-right"></div>
    </div>
</div>
<div class="modal fade text-center" id="category_modal_delete">
    <div class="modal-dialog modal-sm">
        <div class="modal-content rounded">
            <div class="modal-body py-3">
                <p class="h5 text-danger">Do you really want to remove the category?</p>
            </div>
            <div class="modal-footer py-1">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                <a href="javascript:void(0);" class="btn btn-outline-danger" id="category_modal_delete_link">Remove</a>
            </div>
        </div>
    </div>
</div>
<script>
    $(document).on("focus", "[data-link]", function() {
        var link = $(this).data("link");
        $("#category_modal_delete_link").attr("href", link);
    });
</script>
<?php $this->load->view("admins/includes/FooterScripts"); ?>