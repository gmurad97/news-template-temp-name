<?php
defined('BASEPATH') or exit('No direct script access allowed');

/**
 * AUTHOR: Murad Gazymagomedov
 * USERNAME: gmurad97 || ASProgerHack
 * USER TEMPLATE: MORUS NEWS (Dump&Crack FrontEnd Pages)
 * ADMIN TEMPLATE: HUD ADMIN (Dump&Crack FrontEnd Pages)
 * VERSION: 2.1
 * USED LOCAL SERVER: OPENSERVER 5.4.3
 **/

class AdminController extends CI_Controller
{
    public function __construct()
    {
        parent::__construct();
        $this->load->model("AdminModel");
    }

    /*=====LOCAL ADMIN CONTROLLER FUNCTION - START=====*/
    protected function SendEmail(array $eTo, string $eSubject, string $eMessage): bool
    {
        $eFrom = "murad.dev@rrshipping.global";
        $eFromName = "STIMUL NEWS";
        $this->email->clear();
        $this->email->from($eFrom, $eFromName);
        $this->email->to($eTo);
        $this->email->subject($eSubject);
        $this->email->message($eMessage);
        return $this->email->send();
    }

    protected function AlertFlashData(string $alertType, string $alertName, string $alertShortMessage, string $alertLongMessage): void
    {
        $alert_type     = strtolower($alertType);
        $alert_bg_color = "rgba(0, 23, 51, 0.32)";
        $alert_icon     = "bi bi-info-circle";
        if ($alert_type === "info") {
            $alert_bg_color = "rgba(0, 23, 51, 0.32)";
            $alert_icon     = "bi bi-info-circle";
        } elseif ($alert_type === "success") {
            $alert_bg_color = "rgba(4, 27, 7, 0.32)";
            $alert_icon     = "bi bi-check-circle";
        } elseif ($alert_type === "warning") {
            $alert_bg_color = "rgba(50, 46, 3, 0.32)";
            $alert_icon     = "bi bi-exclamation-triangle";
        } elseif ($alert_type === "danger") {
            $alert_bg_color = "rgba(45, 0, 0, 0.32)";
            $alert_icon     = "bi bi-exclamation-octagon";
        }
        $this->session->set_flashdata(
            $alertName,
            [
                "alert_type"          => $alert_type,
                "alert_icon"          => $alert_icon,
                "alert_bg_color"      => "background-color:" . $alert_bg_color,
                "alert_short_message" => $alertShortMessage,
                "alert_long_message"  => $alertLongMessage
            ]
        );
    }

    protected function CryptoPrice($cryptoLimit)
    {
        $curl_crypto_price = curl_init("https://api.binance.com/api/v3/ticker/price");
        curl_setopt($curl_crypto_price, CURLOPT_USERAGENT, "StimulNewsClient-v1.3");
        curl_setopt($curl_crypto_price, CURLOPT_RETURNTRANSFER, TRUE);
        $response_result = array_values(array_filter(json_decode(curl_exec($curl_crypto_price), FALSE), function ($cryptoPair) {
            if (str_ends_with($cryptoPair->symbol, "USDT")) {
                return $cryptoPair->symbol;
            }
        }));
        curl_close($curl_crypto_price);
        return array_splice($response_result, 0, $cryptoLimit);
    }

    protected function FiatPrice(array $currencyFiat)
    {
        $curl_fiat_price = curl_init("https://openexchangerates.org/api/latest.json?app_id=65d9e0d1a009445c9f96ceb8caed66bb&symbols=" . join(",", $currencyFiat));
        curl_setopt($curl_fiat_price, CURLOPT_USERAGENT, "StimulNewsClient-v1.3");
        curl_setopt($curl_fiat_price, CURLOPT_RETURNTRANSFER, TRUE);
        $response_result = curl_exec($curl_fiat_price);
        curl_close($curl_fiat_price);
        return json_decode($response_result, TRUE)["rates"];
    }
    /*=====LOCAL ADMIN CONTROLLER FUNCTION - ENDED=====*/

    /*=====LOGIN - START=====*/
    public function login()
    {
        $this->load->helper("captcha");

        $captcha_cfg = [
            "img_path" => "./file_manager/system/captcha/",
            "img_url" => base_url("/file_manager/system/captcha/"),
            "font_path" => "system/fonts/texb.ttf",
            "img_width" => 160,
            "img_height" => 48,
            "expiration" => 960,
            "word_length" => 6,
            "pool" => "XYZ0123456789HQF",
            "colors" => [
                "background" => array(56, 173, 169),
                "border" => array(30, 55, 153),
                "text" => array(123, 237, 159),
                "grid" => array(60, 99, 130)
            ]
        ];

        $data["admin_auth_captcha"] = create_captcha($captcha_cfg);
        $data["admin_page_name"] = "Admin Panel";

        $this->session->unset_userdata("adm_auth_captcha");
        $this->session->set_userdata("adm_auth_captcha", $data["admin_auth_captcha"]["word"]);

        $this->load->view("admins/Login", $data);
    }

    public function login_action()
    {
        $admin_session_captcha = strtoupper($this->session->userdata("adm_auth_captcha"));
        $this->session->unset_userdata("adm_auth_captcha");

        $admin_login    = $this->input->post("admin_login", TRUE);
        $admin_password = hash("sha512", hash("md5", $this->input->post("admin_password", TRUE)));
        $admin_captcha  = strtoupper($this->input->post("admin_captcha", TRUE));

        if (!empty($admin_login) && !empty($admin_password) && !empty($admin_captcha)) {
            if ($admin_captcha === $admin_session_captcha) {
                $admin_data = $this->db->or_where(["a_username" => $admin_login, "a_email" => $admin_login])->get("admin")->row_array();
                if (!empty($admin_data) && $admin_password === $admin_data["a_password"]) {
                    $sess_data = [
                        "admin_first_name" => $admin_data["a_name"],
                        "admin_last_name"  => $admin_data["a_surname"],
                        "admin_img"        => $admin_data["a_img"],
                        "admin_role"       => $admin_data["a_role"],
                        "logged_in"        => TRUE
                    ];
                    $this->session->set_userdata("admin_auth", $sess_data);
                    redirect(base_url("admin/dashboard"));
                } else {
                    $this->AlertFlashData(
                        "danger",
                        "crud_alert",
                        "Danger!",
                        "The login or password you provided is incorrect."
                    );
                    redirect($_SERVER["HTTP_REFERER"]);
                }
            } else {
                $this->AlertFlashData(
                    "danger",
                    "crud_alert",
                    "Danger!",
                    "The provided captcha was entered incorrectly."
                );
                redirect($_SERVER["HTTP_REFERER"]);
            }
        } else {
            $this->AlertFlashData(
                "warning",
                "crud_alert",
                "Warning!",
                "Please, fill in all the fields."
            );
            redirect($_SERVER["HTTP_REFERER"]);
        }
    }
    /*=====LOGIN - ENDED=====*/

    /*=====DASHBOARD - START=====*/
    public function dashboard()
    {
        $data["admin_page_name"] = "Dashboard";
        $data["crypto_price"] = $this->CryptoPrice(3);
        $data["fiat_price"] = $this->FiatPrice(["AZN", "RUB", "EUR"]);
        $this->load->view("admins/Dashboard", $data);
    }
    /*=====DASHBOARD - ENDED=====*/

    /*=====AI GPT - START=====*/
    public function ai_gpt()
    {
        $data["admin_page_name"] = "AI GPT 3.5";
        $this->load->view("admins/ChatGpt", $data);
    }

    public function ai_gpt_action($query)
    {
        $json_data = [
            "id" => null,
            "botId" => "default",
            "newMessage" => $query,
            "stream" => false
        ];
        $curl_api_gpt = curl_init("https://chatg.io/wp-json/mwai-ui/v1/chats/submit");
        curl_setopt($curl_api_gpt, CURLOPT_USERAGENT, "StimulNewsClient-v1.3");
        curl_setopt($curl_api_gpt, CURLOPT_RETURNTRANSFER, TRUE);
        curl_setopt(
            $curl_api_gpt,
            CURLOPT_HTTPHEADER,
            [
                "Referer:https://chatg.io/chat/",
                "Accept: text/event-stream",
                "Content-Type: application/json"
            ]
        );
        curl_setopt($curl_api_gpt, CURLOPT_POST, 1);
        curl_setopt($curl_api_gpt, CURLOPT_POSTFIELDS, json_encode($json_data));
        curl_setopt($curl_api_gpt, CURLOPT_TIMEOUT, 60);
        $gpt_response = curl_exec($curl_api_gpt);
        curl_close($curl_api_gpt);
        print_r(json_decode($gpt_response, FALSE)->reply);
    }
    /*=====AI GPT - ENDED=====*/

    /*=====TOPBAR CRUD - START=====*/
    public function crud_topbar_create()
    {
        $topbar_db_row = $this->AdminModel->table_row_id("topbar", "t_uid");
        if ($topbar_db_row == -1) {
            $data["admin_page_name"] = "Topbar Create";
            $this->load->view("admins/Topbar/Create", $data);
        } else {
            redirect(base_url("admin/topbar-edit"));
        }
    }

    public function crud_topbar_create_action()
    {
        $topbar_db_row = $this->AdminModel->table_row_id("topbar", "t_uid");
        if ($topbar_db_row == -1) {
            $topbar_self    = $this->input->post("topbar_self",    TRUE);
            $topbar_date    = $this->input->post("topbar_date",    TRUE);
            $topbar_time    = $this->input->post("topbar_time",    TRUE);
            $topbar_weather = $this->input->post("topbar_weather", TRUE);
            $json_data_decoded = [
                "topbar_self"    => str_contains($topbar_self,    "on") ? TRUE : FALSE,
                "topbar_date"    => str_contains($topbar_date,    "on") ? TRUE : FALSE,
                "topbar_time"    => str_contains($topbar_time,    "on") ? TRUE : FALSE,
                "topbar_weather" => str_contains($topbar_weather, "on") ? TRUE : FALSE
            ];
            $json_data_encoded = json_encode($json_data_decoded);
            $data = [
                "t_data" => $json_data_encoded
            ];
            $this->AdminModel->topbar_admin_db_insert($data);
            $this->AlertFlashData(
                "success",
                "crud_alert",
                "Success!",
                "The topbar has been successfully created."
            );
            redirect(base_url("admin/topbar-edit"));
        } else {
            redirect(base_url("admin/topbar-edit"));
        }
    }

    public function crud_topbar_edit()
    {
        $topbar_db_row = $this->AdminModel->table_row_id("topbar", "t_uid");
        if ($topbar_db_row == -1) {
            redirect(base_url("admin/topbar-create"));
        } else {
            $data["admin_page_name"] = "Topbar Edit";
            $data["admin_topbar"] = json_decode($this->AdminModel->topbar_admin_db_get($topbar_db_row)["t_data"]);
            $this->load->view("admins/Topbar/Edit", $data);
        }
    }

    public function crud_topbar_edit_action()
    {
        $topbar_db_row = $this->AdminModel->table_row_id("topbar", "t_uid");
        if ($topbar_db_row == -1) {
            redirect(base_url("admin/topbar-create"));
        } else {
            $topbar_self    = $this->input->post("topbar_self",    TRUE);
            $topbar_date    = $this->input->post("topbar_date",    TRUE);
            $topbar_time    = $this->input->post("topbar_time",    TRUE);
            $topbar_weather = $this->input->post("topbar_weather", TRUE);
            $json_data_decoded = [
                "topbar_self"    => str_contains($topbar_self,    "on") ? TRUE : FALSE,
                "topbar_date"    => str_contains($topbar_date,    "on") ? TRUE : FALSE,
                "topbar_time"    => str_contains($topbar_time,    "on") ? TRUE : FALSE,
                "topbar_weather" => str_contains($topbar_weather, "on") ? TRUE : FALSE
            ];
            $json_data_encoded = json_encode($json_data_decoded);
            $data = [
                "t_data" => $json_data_encoded
            ];
            $this->AdminModel->topbar_admin_db_edit($topbar_db_row, $data);
            $this->AlertFlashData(
                "success",
                "crud_alert",
                "Success!",
                "The topbar has been successfully edited."
            );
            redirect(base_url("admin/topbar-edit"));
        }
    }

    public function crud_topbar_delete()
    {
        $topbar_db_row = $this->AdminModel->table_row_id("topbar", "t_uid");
        $this->AdminModel->topbar_admin_db_delete($topbar_db_row);
        $this->AlertFlashData(
            "success",
            "crud_alert",
            "Success!",
            "The topbar has been successfully removed."
        );
        redirect(base_url("admin/topbar-create"));
    }
    /*=====TOPBAR CRUD - ENDED=====*/

    /*=====BRANDING CRUD - START=====*/
    public function crud_branding_create()
    {
        $branding_db_row = $this->AdminModel->table_row_id("branding", "b_uid");
        if ($branding_db_row == -1) {
            $data["admin_page_name"] = "Branding Create";
            $this->load->view("admins/Branding/Create", $data);
        } else {
            redirect(base_url("admin/branding-edit"));
        }
    }

    public function crud_branding_create_action()
    {
        $branding_db_row = $this->AdminModel->table_row_id("branding", "b_uid");
        if ($branding_db_row == -1) {
            $logo_dark_visibility  = $this->input->post("logo_dark_visibility",  TRUE);
            $logo_light_visibility = $this->input->post("logo_light_visibility", TRUE);
            $site_title_prefix     = $this->input->post("site_title_prefix",     TRUE);
            $branding_config["upload_path"]      = "./file_manager/branding/";
            $branding_config["allowed_types"]    = "ico|jpeg|jpg|png|svg|ICO|JPEG|JPG|PNG|SVG";
            $branding_config["file_ext_tolower"] = TRUE;
            $branding_config["remove_spaces"]    = TRUE;
            $branding_config["encrypt_name"]     = TRUE;
            $this->load->library("upload", $branding_config);
            $this->upload->initialize($branding_config);
            $uploadResults = [
                "logo_dark_img"  => $this->upload->do_upload("logo_dark_img")  ? $this->upload->data() : NULL,
                "logo_light_img" => $this->upload->do_upload("logo_light_img") ? $this->upload->data() : NULL,
                "favicon_img"    => $this->upload->do_upload("favicon_img")    ? $this->upload->data() : NULL
            ];
            if (
                !is_null($uploadResults["logo_dark_img"])
                && !is_null($uploadResults["logo_light_img"])
                && !is_null($uploadResults["favicon_img"])
                && !empty($site_title_prefix)
            ) {
                $json_data_decoded = [
                    "logo_dark" => [
                        "file_name"  => $uploadResults["logo_dark_img"]["file_name"] ?? NULL,
                        "file_type"  => $uploadResults["logo_dark_img"]["file_type"] ?? NULL,
                        "file_ext"   => $uploadResults["logo_dark_img"]["file_ext"]  ?? NULL,
                        "visibility" => str_contains($logo_dark_visibility, "on") ? TRUE : FALSE
                    ],
                    "logo_light" => [
                        "file_name"  => $uploadResults["logo_light_img"]["file_name"] ?? NULL,
                        "file_type"  => $uploadResults["logo_light_img"]["file_type"] ?? NULL,
                        "file_ext"   => $uploadResults["logo_light_img"]["file_ext"]  ?? NULL,
                        "visibility" => str_contains($logo_light_visibility, "on") ? TRUE : FALSE
                    ],
                    "favicon" => [
                        "file_name"  => $uploadResults["favicon_img"]["file_name"] ?? NULL,
                        "file_type"  => $uploadResults["favicon_img"]["file_type"] ?? NULL,
                        "file_ext"   => $uploadResults["favicon_img"]["file_ext"]  ?? NULL
                    ],
                    "title_prefix" =>  base64_encode($site_title_prefix)
                ];
                $json_data_encoded = json_encode($json_data_decoded);
                $data = [
                    "b_data" => $json_data_encoded
                ];
                $this->AdminModel->branding_admin_db_insert($data);
                $this->AlertFlashData(
                    "success",
                    "crud_alert",
                    "Success!",
                    "The branding has been successfully created."
                );
                redirect(base_url("admin/branding-edit"));
            } else {
                $this->AlertFlashData(
                    "warning",
                    "crud_alert",
                    "Warning!",
                    "Please, fill in all the fields."
                );
                redirect($_SERVER["HTTP_REFERER"]);
            }
        } else {
            redirect(base_url("admin/branding-edit"));
        }
    }

    public function crud_branding_edit()
    {
        $branding_db_row = $this->AdminModel->table_row_id("branding", "b_uid");
        if ($branding_db_row == -1) {
            redirect(base_url("admin/branding-create"));
        } else {
            $data["admin_page_name"] = "Branding Edit";
            $data["admin_branding"] = json_decode($this->AdminModel->branding_admin_db_get($branding_db_row)["b_data"]);
            $this->load->view("admins/Branding/Edit", $data);
        }
    }

    public function crud_branding_edit_action()
    {
        $branding_db_row = $this->AdminModel->table_row_id("branding", "b_uid");
        if ($branding_db_row == -1) {
            redirect(base_url("admin/branding-create"));
        } else {
            $logo_dark_visibility  = $this->input->post("logo_dark_visibility",  TRUE);
            $logo_light_visibility = $this->input->post("logo_light_visibility", TRUE);
            $site_title_prefix     = $this->input->post("site_title_prefix",     TRUE);
            $branding_config["upload_path"]      = "./file_manager/branding/";
            $branding_config["allowed_types"]    = "ico|jpeg|jpg|png|svg|ICO|JPEG|JPG|PNG|SVG";
            $branding_config["file_ext_tolower"] = TRUE;
            $branding_config["remove_spaces"]    = TRUE;
            $branding_config["encrypt_name"]     = TRUE;
            $this->load->library("upload", $branding_config);
            $this->upload->initialize($branding_config);
            $old_branding_data = json_decode($this->AdminModel->branding_admin_db_get($branding_db_row)["b_data"], TRUE);
            $logo_dark_img  = $old_branding_data["logo_dark"];
            $logo_light_img = $old_branding_data["logo_light"];
            $favicon_img    = $old_branding_data["favicon"];
            if ($this->upload->do_upload("logo_dark_img")) {
                if (isset($logo_dark_img["file_name"]) && file_exists($branding_config["upload_path"] . $logo_dark_img["file_name"])) {
                    unlink($branding_config["upload_path"] . $logo_dark_img["file_name"]);
                }
                $logo_dark_img = $this->upload->data();
            } elseif ($this->upload->do_upload("logo_light_img")) {
                if (isset($logo_light_img["file_name"]) && file_exists($branding_config["upload_path"] . $logo_light_img["file_name"])) {
                    unlink($branding_config["upload_path"] . $logo_light_img["file_name"]);
                }
                $logo_light_img = $this->upload->data();
            } elseif ($this->upload->do_upload("favicon_img")) {
                if (isset($favicon_img["file_name"]) && file_exists($branding_config["upload_path"] . $favicon_img["file_name"])) {
                    unlink($branding_config["upload_path"] . $favicon_img["file_name"]);
                }
                $favicon_img = $this->upload->data();
            }
            if (
                !empty($logo_dark_img)
                && !empty($logo_light_img)
                && !empty($favicon_img)
                && !empty($site_title_prefix)
            ) {
                $json_data_decoded = [
                    "logo_dark" => [
                        "file_name"  => $logo_dark_img["file_name"] ?? NULL,
                        "file_type"  => $logo_dark_img["file_type"] ?? NULL,
                        "file_ext"   => $logo_dark_img["file_ext"]  ?? NULL,
                        "visibility" => str_contains($logo_dark_visibility, "on") ? TRUE : FALSE
                    ],
                    "logo_light" => [
                        "file_name"  => $logo_light_img["file_name"] ?? NULL,
                        "file_type"  => $logo_light_img["file_type"] ?? NULL,
                        "file_ext"   => $logo_light_img["file_ext"]  ?? NULL,
                        "visibility" => str_contains($logo_light_visibility, "on") ? TRUE : FALSE
                    ],
                    "favicon" => [
                        "file_name"  => $favicon_img["file_name"] ?? NULL,
                        "file_type"  => $favicon_img["file_type"] ?? NULL,
                        "file_ext"   => $favicon_img["file_ext"]  ?? NULL
                    ],
                    "title_prefix" => base64_encode($site_title_prefix)
                ];
                $json_data_encoded = json_encode($json_data_decoded);
                $data = [
                    "b_data" => $json_data_encoded
                ];
                $this->AdminModel->branding_admin_db_update($branding_db_row, $data);
                $this->AlertFlashData(
                    "success",
                    "crud_alert",
                    "Success!",
                    "The branding has been successfully edited."
                );
                redirect(base_url("admin/branding-edit"));
            } else {
                $this->AlertFlashData(
                    "warning",
                    "crud_alert",
                    "Warning!",
                    "Please, fill in all the fields."
                );
                redirect($_SERVER["HTTP_REFERER"]);
            }
        }
    }

    public function crud_branding_delete()
    {
        $branding_db_row = $this->AdminModel->table_row_id("branding", "b_uid");
        $this->AdminModel->branding_admin_db_delete($branding_db_row);
        array_map("unlink", array_filter((array) glob("./file_manager/branding/*"), "file_exists"));
        $this->AlertFlashData(
            "success",
            "crud_alert",
            "Success!",
            "The branding has been successfully removed."
        );
        redirect(base_url("admin/branding-create"));
    }
    /*=====BRANDING CRUD - ENDED=====*/

    /*=====PARTNERS CRUD - START=====*/
    public function crud_partners_create()
    {
        $data["admin_page_name"] = "Partners Create";
        $this->load->view("admins/Partners/Create", $data);
    }

    public function crud_partners_create_action()
    {
        $partner_link   = filter_var($this->input->post("partner_link",   TRUE), FILTER_SANITIZE_URL);
        $partner_title  = $this->input->post("partner_title",  TRUE);
        $partner_status = $this->input->post("partner_status", TRUE);
        $partners_config["upload_path"]      = "./file_manager/partners/";
        $partners_config["allowed_types"]    = "ico|jpeg|jpg|png|svg|ICO|JPEG|JPG|PNG|SVG";
        $partners_config["file_ext_tolower"] = TRUE;
        $partners_config["remove_spaces"]    = TRUE;
        $partners_config["encrypt_name"]     = TRUE;
        $this->load->library("upload", $partners_config);
        $this->upload->initialize($partners_config);
        if ($this->upload->do_upload("partner_img") && (!empty($partner_link) && filter_var($partner_link, FILTER_VALIDATE_URL)) && !empty($partner_title)) {
            $partner_img = $this->upload->data();
            $partners_config_img["image_library"] = "gd2";
            $partners_config_img["source_image"] = $partners_config["upload_path"] . $partner_img["file_name"];
            $partners_config_img["maintain_ratio"] = FALSE;
            $partners_config_img["width"] = 200;
            $partners_config_img["height"] = 150;
            $this->load->library("image_lib", $partners_config_img);
            $this->load->initialize($partners_config_img);
            $this->image_lib->resize();
            $data = [
                "p_title"  => $partner_title,
                "p_link"   => $partner_link,
                "p_img"    => $partner_img["file_name"],
                "p_status" => str_contains($partner_status, "on") ? TRUE : FALSE
            ];
            $this->AdminModel->partners_admin_db_insert($data);
            $this->AlertFlashData(
                "success",
                "crud_alert",
                "Success!",
                "The partner has been successfully added."
            );
            redirect(base_url("admin/partners-list"));
        } else {
            $this->AlertFlashData(
                "warning",
                "crud_alert",
                "Warning!",
                "Please, fill in all the fields."
            );
            redirect(base_url("admin/partners-create"));
        }
    }

    public function crud_partners_list()
    {
        $data["admin_page_name"] = "Partners List";
        $data["partners_data"] = $this->AdminModel->partners_admin_db_get_results();
        $this->load->view("admins/Partners/List", $data);
    }

    public function crud_partners_edit($id)
    {
        $data["admin_page_name"] = "Partners Edit";
        $data["partner_data"] = $this->AdminModel->partners_admin_db_get($id);
        $this->load->view("admins/Partners/Edit", $data);
    }

    public function crud_partners_edit_action($id)
    {
        $old_partner_data = $this->AdminModel->partners_admin_db_get($id);
        $partner_img_path = "./file_manager/partners/" . $old_partner_data["p_img"];
        $partner_link   = filter_var($this->input->post("partner_link",   TRUE), FILTER_SANITIZE_URL);
        $partner_title  = $this->input->post("partner_title",  TRUE);
        $partner_status = $this->input->post("partner_status", TRUE);
        $partners_config["upload_path"]      = "./file_manager/partners/";
        $partners_config["allowed_types"]    = "ico|jpeg|jpg|png|svg|ICO|JPEG|JPG|PNG|SVG";
        $partners_config["file_ext_tolower"] = TRUE;
        $partners_config["remove_spaces"]    = TRUE;
        $partners_config["encrypt_name"]     = TRUE;
        $this->load->library("upload", $partners_config);
        $this->upload->initialize($partners_config);
        if ($this->upload->do_upload("partner_img") && (!empty($partner_link) && filter_var($partner_link, FILTER_VALIDATE_URL)) && !empty($partner_title)) {
            if (!is_dir($partner_img_path) && file_exists($partner_img_path)) {
                unlink($partner_img_path);
            }
            $partner_img = $this->upload->data();
            $partners_config_img["image_library"] = "gd2";
            $partners_config_img["source_image"] = $partners_config["upload_path"] . $partner_img["file_name"];
            $partners_config_img["maintain_ratio"] = FALSE;
            $partners_config_img["width"] = 200;
            $partners_config_img["height"] = 150;
            $this->load->library("image_lib", $partners_config_img);
            $this->load->initialize($partners_config_img);
            $this->image_lib->resize();
            $data = [
                "p_title"  => $partner_title,
                "p_link"   => $partner_link,
                "p_img"    => $partner_img["file_name"],
                "p_status" => str_contains($partner_status, "on") ? TRUE : FALSE
            ];
            $this->AdminModel->partners_admin_db_update($id, $data);
            $this->AlertFlashData(
                "success",
                "crud_alert",
                "Success!",
                "The partner has been successfully edited."
            );
            redirect(base_url("admin/partners-list"));
        } elseif ((!empty($partner_link) && filter_var($partner_link, FILTER_VALIDATE_URL)) && !empty($partner_title)) {
            $data = [
                "p_title"  => $partner_title,
                "p_link"   => $partner_link,
                "p_img"    => $old_partner_data["p_img"],
                "p_status" => str_contains($partner_status, "on") ? TRUE : FALSE
            ];
            $this->AdminModel->partners_admin_db_update($id, $data);
            $this->AlertFlashData(
                "success",
                "crud_alert",
                "Success!",
                "The partner has been successfully edited."
            );
            redirect(base_url("admin/partners-list"));
        } else {
            $this->AlertFlashData(
                "warning",
                "crud_alert",
                "Warning!",
                "Please, fill in all the fields."
            );
            redirect($_SERVER["HTTP_REFERER"]);
        }
    }

    public function crud_partners_delete($id)
    {
        $partner_data = $this->AdminModel->partners_admin_db_get($id);
        $partner_img_path = "./file_manager/partners/" . $partner_data["p_img"];
        if (!is_dir($partner_img_path) && file_exists($partner_img_path)) {
            unlink($partner_img_path);
        }
        $this->AdminModel->partners_admin_db_delete($id);
        $this->AlertFlashData(
            "success",
            "crud_alert",
            "Success!",
            "The partner has been successfully removed."
        );
        redirect(base_url("admin/partners-list"));
    }
    /*=====PARTNERS CRUD - ENDED=====*/

    /*=====CATEGORIES CRUD - START=====*/
    public function crud_categories_create()
    {
        $data["admin_page_name"] = "Categories Create";
        $this->load->view("admins/Categories/Create", $data);
    }

    public function crud_categories_create_action()
    {
        $category_en_name  = $this->input->post("category_en_name", TRUE);
        $category_ru_name  = $this->input->post("category_ru_name", TRUE);
        $category_az_name  = $this->input->post("category_az_name", TRUE);
        $category_bg_color = $this->input->post("category_bg_color", TRUE);
        $category_status   = $this->input->post("category_status", TRUE);
        $categories_config["upload_path"]      = "./file_manager/categories/";
        $categories_config["allowed_types"]    = "ico|jpeg|jpg|png|svg|ICO|JPEG|JPG|PNG|SVG";
        $categories_config["file_ext_tolower"] = TRUE;
        $categories_config["remove_spaces"]    = TRUE;
        $categories_config["encrypt_name"]     = TRUE;
        $this->load->library("upload", $categories_config);
        $this->upload->initialize($categories_config);
        if ($this->upload->do_upload("category_img") && !empty($category_en_name) && !empty($category_ru_name) && !empty($category_az_name) && !empty($category_bg_color)) {
            $category_img = $this->upload->data();
            $breadcrumb_img_config["image_library"] = "gd2";
            $breadcrumb_img_config["source_image"] = $categories_config["upload_path"] . $category_img["file_name"];
            $breadcrumb_img_config["maintain_ratio"] = FALSE;
            $breadcrumb_img_config["width"] = 540;
            $breadcrumb_img_config["height"] = 370;
            $this->load->library("image_lib", $breadcrumb_img_config);
            $this->load->initialize($breadcrumb_img_config);
            $this->image_lib->resize();
            $data = [
                "c_name" =>
                json_encode([
                    "en" => base64_encode($category_en_name),
                    "ru" => base64_encode($category_ru_name),
                    "az" => base64_encode($category_az_name)
                ]),
                "c_bg_color" => $category_bg_color,
                "c_img"      => $category_img["file_name"],
                "c_status"   => str_contains($category_status, "on") ? TRUE : FALSE
            ];
            $this->AdminModel->categories_admin_db_insert($data);
            $this->AlertFlashData(
                "success",
                "crud_alert",
                "Success!",
                "The category has been successfully added."
            );
            redirect(base_url("admin/categories-list"));
        } else {
            $this->AlertFlashData(
                "warning",
                "crud_alert",
                "Warning!",
                "Please, fill in all the fields."
            );
            redirect(base_url("admin/categories-create"));
        }
    }

    public function crud_categories_list()
    {
        $data["admin_page_name"] = "Categories List";
        $data["categories_data"] = $this->AdminModel->categories_admin_db_get_results();
        $this->load->view("admins/Categories/List", $data);
    }

    public function crud_categories_edit($id)
    {
        $data["admin_page_name"] = "Categories Edit";
        $data["category_data"] = $this->AdminModel->categories_admin_db_get($id);
        $this->load->view("admins/Categories/Edit", $data);
    }

    public function crud_categories_edit_action($id)
    {
        $old_category_data = $this->AdminModel->categories_admin_db_get($id);
        $category_img_path = "./file_manager/categories/" . $old_category_data["c_img"];
        $category_en_name  = $this->input->post("category_en_name", TRUE);
        $category_ru_name  = $this->input->post("category_ru_name", TRUE);
        $category_az_name  = $this->input->post("category_az_name", TRUE);
        $category_bg_color = $this->input->post("category_bg_color", TRUE);
        $category_status   = $this->input->post("category_status", TRUE);
        $categories_config["upload_path"]      = "./file_manager/categories/";
        $categories_config["allowed_types"]    = "ico|jpeg|jpg|png|svg|ICO|JPEG|JPG|PNG|SVG";
        $categories_config["file_ext_tolower"] = TRUE;
        $categories_config["remove_spaces"]    = TRUE;
        $categories_config["encrypt_name"]     = TRUE;
        $this->load->library("upload", $categories_config);
        $this->upload->initialize($categories_config);
        if ($this->upload->do_upload("category_img") && !empty($category_en_name) && !empty($category_ru_name) && !empty($category_az_name) && !empty($category_bg_color)) {
            if (!is_dir($category_img_path) && file_exists($category_img_path)) {
                unlink($category_img_path);
            }
            $category_img = $this->upload->data();
            $breadcrumb_img_config["image_library"] = "gd2";
            $breadcrumb_img_config["source_image"] = $categories_config["upload_path"] . $category_img["file_name"];
            $breadcrumb_img_config["maintain_ratio"] = FALSE;
            $breadcrumb_img_config["width"] = 540;
            $breadcrumb_img_config["height"] = 370;
            $this->load->library("image_lib", $breadcrumb_img_config);
            $this->load->initialize($breadcrumb_img_config);
            $this->image_lib->resize();
            $data = [
                "c_name" =>
                json_encode([
                    "en" => base64_encode($category_en_name),
                    "ru" => base64_encode($category_ru_name),
                    "az" => base64_encode($category_az_name)
                ]),
                "c_bg_color" => $category_bg_color,
                "c_img"      => $category_img["file_name"],
                "c_status"   => str_contains($category_status, "on") ? TRUE : FALSE
            ];
            $this->AdminModel->categories_admin_db_update($id, $data);
            $this->AlertFlashData(
                "success",
                "crud_alert",
                "Success!",
                "The category has been successfully edited."
            );
            redirect(base_url("admin/categories-list"));
        } else if (!empty($category_en_name) && !empty($category_ru_name) && !empty($category_az_name) && !empty($category_bg_color)) {
            $data = [
                "c_name" =>
                json_encode([
                    "en" => base64_encode($category_en_name),
                    "ru" => base64_encode($category_ru_name),
                    "az" => base64_encode($category_az_name)
                ]),
                "c_bg_color" => $category_bg_color,
                "c_img"      => $old_category_data["c_img"],
                "c_status"   => str_contains($category_status, "on") ? TRUE : FALSE
            ];
            $this->AdminModel->categories_admin_db_update($id, $data);
            $this->AlertFlashData(
                "success",
                "crud_alert",
                "Success!",
                "The category has been successfully edited."
            );
            redirect(base_url("admin/categories-list"));
        } else {
            $this->AlertFlashData(
                "warning",
                "crud_alert",
                "Warning!",
                "Please, fill in all the fields."
            );
            redirect($_SERVER["HTTP_REFERER"]);
        }
    }

    public function crud_categories_delete($id)
    {
        $category_data = $this->AdminModel->categories_admin_db_get($id);
        $category_img_path = "./file_manager/categories/" . $category_data["c_img"];
        if (!is_dir($category_img_path) && file_exists($category_img_path)) {
            unlink($category_img_path);
        }
        $this->AdminModel->categories_admin_db_delete($id);
        $this->AlertFlashData(
            "success",
            "crud_alert",
            "Success!",
            "The category has been successfully removed."
        );
        redirect(base_url("admin/categories-list"));
    }
    /*=====CATEGORIES CRUD - ENDED=====*/























    /*=====NEWS CRUD - START=====*/
    public function crud_news_create()
    {
        $data["admin_page_name"] = "News Create";
        $data["categories_list"] = $this->AdminModel->categories_admin_db_get_results();
        $this->load->view("admins/News/Create", $data);
    }

    public function crud_news_create_action()
    {
        $subscribers_data = array_map(function ($subs_data) {
            return json_decode($subs_data["s_data"], TRUE);
        }, $this->AdminModel->subscribers_admin_db_get_results());
        $subscribers_email = array_values(array_filter(array_map(function ($subs_item) {
            if ($subs_item["subscriber"]["status"]) {
                return base64_decode($subs_item["subscriber"]["email"]);
            }
        }, $subscribers_data)));


        $news_title_en             = $this->input->post("news_title_en", TRUE);
        $news_title_ru             = $this->input->post("news_title_ru", TRUE);
        $news_title_az             = $this->input->post("news_title_az", TRUE);
        $news_short_description_en = $this->input->post("news_short_description_en", TRUE);
        $news_short_description_ru = $this->input->post("news_short_description_ru", TRUE);
        $news_short_description_az = $this->input->post("news_short_description_az", TRUE);
        $news_full_description_en  = $this->input->post("news_full_description_en", FALSE);
        $news_full_description_ru  = $this->input->post("news_full_description_ru", FALSE);
        $news_full_description_az  = $this->input->post("news_full_description_az", FALSE);
        $news_category             = base64_encode($this->input->post("news_category", TRUE));
        $news_status               = $this->input->post("news_status", TRUE);
        $news_notify_subscribers   = str_contains($this->input->post("notify_subscribers", TRUE), "on") ? TRUE : FALSE;

        $categories_list_data = $this->AdminModel->categories_admin_db_get_results();

        $categories_list = array_map(function ($category_item) {
            return json_decode($category_item["c_data"], TRUE);
        }, $categories_list_data);

        $category_data = array_merge(...array_filter($categories_list, function ($v) use ($news_category) {
            if (in_array($news_category, $v["category_name"], TRUE)) {
                return $v;
            }
        }));

        if (empty($category_data)) {
            $this->AlertFlashData(
                "danger",
                "crud_alert",
                "Danger!",
                "Unknown error."
            );
            redirect(base_url("admin/news-create"));
        }

        $news_config["upload_path"]      = "./file_manager/news/";
        $news_config["allowed_types"]    = "ico|jpeg|jpg|png|svg|ICO|JPEG|JPG|PNG|SVG";
        $news_config["file_ext_tolower"] = TRUE;
        $news_config["remove_spaces"]    = TRUE;
        $news_config["encrypt_name"]     = TRUE;
        $this->load->library("upload", $news_config);
        $this->upload->initialize($news_config);

        if (
            $this->upload->do_upload("news_preview_img")
            && !empty($news_title_en)
            && !empty($news_title_ru)
            && !empty($news_title_az)
            && !empty($news_short_description_en)
            && !empty($news_short_description_ru)
            && !empty($news_short_description_az)
            && !empty($news_full_description_en)
            && !empty($news_full_description_ru)
            && !empty($news_full_description_az)
            && !empty($news_category)
        ) {
            $news_preview = $this->upload->data();

            $preview_img_config["image_library"] = "gd2";
            $preview_img_config["source_image"] = $news_config["upload_path"] . $news_preview["file_name"];
            $preview_img_config["maintain_ratio"] = FALSE;
            $preview_img_config["width"] = 1920;
            $preview_img_config["height"] = 1080;

            $this->load->library("image_lib", $preview_img_config);
            $this->load->initialize($preview_img_config);

            $this->image_lib->resize();

            $json_data_decoded = [
                "news_title" => [
                    "en" => base64_encode($news_title_en),
                    "ru" => base64_encode($news_title_ru),
                    "az" => base64_encode($news_title_az)
                ],
                "news_short" => [
                    "en" => base64_encode($news_short_description_en),
                    "ru" => base64_encode($news_short_description_ru),
                    "az" => base64_encode($news_short_description_az)
                ],
                "news_full" => [
                    "en" => base64_encode($news_full_description_en),
                    "ru" => base64_encode($news_full_description_ru),
                    "az" => base64_encode($news_full_description_az),
                ],
                "news_preview" => $news_preview["file_name"],
                "news_category" => [
                    "en" => $category_data["category_name"]["en"],
                    "ru" => $category_data["category_name"]["ru"],
                    "az" => $category_data["category_name"]["az"],
                ],
                "news_category_bg_color" => $category_data["category_bg_color"],
                "news_status" => str_contains($news_status, "on") ? TRUE : FALSE,
                "news_created_date" => date("d.m.Y"),
                "news_created_time" => date("H:i"),
            ];

            $json_data_encoded = json_encode($json_data_decoded);

            $data = [
                "n_data" => $json_data_encoded
            ];

            $this->AdminModel->news_admin_db_insert($data);
            $inserted_id = $this->db->insert_id();

            if ($news_notify_subscribers) {
                $this->SendEmail(
                    $subscribers_email,
                    "New news on the StimulNews",
                    "
                    <h1 style='font-family: Impact,sans-serif; font-weight: lighter; text-align: center;color: #3CD2A5;'>{$news_title_en}</h1>
                    <p style='text-indent: 32px;'>
                        {$news_short_description_en}
                    </p>
                    <a href='{$inserted_id}' style='padding: 8px; background-color: #3CD2A5; font-weight: bold; text-decoration: none;border-radius: 8px; float: right; color: black;'>Read More...</a>
                    "
                );
            }

            $this->AlertFlashData(
                "success",
                "crud_alert",
                "Success!",
                "The news has been successfully created."
            );
            redirect(base_url("admin/news-list"));
        } else {
            $this->AlertFlashData(
                "warning",
                "crud_alert",
                "Warning!",
                "Please, fill in all the fields."
            );
            redirect(base_url("admin/news-create"));
        }
    }

    public function crud_news_list()
    {
        $data["admin_page_name"] = "News Create";
        $data["news_data"] = $this->AdminModel->news_admin_db_get_results();
        $this->load->view("admins/News/List", $data);
    }

    public function crud_news_detail($id)
    {
        $data["admin_page_name"] = "News Detail";
        $data["news_data"] = $this->AdminModel->news_admin_db_get($id);
        $this->load->view("admins/News/Detail", $data);
    }

    public function crud_news_edit($id)
    {
        $data["admin_page_name"] = "News Edit";
        $data["categories_list"] = $this->AdminModel->categories_admin_db_get_results();
        $data["news_data"] = $this->AdminModel->news_admin_db_get($id);
        $this->load->view("admins/News/Edit", $data);
    }

    public function crud_news_edit_action($id)
    {
        $old_news_data = json_decode($this->AdminModel->news_admin_db_get($id)["n_data"], TRUE);
        $news_img_path = "./file_manager/news/" . $old_news_data["news_preview"];

        $news_title_en             = $this->input->post("news_title_en", TRUE);
        $news_title_ru             = $this->input->post("news_title_ru", TRUE);
        $news_title_az             = $this->input->post("news_title_az", TRUE);
        $news_short_description_en = $this->input->post("news_short_description_en", TRUE);
        $news_short_description_ru = $this->input->post("news_short_description_ru", TRUE);
        $news_short_description_az = $this->input->post("news_short_description_az", TRUE);
        $news_full_description_en  = $this->input->post("news_full_description_en", FALSE);
        $news_full_description_ru  = $this->input->post("news_full_description_ru", FALSE);
        $news_full_description_az  = $this->input->post("news_full_description_az", FALSE);
        $news_category             = base64_encode($this->input->post("news_category", TRUE));
        $news_status               = $this->input->post("news_status", TRUE);

        $categories_list_data = $this->AdminModel->categories_admin_db_get_results();

        $categories_list = array_map(function ($category_item) {
            return json_decode($category_item["c_data"], TRUE);
        }, $categories_list_data);

        $category_data = array_merge(...array_filter($categories_list, function ($v) use ($news_category) {
            if (in_array($news_category, $v["category_name"], TRUE)) {
                return $v;
            }
        }));

        if (empty($category_data)) {
            $this->AlertFlashData(
                "danger",
                "crud_alert",
                "Danger!",
                "Unknown error."
            );
            redirect(base_url("admin/news-create"));
        }

        $news_config["upload_path"]      = "./file_manager/news/";
        $news_config["allowed_types"]    = "ico|jpeg|jpg|png|svg|ICO|JPEG|JPG|PNG|SVG";
        $news_config["file_ext_tolower"] = TRUE;
        $news_config["remove_spaces"]    = TRUE;
        $news_config["encrypt_name"]     = TRUE;
        $this->load->library("upload", $news_config);
        $this->upload->initialize($news_config);

        if (
            $this->upload->do_upload("news_preview_img")
            && !empty($news_title_en)
            && !empty($news_title_ru)
            && !empty($news_title_az)
            && !empty($news_short_description_en)
            && !empty($news_short_description_ru)
            && !empty($news_short_description_az)
            && !empty($news_full_description_en)
            && !empty($news_full_description_ru)
            && !empty($news_full_description_az)
            && !empty($news_category)
        ) {
            if (!is_dir($news_img_path) && file_exists($news_img_path)) {
                unlink($news_img_path);
            }

            $news_preview = $this->upload->data();

            $preview_img_config["image_library"] = "gd2";
            $preview_img_config["source_image"] = $news_config["upload_path"] . $news_preview["file_name"];
            $preview_img_config["maintain_ratio"] = FALSE;
            $preview_img_config["width"] = 1920;
            $preview_img_config["height"] = 1080;

            $this->load->library("image_lib", $preview_img_config);
            $this->load->initialize($preview_img_config);

            $this->image_lib->resize();

            $json_data_decoded = [
                "news_title" => [
                    "en" => base64_encode($news_title_en),
                    "ru" => base64_encode($news_title_ru),
                    "az" => base64_encode($news_title_az)
                ],

                "news_short" => [
                    "en" => base64_encode($news_short_description_en),
                    "ru" => base64_encode($news_short_description_ru),
                    "az" => base64_encode($news_short_description_az)
                ],

                "news_full" => [
                    "en" => base64_encode($news_full_description_en),
                    "ru" => base64_encode($news_full_description_ru),
                    "az" => base64_encode($news_full_description_az)
                ],
                "news_preview" => $news_preview["file_name"],
                "news_category" => [
                    "en" => $category_data["category_name"]["en"],
                    "ru" => $category_data["category_name"]["ru"],
                    "az" => $category_data["category_name"]["az"]
                ],
                "news_category_bg_color" => $category_data["category_bg_color"],
                "news_status" => str_contains($news_status, "on") ? TRUE : FALSE,
                "news_created_date" => date("d.m.Y"),
                "news_created_time" => date("H:i")
            ];

            $json_data_encoded = json_encode($json_data_decoded);

            $data = [
                "n_data" => $json_data_encoded
            ];

            $this->AdminModel->news_admin_db_update($id, $data);

            $this->AlertFlashData(
                "success",
                "crud_alert",
                "Success!",
                "The news has been successfully edited."
            );

            redirect(base_url("admin/news-list"));
        } else if (
            !empty($news_title_en)
            && !empty($news_title_ru)
            && !empty($news_title_az)
            && !empty($news_short_description_en)
            && !empty($news_short_description_ru)
            && !empty($news_short_description_az)
            && !empty($news_full_description_en)
            && !empty($news_full_description_ru)
            && !empty($news_full_description_az)
            && !empty($news_category)
        ) {
            $json_data_decoded = [
                "news_title" => [
                    "en" => base64_encode($news_title_en),
                    "ru" => base64_encode($news_title_ru),
                    "az" => base64_encode($news_title_az)
                ],

                "news_short" => [
                    "en" => base64_encode($news_short_description_en),
                    "ru" => base64_encode($news_short_description_ru),
                    "az" => base64_encode($news_short_description_az)
                ],

                "news_full" => [
                    "en" => base64_encode($news_full_description_en),
                    "ru" => base64_encode($news_full_description_ru),
                    "az" => base64_encode($news_full_description_az)
                ],
                "news_preview" => $old_news_data["news_preview"],
                "news_category" => [
                    "en" => $category_data["category_name"]["en"],
                    "ru" => $category_data["category_name"]["ru"],
                    "az" => $category_data["category_name"]["az"]
                ],
                "news_category_bg_color" => $category_data["category_bg_color"],
                "news_status" => str_contains($news_status, "on") ? TRUE : FALSE,
                "news_created_date" => date("d.m.Y"),
                "news_created_time" => date("H:i"),
            ];

            $json_data_encoded = json_encode($json_data_decoded);

            $data = [
                "n_data" => $json_data_encoded
            ];

            $this->AdminModel->news_admin_db_update($id, $data);

            $this->AlertFlashData(
                "success",
                "crud_alert",
                "Success!",
                "The news has been successfully edited."
            );

            redirect(base_url("admin/news-list"));
        } else {
            $this->AlertFlashData(
                "warning",
                "crud_alert",
                "Warning!",
                "Please, fill in all the fields."
            );
            redirect($_SERVER["HTTP_REFERER"]);
        }
    }

    public function crud_news_delete($id)
    {
        $news_data = json_decode($this->AdminModel->news_admin_db_get($id)["n_data"], TRUE);
        $news_img_path = "./file_manager/news/" . $news_data["news_preview"];
        if (!is_dir($news_img_path) && file_exists($news_img_path)) {
            unlink($news_img_path);
        }

        $this->AdminModel->news_admin_db_delete($id);

        $this->AlertFlashData(
            "success",
            "crud_alert",
            "Success!",
            "The news has been successfully removed."
        );

        redirect(base_url("admin/news-list"));
    }
    /*=====NEWS CRUD - ENDED=====*/



















    /*=====SUBSCRIBERS CRUD - START=====*/
    public function crud_subscribers_create()
    {
        $data["admin_page_name"] = "Subscribers Create";
        $this->load->view("admins/Subscribers/Create", $data);
    }

    public function crud_subscribers_create_action()
    {
        $subscriber_email = $this->input->post("subscriber_email", TRUE);
        $subscriber_status = $this->input->post("subscriber_status", TRUE);
        if (!empty($subscriber_email)) {
            $data = [
                "s_email"  => strtolower($subscriber_email),
                "s_status" => str_contains($subscriber_status, "on") ? TRUE : FALSE
            ];
            $this->AdminModel->subscribers_admin_db_insert($data);
            $this->AlertFlashData(
                "success",
                "crud_alert",
                "Success!",
                "The subscriber has been successfully added."
            );
            redirect(base_url("admin/subscribers-list"));
        } else {
            $this->AlertFlashData(
                "warning",
                "crud_alert",
                "Warning!",
                "Please, fill in all the fields."
            );
            redirect($_SERVER["HTTP_REFERER"]);
        }
    }

    public function crud_subscribers_edit($id)
    {
        $data["admin_page_name"] = "Subscribers Edit";
        $data["subscriber_data"] = $this->AdminModel->subscribers_admin_db_get($id);
        $this->load->view("admins/Subscribers/Edit", $data);
    }

    public function crud_subscribers_edit_action($id)
    {
        $subscriber_email = $this->input->post("subscriber_email", TRUE);
        $subscriber_status = $this->input->post("subscriber_status", TRUE);
        if (!empty($subscriber_email)) {
            $data = [
                "s_email"  => strtolower($subscriber_email),
                "s_status" => str_contains($subscriber_status, "on") ? TRUE : FALSE
            ];
            $this->AdminModel->subscribers_admin_db_update($id, $data);
            $this->AlertFlashData(
                "success",
                "crud_alert",
                "Success!",
                "The subscriber has been successfully edited."
            );
            redirect(base_url("admin/subscribers-list"));
        } else {
            $this->AlertFlashData(
                "warning",
                "crud_alert",
                "Warning!",
                "Please, fill in all the fields."
            );
            redirect($_SERVER["HTTP_REFERER"]);
        }
    }

    public function crud_subscribers_list()
    {
        $data["admin_page_name"] = "Subscribers List";
        $data["subscribers_list"] = $this->AdminModel->subscribers_admin_db_get_results();
        $this->load->view("admins/Subscribers/List", $data);
    }

    public function crud_subscribers_delete($id)
    {
        $this->AdminModel->subscribers_admin_db_delete($id);
        $this->AlertFlashData(
            "success",
            "crud_alert",
            "Success!",
            "The subscriber has been successfully removed."
        );
        redirect(base_url("admin/subscribers-list"));
    }
    /*=====SUBSCRIBERS CRUD - ENDED=====*/



















    /*=====SLIDER CRUD - START=====*/

    public function get_news_uid(int $news_uid, string $selector)
    {
        if (is_numeric($news_uid)) {
            @$news_uid_data = base64_decode(json_decode($this->AdminModel->news_admin_db_get($news_uid)["n_data"], TRUE)["news_title"]["en"]);
            if (!empty($news_uid_data) && $selector == "title") {
                $json = [
                    "title" => $news_uid_data,
                    "status" => 200
                ];
                print_r(json_encode($json));
            } else {
                print_r("-1");
            }
        } else {
            print_r("-1");
        }
    }

    public function crud_slider_create()
    {
        $data["admin_page_name"] = "Slider Create";
        $this->load->view("admins/Slider/Create", $data);
    }

    public function crud_slider_news_create_action()
    {
        $slider_news_uid    = $this->input->post("slider_news_uid", TRUE);
        $slider_news_status = $this->input->post("slider_news_status", TRUE);

        if (!empty($slider_news_uid) && is_numeric($slider_news_uid)) {
            @$news_uid_data = json_decode($this->AdminModel->news_admin_db_get($slider_news_uid)["n_data"], TRUE);
            if (empty($news_uid_data)) {
                $this->AlertFlashData(
                    "warning",
                    "crud_alert",
                    "Warning!",
                    "UID not found.Please enter a valid UID."
                );
                redirect(base_url("admin/slider-create"));
            }
        } else {
            $this->AlertFlashData(
                "danger",
                "crud_alert",
                "Danger!",
                "Unknown error."
            );
            redirect(base_url("admin/slider-create"));
        }

        $json_data_decoded = [
            "slider_type" => "slider_news",
            "slider_info" => [
                "slider_uid" => $slider_news_uid,
                "slider_status" => str_contains($slider_news_status, "on") ? TRUE : FALSE
            ]
        ];

        $data = [
            "s_data" => json_encode($json_data_decoded)
        ];

        $this->AdminModel->slider_admin_db_insert($data);

        $this->AlertFlashData(
            "success",
            "crud_alert",
            "Success!",
            "The slider has been successfully created."
        );

        redirect(base_url("admin/slider-list"));
    }

    public function crud_slider_custom_create_action()
    {
        $slider_custom_large_text       = $this->input->post("slider_custom_large_text", TRUE);
        $slider_custom_small_text       = $this->input->post("slider_custom_small_text", TRUE);
        $slider_custom_large_text_color = $this->input->post("slider_custom_large_text_color", TRUE);
        $slider_custom_small_text_color = $this->input->post("slider_custom_small_text_color", TRUE);
        $slider_custom_status           = $this->input->post("slider_custom_status", TRUE);

        $slider_custom_config["upload_path"]      = "./file_manager/slider/";
        $slider_custom_config["allowed_types"]    = "ico|jpeg|jpg|png|svg|ICO|JPEG|JPG|PNG|SVG";
        $slider_custom_config["file_ext_tolower"] = TRUE;
        $slider_custom_config["remove_spaces"]    = TRUE;
        $slider_custom_config["encrypt_name"]     = TRUE;
        $this->load->library("upload", $slider_custom_config);
        $this->upload->initialize($slider_custom_config);

        if ($this->upload->do_upload("slider_custom_img")) {
            $slider_custom_img = $this->upload->data()["file_name"];
            $json_data_decoded = [
                "slider_type" => "slider_custom",
                "slider_info" => [
                    "slider_img" => $slider_custom_img,
                    "slider_large_text" => base64_encode($slider_custom_large_text),
                    "slider_small_text" => base64_encode($slider_custom_small_text),
                    "slider_large_text_color" => $slider_custom_large_text_color,
                    "slider_small_text_color" => $slider_custom_small_text_color,
                    "slider_status" => str_contains($slider_custom_status, "on") ? TRUE : FALSE,
                ]
            ];

            $data = [
                "s_data" => json_encode($json_data_decoded)
            ];

            $this->AdminModel->slider_admin_db_insert($data);

            $this->AlertFlashData(
                "success",
                "crud_alert",
                "Success!",
                "The slider has been successfully created."
            );

            redirect(base_url("admin/slider-list"));
        } else {
            $this->AlertFlashData(
                "warning",
                "crud_alert",
                "Warning!",
                "Please, fill in all the fields."
            );

            redirect(base_url("admin/slider-create"));
        }
    }

    public function crud_slider_list()
    {
        $data["admin_page_name"] = "Slider List";
        $data["slider_results"] = $this->AdminModel->slider_admin_db_get_results();
        $this->load->view("admins/Slider/List", $data);
    }

    public function crud_slider_edit($id)
    {
        $data["admin_page_name"] = "Slider Edit";
        $data["slider_data"] = $this->AdminModel->slider_admin_db_get($id);
        $this->load->view("admins/Slider/Edit", $data);
    }

    public function crud_slider_edit_action($id)
    {
        $s_data = json_decode($this->AdminModel->slider_admin_db_get($id)["s_data"], TRUE);
        $s_img_path = "./file_manager/slider/" . $s_data["slider_info"]["slider_img"];
        $s_type = $s_data["slider_type"] == "slider_news" ? TRUE : FALSE;

        if ($s_type) {
            $slider_news_uid    = $this->input->post("slider_news_uid", TRUE);
            $slider_news_status = $this->input->post("slider_news_status", TRUE);

            if (!empty($slider_news_uid) && is_numeric($slider_news_uid)) {
                @$news_uid_data = json_decode($this->AdminModel->news_admin_db_get($slider_news_uid)["n_data"], TRUE);
                if (empty($news_uid_data)) {
                    $this->AlertFlashData(
                        "warning",
                        "crud_alert",
                        "Warning!",
                        "UID not found.Please enter a valid UID."
                    );
                    redirect($_SERVER["HTTP_REFERER"]);
                }
            } else {
                $this->AlertFlashData(
                    "danger",
                    "crud_alert",
                    "Danger!",
                    "Unknown error."
                );

                redirect(base_url("admin/slider-create"));
            }
            $json_data_decoded = [
                "slider_type" => "slider_news",
                "slider_info" => [
                    "slider_uid" => $slider_news_uid,
                    "slider_status" => str_contains($slider_news_status, "on") ? TRUE : FALSE
                ]
            ];
            $data = [
                "s_data" => json_encode($json_data_decoded)
            ];
            $this->AdminModel->slider_admin_db_update($id, $data);
            $this->AlertFlashData(
                "success",
                "crud_alert",
                "Success!",
                "The slider has been successfully edited."
            );
            redirect(base_url("admin/slider-list"));
        } else {

            $slider_custom_large_text       = $this->input->post("slider_custom_large_text", TRUE);
            $slider_custom_small_text       = $this->input->post("slider_custom_small_text", TRUE);
            $slider_custom_large_text_color = $this->input->post("slider_custom_large_text_color", TRUE);
            $slider_custom_small_text_color = $this->input->post("slider_custom_small_text_color", TRUE);
            $slider_custom_status           = $this->input->post("slider_custom_status", TRUE);

            $slider_custom_config["upload_path"]      = "./file_manager/slider/";
            $slider_custom_config["allowed_types"]    = "ico|jpeg|jpg|png|svg|ICO|JPEG|JPG|PNG|SVG";
            $slider_custom_config["file_ext_tolower"] = TRUE;
            $slider_custom_config["remove_spaces"]    = TRUE;
            $slider_custom_config["encrypt_name"]     = TRUE;
            $this->load->library("upload", $slider_custom_config);
            $this->upload->initialize($slider_custom_config);

            if ($this->upload->do_upload("slider_custom_img")) {
                if (!is_dir($s_img_path) && file_exists($s_img_path)) {
                    unlink($s_img_path);
                }
                $slider_custom_img = $this->upload->data()["file_name"];
                $json_data_decoded = [
                    "slider_type" => "slider_custom",
                    "slider_info" => [
                        "slider_img" => $slider_custom_img,
                        "slider_large_text" => base64_encode($slider_custom_large_text),
                        "slider_small_text" => base64_encode($slider_custom_small_text),
                        "slider_large_text_color" => $slider_custom_large_text_color,
                        "slider_small_text_color" => $slider_custom_small_text_color,
                        "slider_status" => str_contains($slider_custom_status, "on") ? TRUE : FALSE,
                    ]
                ];

                $data = [
                    "s_data" => json_encode($json_data_decoded)
                ];

                $this->AdminModel->slider_admin_db_update($id, $data);

                $this->AlertFlashData(
                    "success",
                    "crud_alert",
                    "Success!",
                    "The slider has been successfully edited."
                );

                redirect(base_url("admin/slider-list"));
            } else {

                $json_data_decoded = [
                    "slider_type" => "slider_custom",
                    "slider_info" => [
                        "slider_img" => $s_data["slider_info"]["slider_img"],
                        "slider_large_text" => base64_encode($slider_custom_large_text),
                        "slider_small_text" => base64_encode($slider_custom_small_text),
                        "slider_large_text_color" => $slider_custom_large_text_color,
                        "slider_small_text_color" => $slider_custom_small_text_color,
                        "slider_status" => str_contains($slider_custom_status, "on") ? TRUE : FALSE,
                    ]
                ];

                $data = [
                    "s_data" => json_encode($json_data_decoded)
                ];

                $this->AdminModel->slider_admin_db_update($id, $data);

                $this->AlertFlashData(
                    "success",
                    "crud_alert",
                    "Success!",
                    "The slider has been successfully edited."
                );

                redirect(base_url("admin/slider-list"));
            }
        }
    }

    public function crud_slider_delete($id)
    {
        $slider_data = json_decode($this->AdminModel->slider_admin_db_get($id)["s_data"], TRUE);

        if (!($slider_data["slider_type"] == "slider_news")) {
            $slider_img_path = "./file_manager/slider/" . $slider_data["slider_info"]["slider_img"];
            if (!is_dir($slider_img_path) && file_exists($slider_img_path)) {
                unlink($slider_img_path);
            }

            $this->AdminModel->slider_admin_db_delete($id);

            $this->AlertFlashData(
                "success",
                "crud_alert",
                "Success!",
                "The news has been successfully removed."
            );

            redirect(base_url("admin/slider-list"));
        } else {
            $this->AdminModel->slider_admin_db_delete($id);

            $this->AlertFlashData(
                "success",
                "crud_alert",
                "Success!",
                "The news has been successfully removed."
            );

            redirect(base_url("admin/slider-list"));
        }
    }
    /*=====SLIDER CRUD - ENDED=====*/

    /*=====CONTACTS CRUD - START=====*/
    public function crud_contacts_create()
    {
        $contacts_db_row = $this->AdminModel->table_row_id("contacts", "c_uid");
        if ($contacts_db_row == -1) {
            $data["admin_page_name"] = "Сontacts Create";
            $this->load->view("admins/Contacts/Create", $data);
        } else {
            redirect(base_url("admin/contacts-edit"));
        }
    }

    public function crud_contacts_create_action()
    {
        $contacts_db_row = $this->AdminModel->table_row_id("contacts", "c_uid");
        if ($contacts_db_row == -1) {
            $facebook_link      = $this->input->post("facebook_link", true);
            $facebook_status    = $this->input->post("facebook_status", true);
            $twitter_link       = $this->input->post("twitter_link", true);
            $twitter_status     = $this->input->post("twitter_status", true);
            $google_plus_link   = $this->input->post("google_plus_link", true);
            $google_plus_status = $this->input->post("google_plus_status", true);
            $instagram_link     = $this->input->post("instagram_link", true);
            $instagram_status   = $this->input->post("instagram_status", true);
            $youtube_link       = $this->input->post("youtube_link", true);
            $youtube_status     = $this->input->post("youtube_status", true);
            $pinterest_link     = $this->input->post("pinterest_link", true);
            $pinterest_status   = $this->input->post("pinterest_status", true);
            $address_info       = $this->input->post("address_info", true);
            $address_status     = $this->input->post("address_status", true);
            $mail_info          = $this->input->post("mail_info", true);
            $mail_status        = $this->input->post("mail_status", true);
            $phone_info         = $this->input->post("phone_info", true);
            $phone_status       = $this->input->post("phone_status", true);

            if (
                !empty($facebook_link) && !empty($twitter_link) &&
                !empty($google_plus_link) && !empty($instagram_link) &&
                !empty($pinterest_link) && !empty($address_info) &&
                !empty($mail_info) && !empty($phone_info)
            ) {
                $json_data_decoded = [
                    "social" => [
                        "facebook" => [
                            "link" => base64_encode($facebook_link),
                            "status" => str_contains($facebook_status, "on") ? TRUE : FALSE
                        ],
                        "twitter" => [
                            "link" => base64_encode($twitter_link),
                            "status" => str_contains($twitter_status, "on") ? TRUE : FALSE
                        ],
                        "google_plus" => [
                            "link" => base64_encode($google_plus_link),
                            "status" => str_contains($google_plus_status, "on") ? TRUE : FALSE
                        ],
                        "instagram" => [
                            "link" => base64_encode($instagram_link),
                            "status" => str_contains($instagram_status, "on") ? TRUE : FALSE
                        ],
                        "youtube" => [
                            "link" => base64_encode($youtube_link),
                            "status" => str_contains($youtube_status, "on") ? TRUE : FALSE
                        ],
                        "pinterest" => [
                            "link" => base64_encode($pinterest_link),
                            "status" => str_contains($pinterest_status, "on") ? TRUE : FALSE
                        ]
                    ],
                    "information" => [
                        "address" => [
                            "info" => base64_encode($address_info),
                            "status" => str_contains($address_status, "on") ? TRUE : FALSE
                        ],
                        "mail" => [
                            "info" => base64_encode($mail_info),
                            "status" => str_contains($mail_status, "on") ? TRUE : FALSE
                        ],
                        "phone" => [
                            "info" => base64_encode($phone_info),
                            "status" => str_contains($phone_status, "on") ? TRUE : FALSE
                        ]
                    ]
                ];

                $json_data_encoded = json_encode($json_data_decoded);

                $data = [
                    "c_data" => $json_data_encoded
                ];

                $this->AdminModel->contacts_admin_db_insert($data);

                $this->AlertFlashData(
                    "success",
                    "crud_alert",
                    "Success!",
                    "The contacts has been successfully created."
                );

                redirect(base_url("admin/contacts-edit"));
            } else {
                $this->AlertFlashData(
                    "warning",
                    "crud_alert",
                    "Warning!",
                    "Please, fill in all the fields."
                );

                redirect($_SERVER["HTTP_REFERER"]);
            }
        } else {
            redirect(base_url("admin/contacts-edit"));
        }
    }

    public function crud_contacts_edit()
    {
        $contacts_db_row = $this->AdminModel->table_row_id("contacts", "c_uid");
        if ($contacts_db_row == -1) {
            redirect(base_url("admin/contacts-create"));
        } else {
            $data["admin_page_name"] = "Contacts Edit";
            $data["contacts_data"] = $this->AdminModel->contacts_admin_db_get($contacts_db_row);
            $this->load->view("admins/Contacts/Edit", $data);
        }
    }

    public function crud_contacts_edit_action()
    {
        $contacts_db_row = $this->AdminModel->table_row_id("contacts", "c_uid");
        if ($contacts_db_row == -1) {
            redirect(base_url("admin/contacts-create"));
        } else {

            $facebook_link      = $this->input->post("facebook_link", true);
            $facebook_status    = $this->input->post("facebook_status", true);
            $twitter_link       = $this->input->post("twitter_link", true);
            $twitter_status     = $this->input->post("twitter_status", true);
            $google_plus_link   = $this->input->post("google_plus_link", true);
            $google_plus_status = $this->input->post("google_plus_status", true);
            $instagram_link     = $this->input->post("instagram_link", true);
            $instagram_status   = $this->input->post("instagram_status", true);
            $youtube_link       = $this->input->post("youtube_link", true);
            $youtube_status     = $this->input->post("youtube_status", true);
            $pinterest_link     = $this->input->post("pinterest_link", true);
            $pinterest_status   = $this->input->post("pinterest_status", true);
            $address_info       = $this->input->post("address_info", true);
            $address_status     = $this->input->post("address_status", true);
            $mail_info          = $this->input->post("mail_info", true);
            $mail_status        = $this->input->post("mail_status", true);
            $phone_info         = $this->input->post("phone_info", true);
            $phone_status       = $this->input->post("phone_status", true);

            if (
                !empty($facebook_link) && !empty($twitter_link) &&
                !empty($google_plus_link) && !empty($instagram_link) &&
                !empty($pinterest_link) && !empty($address_info) &&
                !empty($mail_info) && !empty($phone_info)
            ) {
                $json_data_decoded = [
                    "social" => [
                        "facebook" => [
                            "link" => base64_encode($facebook_link),
                            "status" => str_contains($facebook_status, "on") ? TRUE : FALSE
                        ],
                        "twitter" => [
                            "link" => base64_encode($twitter_link),
                            "status" => str_contains($twitter_status, "on") ? TRUE : FALSE
                        ],
                        "google_plus" => [
                            "link" => base64_encode($google_plus_link),
                            "status" => str_contains($google_plus_status, "on") ? TRUE : FALSE
                        ],
                        "instagram" => [
                            "link" => base64_encode($instagram_link),
                            "status" => str_contains($instagram_status, "on") ? TRUE : FALSE
                        ],
                        "youtube" => [
                            "link" => base64_encode($youtube_link),
                            "status" => str_contains($youtube_status, "on") ? TRUE : FALSE
                        ],
                        "pinterest" => [
                            "link" => base64_encode($pinterest_link),
                            "status" => str_contains($pinterest_status, "on") ? TRUE : FALSE
                        ]
                    ],
                    "information" => [
                        "address" => [
                            "info" => base64_encode($address_info),
                            "status" => str_contains($address_status, "on") ? TRUE : FALSE
                        ],
                        "mail" => [
                            "info" => base64_encode($mail_info),
                            "status" => str_contains($mail_status, "on") ? TRUE : FALSE
                        ],
                        "phone" => [
                            "info" => base64_encode($phone_info),
                            "status" => str_contains($phone_status, "on") ? TRUE : FALSE
                        ]
                    ]
                ];

                $json_data_encoded = json_encode($json_data_decoded);

                $data = [
                    "c_data" => $json_data_encoded
                ];

                $this->AdminModel->contacts_admin_db_edit($contacts_db_row, $data);

                $this->AlertFlashData(
                    "success",
                    "crud_alert",
                    "Success!",
                    "The contacts has been successfully edited."
                );

                redirect(base_url("admin/contacts-edit"));
            } else {
                $this->AlertFlashData(
                    "warning",
                    "crud_alert",
                    "Warning!",
                    "Please, fill in all the fields."
                );

                redirect($_SERVER["HTTP_REFERER"]);
            }
        }
    }

    public function crud_contacts_delete()
    {
        $contacts_db_row = $this->AdminModel->table_row_id("contacts", "c_uid");
        $this->AdminModel->contacts_admin_db_delete($contacts_db_row);

        $this->AlertFlashData(
            "success",
            "crud_alert",
            "Success!",
            "The contacts has been successfully removed."
        );

        redirect(base_url("admin/contacts-create"));
    }
    /*=====CONTACTS CRUD - ENDED=====*/

    /*=====GALLERY CRUD - START=====*/
    public function crud_gallery_create()
    {
        $data["admin_page_name"] = "Gallery Create";
        $this->load->view("admins/Gallery/Create", $data);
    }

    public function crud_gallery_create_action()
    {
        $gallery_config["upload_path"]      = "./file_manager/gallery/";
        $gallery_config["allowed_types"]    = "ico|jpeg|jpg|png|svg|ICO|JPEG|JPG|PNG|SVG";
        $gallery_config["file_ext_tolower"] = TRUE;
        $gallery_config["remove_spaces"]    = TRUE;
        $gallery_config["encrypt_name"]     = TRUE;
        $this->load->library("upload", $gallery_config);
        $this->upload->initialize($gallery_config);

        if ($this->upload->do_upload("gallery_img")) {
            $gallery_img = $this->upload->data()["file_name"];

            $json_data_decoded = [
                "gallery_file_name" => $gallery_img
            ];

            $json_data_ecoded = json_encode($json_data_decoded);

            $data = [
                "g_data" => $json_data_ecoded
            ];

            $this->AdminModel->gallery_admin_db_insert($data);

            $this->AlertFlashData(
                "success",
                "crud_alert",
                "Success!",
                "The image has been successfully added."
            );

            redirect(base_url("admin/gallery-list"));
        } else {
            $this->AlertFlashData(
                "warning",
                "crud_alert",
                "Warning!",
                "Please, fill in all the fields."
            );
            redirect($_SERVER["HTTP_REFERER"]);
        }
    }

    public function crud_gallery_list()
    {
        $data["admin_page_name"] = "Gallery List";
        $data["gallery_images"] = $this->AdminModel->gallery_admin_db_get_results();
        $this->load->view("admins/Gallery/List", $data);
    }

    public function crud_gallery_delete($id)
    {
        $gallery_img_path = "./file_manager/gallery/" . json_decode($this->AdminModel->gallery_admin_db_get($id)["g_data"], TRUE)["gallery_file_name"];

        if (!is_dir($gallery_img_path) && file_exists($gallery_img_path)) {
            unlink($gallery_img_path);
        }

        $this->AdminModel->gallery_admin_db_delete($id);

        $this->AlertFlashData(
            "success",
            "crud_alert",
            "Success!",
            "The image has been successfully deleted."
        );

        redirect(base_url("admin/gallery-list"));
    }
    /*=====GALLERY CRUD - ENDED=====*/
}
