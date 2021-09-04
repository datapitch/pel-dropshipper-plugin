<?php
if (! defined('ABSPATH')) exit;// Exit if accessed directly

//Temaplate class
class DropshipTemplate
{
    private $plugin_name;
    private $version;
    private $language;
    private $license_no;
    private $domain;
    private $api_domain;
    private $data;

    public function __construct($dataArray)
    {
        $this->plugin_name = $dataArray['plugin_name'];
        $this->version = $dataArray['plugin_version'];
        $this->domain = $dataArray['plugin_domain'];
        $this->api_domain = $dataArray['api_domain'];
        $this->data = $dataArray;

        //calling menu
        add_action('admin_menu', array($this,'mantell_plugin_setup_menu'));
        add_action('admin_head', array($this,'icon_admin_menu_mantella'));


        add_action('wp_ajax_send_data_action', array($this,'sendinfotomantella'));
        add_action('wp_ajax_nopriv_send_data_action', array($this,'sendinfotomantella'));

        add_action('wp_ajax_check_import_status', array($this,'checkImportStatus'));
        add_action('wp_ajax_nopriv_check_import_status', array($this,'checkImportStatus'));
      
        add_action('wp_ajax_get_imported_products_status', array($this,'getImportedProductsStatus'));
        add_action('wp_ajax_nopriv_get_imported_products_status', array($this,'getImportedProductsStatus'));
    }

    // Adding Menu to Plugin
    public function mantell_plugin_setup_menu()
    {
        add_menu_page('Dropship Tool', 'Dropship Tool', 'manage_woocommerce', 'mantella', array($this, 'dropship_platform'), 'div');
    }

    //Adding Menu image and style
    public function icon_admin_menu_mantella()
    {
        $this->hover_icon = plugin_dir_url( __FILE__ ) . 'assets/mantella_icon-hover.png';
        $this->icon = plugin_dir_url( __FILE__ ) . 'assets/mantella_icon.png';
        echo '<style type="text/css">
	    #adminmenu #toplevel_page_mantella div.wp-menu-image {
            background:url('. $this->icon .') no-repeat;
            background-position:center center;
            background-size: 50%;
        }
		
		#adminmenu #toplevel_page_mantella:hover div.wp-menu-image{
            background:url('. $this->hover_icon . ') no-repeat;
            background-position:center center;
            background-size: 50%;
	    }
		
		.form-group.row {
            margin-right: 0px;
            margin-left: 0px;
        }
		
		.form-group.row .col-sm-12 {
			padding-left: 0px;
			
		}
		
		.form-valid  {
			opacity:0.5;
		}
		
		.form-control-label abbr {
			text-decoration: none;
			font-weight: normal;
		}
		
		.clserror{
		    display: none;
		}
		
		.form-control.is-valid, .was-validated .form-control:valid {
            border-color: #23282d;
            padding-right: calc(1.5em + .75rem);
            background-image: none !important;
            background-repeat: no-repeat;
            background-position: right calc(.375em + .1875rem) center;
            background-size: calc(.75em + .375rem) calc(.75em + .375rem);
        }
        
        .form-control.is-invalid, .was-validated .form-control:invalid {
            border-color: #dc3545;
            padding-right: calc(1.5em + .75rem);
            background-image: none !important;
            background-repeat: no-repeat;
            background-position: right calc(.375em + .1875rem) center;
            background-size: calc(.75em + .375rem) calc(.75em + .375rem);
        } 
        
        .form-check-input.is-valid~.form-check-label, .was-validated .form-check-input:valid~.form-check-label {
            color: #23282d !important; 
        }
                
        .form-check-input.is-invalid~.form-check-label, .was-validated .form-check-input:invalid~.form-check-label {
            color: #dc3545;
        }
        
        .row {
            display: flex;
            flex-wrap: wrap;
            margin: 0 -15px
        }
        .row>div {
            padding-left: 15px;
            padding-right: 15px
        }
        .col1 {
            width: 8.33%
        }
        .col2 {
            width: 16.66%
        }
        .col3 {
            width: 25%
        }
        .col4 {
            width: 33.33%
        }
        .col5 {
            width: 41.66%
        }
        .col6 {
            width: 50%
        }
        .col7 {
            width: 58.33%
        }
        .col8 {
            width: 66.66%
        }
        .col9 {
            width: 75%
        }
        .col10 {
            width: 83.33%
        }
        .col11 {
            width: 91.66%
        }
        .col12 {
            padding: 0 15px;
            width: 100%
        }
        .text-center {
            text-align:center;
            }
		</style>';

    }

    //Getting total products perpage
    public function GetTotalFeed($perpage)
    {
        global $wpdb;
        include_once( ABSPATH . 'wp-admin/includes/upgrade.php' );
        $this->record = $wpdb->get_results("select * from " . $wpdb->prefix . "license_key");
        $this->license_no = $this->record[0]->license_no;
        $this->language = $this->record[0]->language;
        $this->url = $this->api_domain . "feeds/" . $this->domain . "/" . $this->license_no . "/feed/total?perpage=". $perpage;

        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->ch, CURLOPT_URL, $this->url);
        $this->l_result = curl_exec($this->ch);
        curl_close($this->ch);
        $this->license_key_status = json_decode($this->l_result, true);

        return $this->license_key_status["pages"];

    }

    //Checking License Key is valid or not
    public function CheckLicenseKey($license_no, $domain)
    {
        $this->url = $this->api_domain . "feeds/" . $domain . "/" . $license_no . "/feed/total";

        $this->ch = curl_init();
        curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($this->ch, CURLOPT_URL, $this->url);
        $this->l_result = curl_exec($this->ch);
        curl_close($this->ch);
        $this->license_key_status = json_decode($this->l_result, true);

        return $this->license_key_status;

    }

    //Sending site details to mantella
    public function sendinfotomantella(){
        include_once plugin_dir_path( dirname( __FILE__ ) ) . 'core/mantella_sendtoportal.php';
        $dsp = new DropshipSendtoPortal($this->data);
        $_SESSION['SEND_TO_PORTAL_MSG'] = $dsp->sendtoAPI();
        //var_dump($dsp->sendtoAPI());
        //echo'<hr>';
        //var_dump(json_encode($dsp->sendtoAPI()));
        echo json_encode($dsp->sendtoAPI());
        exit();
    }

    //sending data to portal
    public function sendinfotoPortal(){
        include_once plugin_dir_path( dirname( __FILE__ ) ) . 'core/mantella_sendtoportal.php';
        $dsp = new DropshipSendtoPortal($this->data);
        $dsp->sendtoAPI();
    }
    //current import fetching record
    public function getImportedProductsStatus(){
        global $wpdb;
        
        $record = $wpdb->get_results("select perpage from " . $wpdb->prefix . "license_key");
        $page_number = $wpdb->get_var("SELECT page_no FROM " . $wpdb->prefix . "dropship_import_cron_dtl");
       
        $total_imported_count = "";
        if($page_number != '')
        { 
            $total_imported_count = $page_number* $record[0]->perpage;
        }
        echo json_encode($total_imported_count);
        exit();   
    }

    //checking cron process is working or not
    public function checkImportStatus(){
        global $wpdb;
               
        $dtlrecord = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "dropship_import_cron_dtl");       
        
        $status_val = "In process...";
        if(count($dtlrecord) == 1)
        { 
            $past_time = date('h:i:s', strtotime($dtlrecord[0]->modified_on));        
            $past_time_stamp = strtotime($past_time);

            $cur_time_stamp = strtotime(date('h:i:s'));
            $total_minutes = round(($cur_time_stamp - $past_time_stamp) / 60);
        
            if ($total_minutes > 5)
            {
                $dropship_temp_table = $wpdb->prefix . "dropship_import_cron_dtl_temp";
                $dtltemprecord = $wpdb->get_results("SELECT * FROM " . $dropship_temp_table);  
                if(count($dtltemprecord) == 0)
                { 
                    $next_pages_value = $dtlrecord[0]->page_no+1;
                    $temptabledata = array(
                        'page_no'       => $next_pages_value, 
                        'created_on'    => date('Y-m-d h:i:s')
                    );                 

                    $wpdb->insert( $dropship_temp_table, $temptabledata);
                    $status_val = "Process stop";
                }
                
            }
        }
        echo json_encode($status_val);
        exit();   
    }

    //Showing Plugin Layout
    function dropship_platform()
    {
        global $wpdb;
        include_once( ABSPATH . 'wp-admin/includes/upgrade.php' );

        //Adding stylesheet
        wp_enqueue_style('_inc', plugins_url('/assets/layout.css', __FILE__));
        $this->record = $wpdb->get_results("select * from " . $wpdb->prefix . "license_key");

        //support
        $this->support_data = $wpdb->get_results("select * from " . $wpdb->prefix . "support");

        //Checking for domain API url is live or demo
        if($this->api_domain != 'https://portal.mantella.nl/')
        {
            $this->domain_status_err_message = __('The plugin is connected to ', 'dropship-tool-by-mantella');
            echo '<div class="notice notice-error is-dismissible"><p>'. $this->domain_status_err_message . $this->api_domain . '</p></div>';

        }

        if (isset($_POST['save_perpage']))
        {
            $this->perpage = $_POST['perpage'];
            $this->total_feed = $this->GetTotalFeed($this->perpage);

            $this->sql_support = "UPDATE `" . $wpdb->prefix . "license_key` SET `total_feed`='". $this->total_feed ."', `perpage`='". $this->perpage ."' WHERE id=". $this->record[0]->id;
            $wpdb->query($this->sql_support);

            $this->save_msg = __('Saved successfully.', 'dropship-tool-by-mantella');
            $_SESSION['SAVEMSG'] = $this->save_msg;

            $this->record = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "license_key");

        }

        if (isset($_POST['sendtoportal']))
        {
            print_r($_POST);
            include_once plugin_dir_path( dirname( __FILE__ ) ) . 'core/mantella_sendtoportal.php';
            $dsp = new DropshipSendtoPortal($this->data);
            $_SESSION['SEND_TO_PORTAL_MSG'] = $dsp->sendtoAPI();
            echo 'Submitted';
        }

        if (isset($_POST['add']))
        {
            $this->license_no = $_POST['key'];
            $this->license_key_status = $this->CheckLicenseKey($this->license_no, $this->domain);
            if(isset($this->license_key_status))
            {
                if(isset($this->license_key_status['access']) && $this->license_key_status['access']=='denied')
                {
                    $this->license_status = $this->license_key_status['reason'];
                    $this->license_status_err_message = __('Your Dropship Tool license key is invalid. Product stock will not be checked and product information will not be updated.', 'dropship-tool-by-mantella');
                    echo '<script>jQuery(document).ready(function(){ jQuery("#seterrmsgkey").show(); jQuery("#seterrmsgkey").fadeIn("slow").delay(20000).hide(0); });</script>';
                    //echo '<div class="notice notice-error is-dismissible"><p>'. $this->license_status_err_message . '</p></div>';

                }else{

                    $this->license_status = 'The license key is valid and active';

                }

            }else{
                $this->license_status = 'License key is blank';
                $this->license_status_err_message = __('Your Dropship Tool license key is invalid. Product stock will not be checked and product information will not be updated.', 'dropship-tool-by-mantella');
                echo '<script>jQuery(document).ready(function(){ jQuery("#seterrmsgkey").show(); jQuery("#seterrmsgkey").fadeIn("slow").delay(20000).hide(0); });</script>';
                //echo '<div class="notice notice-error is-dismissible"><p>'. $this->license_status_err_message . '</p></div>';
            }

            $this->stock_status         = 'Yes';
            $this->prices_status        = (isset($_POST['prices_status']))?$_POST['prices_status']:'No';
            $this->categories_status    = (isset($_POST['categories_status']))?$_POST['categories_status']:'No';
            $this->allproduct_status    = (isset($_POST['allproduct_status']))?'Yes':'No';
            $this->time_var		        = (isset($_POST['time_var']))?$_POST['time_var']:'60';
            $this->perpage              = (isset($_POST['perpage']))?$_POST['perpage']:"10";
            $this->ean_show_in_front    = (isset($_POST['ean_show_in_front']))? $_POST['ean_show_in_front']:'No';
            $this->brand_show_in_front  = (isset($_POST['brand_show_in_front']))? $_POST['brand_show_in_front']:'No';
            $this->brand_with_title_status  = (isset($_POST['brand_with_title_status']))? $_POST['brand_with_title_status']:'No';
            $this->product_attributes_status = (isset($_POST['product_attributes_status']))?$_POST['product_attributes_status']:'No';
            $this->title_status  = (isset($_POST['title_status']))? $_POST['title_status']:'No';
            $this->language             = (isset($_POST['language']))? $_POST['language']:'NL';

            if(!empty($this->stock_status) || !empty($this->prices_status) || !empty($this->categories_status) )
            {

                $this->ean = 'Yes';

            }else{

                $this->ean = 'No';

            }

            $array_list['stock_status']                     = $this->stock_status;
            $array_list['stock_status_update']              = (isset($_POST['stock_status_update']))?$_POST['stock_status_update']:'No';
            $array_list['prices_status']                    = $this->prices_status;
            $array_list['prices_status_update']             = (isset($_POST['prices_status_update']))?$_POST['prices_status_update']:'No';
            $array_list['categories_status']                = $this->categories_status;
            $array_list['categories_status_update']         = (isset($_POST['categories_status_update']))?$_POST['categories_status_update']:'No';
            $array_list['title_status']                     = $this->title_status;
            $array_list['title_status_update']              = (isset($_POST['title_status_update']))?$_POST['title_status_update']:'No';
            $array_list['url_status']                       = (isset($_POST['url_status']))?$_POST['url_status']:'No';
            $array_list['url_status_update']                = (isset($_POST['url_status_update']))?$_POST['url_status_update']:'No';
            $array_list['product_attributes_status']        = $this->product_attributes_status;
            $array_list['product_attributes_status_update'] = (isset($_POST['product_attributes_status_update']))?$_POST['product_attributes_status_update']:'No';
            $array_list['attribute_type']                   = (isset($_POST['attribute_type']))?$_POST['attribute_type']:'local';
            $array_list['description_status']               = (isset($_POST['description_status']))?$_POST['description_status']:'No';
            $array_list['description_status_update']        = (isset($_POST['description_status_update']))?$_POST['description_status_update']:'No';
            $array_list['shipping_class_status']            = (isset($_POST['shipping_class_status']))?$_POST['shipping_class_status']:'No';
            $array_list['shipping_class_status_update']     = (isset($_POST['shipping_class_status_update']))?$_POST['shipping_class_status_update']:'No';
            $array_list['brand_status']                     = (isset($_POST['brand_status']))?$_POST['brand_status']:'No';
            $array_list['brand_status_update']              = (isset($_POST['brand_status_update']))?$_POST['brand_status_update']:'No';
            $array_list['image_status']                     = (isset($_POST['image_status']))?$_POST['image_status']:'No';
            $array_list['image_status_update']              = (isset($_POST['image_status_update']))?$_POST['image_status_update']:'No';
            $array_list['allproduct_status']                = (isset($_POST['allproduct_status']))?$_POST['allproduct_status']:'No';
            $array_list['allproduct_status_update']         = (isset($_POST['allproduct_status_update']))?$_POST['allproduct_status_update']:'No';
            $array_list['added_product_status']             = (isset($_POST['added_product_status']))?$_POST['added_product_status']:'publish';
           
            $import_update_data = serialize($array_list);

            if($array_list['brand_status'] == 'No')
            {
                $this->brand_show_in_front  = 'No';
            }

            if (isset($this->record[0]))
            {
                global $wpdb;
                $this->table_name = $wpdb->prefix.'license_key';
                $this->data_update = array(
                    'license_no'	 	=> $this->license_no,
                    'ean_code'	 		=> $this->ean,
                    'stock_status' 		=> $this->stock_status,
                    'prices_status' 	=> $this->prices_status,
                    'categories_status' => $this->categories_status,
                    'allproduct_status' => $this->allproduct_status,
                    'language' 			=> $this->language,
                    'ean_show_in_front' => $this->ean_show_in_front,
                    'license_status' 	=> $this->license_status,
                    'time_var' 			=> $this->time_var,
                    'perpage'           => $this->perpage,
                    'brand_show_in_front' => $this->brand_show_in_front,
                    'brand_with_title_status' => $this->brand_with_title_status,
                    'product_attributes_status' => $this->product_attributes_status,
                    'title_status' => $this->title_status,
                    'import_settings' => $import_update_data
                );

                $this->data_where = array('id'=>$this->record[0]->id);
                $wpdb->update($this->table_name , $this->data_update, $this->data_where);

            } else {

                $wpdb->insert($wpdb->prefix . "license_key",
                    array(
                        'license_no' 		=> $this->license_no,
                        'ean_code'	 		=> $this->ean,
                        'stock_status' 		=> $this->stock_status,
                        'prices_status' 	=> $this->prices_status,
                        'categories_status' => $this->categories_status,
                        'allproduct_status' => $this->allproduct_status,
                        'language' 			=> $this->language,
                        'ean_show_in_front' => $this->ean_show_in_front,
                        'license_status' 	=> $this->license_status,
                        'time_var' 			=> $this->time_var,
                        'perpage'           => $this->perpage,
                        'brand_show_in_front' => $this->brand_show_in_front,
                        'brand_with_title_status' => $this->brand_with_title_status,
                        'product_attributes_status' => $this->product_attributes_status,
                        'title_status' => $this->title_status,
                        'import_settings' => $import_update_data
                    )
                );

            }
            
            //data send to portal to update shop info
            $this->sendinfotoPortal();
            $this->record = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "license_key");

        }

        if (isset($_POST['import_product']))
        {
            include_once plugin_dir_path( dirname( __FILE__ ) ) . 'core/wp-product-import.php';
            $dpi = new DropshipProductImport($this->data);

            $this->record = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "license_key");
            $this->license_no = $this->record[0]->license_no;
            $this->c_rec = $wpdb->get_results("SELECT count(*) as tot FROM " . $wpdb->prefix . "dropship_import_cron_dtl");
            $this->count = $this->c_rec[0]->tot;
            $this->perpage =$this->record[0]->perpage;

            //Check if there is a current import running
            if($this->count > 0)
            {
                $this->count_msg = __('there is already an import running in the background.', 'dropship-tool-by-mantella');
                $_SESSION['PROMSG'] = $this->count_msg;

            } else {

                //Add total feed in table
                $this->url = $this->api_domain . "feeds/" . $this->domain . "/" . $this->license_no . "/feed/total?perpage=".$this->perpage;

                $this->ch = curl_init();
                curl_setopt($this->ch, CURLOPT_RETURNTRANSFER, 1);
                curl_setopt($this->ch, CURLOPT_URL, $this->url);
                $this->outer_result = curl_exec($this->ch);
                curl_close($this->ch);
                $this->outer_json = json_decode($this->outer_result, true);
                $this->total_feed = $this->outer_json['pages'];

                $this->sql_support = $wpdb->prepare("UPDATE `" . $wpdb->prefix . "license_key` SET `total_feed`='". $this->total_feed ."' WHERE id=%d",$this->record[0]->id);
                $wpdb->query($this->sql_support);

                //insert a row with pagenumber is 1 in both tables
                $this->created_on	= date("Y-m-d H:i:s");

                $wpdb->insert($wpdb->prefix . "dropship_import_cron_dtl_temp",
                    array(
                        'page_no'		=> 1,
                        'created_on' 	=> $this->created_on
                    )
                );

                $wpdb->insert($wpdb->prefix . "dropship_import_cron_dtl",
                    array(
                        'page_no'		=> 1,
                        'created_on' 	=> $this->created_on
                    )
                );

                $dpi->mantella_cron_productimport_function_now();

                $this->count_msg2 = __('The import will start soon. It can take some time for it to finish. Please be patient', 'dropship-tool-by-mantella');

                $_SESSION['PROMSG'] = $this->count_msg2;
              
                $this->checkImportStatusInTable();
                $this->callImportStatusScript($this->perpage);
            }

        }

        if (isset($_POST['stop_import']))
        {
            $this->sql_del = "DELETE FROM `" . $wpdb->prefix . "dropship_import_cron_dtl`";
            $wpdb->query($this->sql_del);

            $this->sql_del2 = "DELETE FROM `" . $wpdb->prefix . "dropship_import_cron_dtl_temp`";
            $wpdb->query($this->sql_del2);

            $this->count_msg3 = "";
            $_SESSION['PROMSG'] = $this->count_msg3;

        }

        //support
        if (isset($_POST['suport_add']))
        {
            $this->title = $_POST['title'];
            $this->description = $_POST['description'];
            if (isset($this->support_data[0]))
            {
                $this->sql_support = $wpdb->prepare("UPDATE `" . $wpdb->prefix . "support` SET `title`='$this->title', `description`='$this->description' WHERE id=1");
                $wpdb->query($this->sql_support);

            } else {

                $this->sql_support = $wpdb->prepare("INSERT INTO `" . $wpdb->prefix . "support`(`title`,`description`) values('$this->title', '$this->description')");
                $wpdb->query($this->sql_support);
            }

            $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "support");

            $this->support_msg = "<div class='alert alert-success' role='alert'>
			<strong>Well done!</strong> Support Successfuly Submited.
			</div>";

            $_SESSION['SUPMSG'] = $this->support_msg;

        }

        $this->logo_img = plugin_dir_url(__FILE__) . 'assets/dropshiptool-by-mantella-logo.png';

        ?>
        <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/css/bootstrap.min.css" crossorigin="anonymous">
        <link href="https://stackpath.bootstrapcdn.com/font-awesome/4.7.0/css/font-awesome.min.css"  rel="stylesheet">

        <!-- Latest compiled JavaScript -->
        <script src="https://cdn.jsdelivr.net/npm/popper.js@1.16.0/dist/umd/popper.min.js" crossorigin="anonymous"></script>
        <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.5.0/js/bootstrap.min.js" crossorigin="anonymous"></script>
        <script>
            jQuery(function () {
                jQuery('[data-toggle="tooltip"]').tooltip()
            })

            jQuery(document).ready(function(){
                var updatekeystatus = jQuery('#Updateinputname').val();
                var insertkeystatus = jQuery('#Insertinputname').val();
                if(updatekeystatus){
                    jQuery('.warnmsg').removeClass("clserror");
                }
                if(insertkeystatus){
                    jQuery('.warnmsg').removeClass("clserror");
                }

                jQuery('#Updateinputname').blur(function(){
                    var updatekeystatusb = jQuery('#Updateinputname').val();
                    if(!updatekeystatusb){
                        jQuery('.warnmsg').addClass("clserror");
                    }else{
                        jQuery('.warnmsg').removeClass("clserror");
                    }
                });

                //general tab messages
                jQuery('#gensucmessage').hide();
                jQuery('#generrmessage').hide();
                jQuery('#generalbtn').click(function(){
                    jQuery('#gensucmessage').show();
                    jQuery('#gensucmessage').fadeIn('slow').delay(50000).hide(0);
                });

                //settings tab messages
                jQuery('#setsucmsg').hide();
                jQuery('#setsucmsg2').hide();
                jQuery('#seterrmsg').hide();
                jQuery('#settingsbtn').click(function(){
                    jQuery('#setsucmsg').show();
                    jQuery('#setsucmsg').fadeIn('slow').delay(50000).hide(0);
                });

                //support tab messages
                jQuery('#supsucmsg').hide();

                //Showing active tab after refresh
                jQuery('a[data-toggle="tab"]').on('show.bs.tab', function(e) {
                    localStorage.setItem('activeTab', jQuery(e.target).attr('href'));
                });

                var activeTab = localStorage.getItem('activeTab');
                if(activeTab){
                    jQuery('#pluginTab a[href="' + activeTab + '"]').tab('show');
                }

                //Form validation showing
                jQuery("#UpdateForm").submit(function(event) {
                    jQuery('.content_brand_title').hide();
                    var vForm = jQuery(this);
                    if (vForm[0].checkValidity() === false) {
                        event.preventDefault()
                        event.stopPropagation()
                    } else {
                        //alert("your form is valid and ready to send");
                    }
                    vForm.addClass('was-validated');
                });

                jQuery("#InsertForm").submit(function(event) {
                    jQuery('.content_brand_title').hide();
                    var vForm = jQuery(this);
                    if (vForm[0].checkValidity() === false) {
                        event.preventDefault()
                        event.stopPropagation()
                    } else {
                        //alert("your form is valid and ready to send");
                    }
                    vForm.addClass('was-validated');
                });

            });

        </script>

        <div id="exTab2" class="container">
            <h4>
                <img src="<?php echo $this->logo_img; ?>" style="max-width: 300px; height: auto;" alt="<?php echo __('Dropship tool by Mantella', 'dropship-tool-by-mantella'); ?>" title="<?php echo __('Dropship tool by Mantella', 'dropship-tool-by-mantella'); ?>">
            </h4>
            <div class="site_tab">
                <ul class="nav nav-tabs" id="pluginTab" role="tablist">
                    <?php if($this->record[0]->license_status=='The license key is valid and active'){ ?>
                        <li class="nav-item"><a href="#general" class="nav-link" data-toggle="tab" aria-selected="false"><?php echo __('General', 'dropship-tool-by-mantella'); ?></a></li>
                    <?php } ?>
                    <li class="nav-item"><a href="#settings" class="nav-link active" data-toggle="tab" aria-selected="true"><?php echo __('Settings', 'dropship-tool-by-mantella'); ?></a></li>
                    <li class="nav-item"><a href="#support" class="nav-link" data-toggle="tab" aria-selected="false"><?php echo __('Support', 'dropship-tool-by-mantella'); ?></a></li>
                </ul>
            </div>

            <div class="tab-content">

                <?php if($this->record[0]->license_status=='The license key is valid and active'){ }else{ if($this->license_status_err_message){ echo '<div class="notice notice-error is-dismissible" style="margin:0px;"><p>'. $this->license_status_err_message . '</p></div><br/>'; } } ?>

                <div id="general" class="tab-pane fade">
                    <!-- Alert for success -->
                    <div id="gensucmessage" class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo __('The import will start soon. It can take some time for it to finish. Please be patient', 'dropship-tool-by-mantella'); ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <?php
                    $this->btn_disable="";
                    $this->page_no = $wpdb->get_var("SELECT page_no FROM " . $wpdb->prefix . "dropship_import_cron_dtl");
                    $this->tot_imp_prod_count = ($this->page_no-1)* $this->record[0]->perpage;
                    $this->promsg = isset($_SESSION['PROMSG'])? $_SESSION['PROMSG']: "";
                    ?>
                    <div class="tab-inner-content">
                        <div class="tab-summery">
                            <div class="row">
                                <div class="col-sm-12 mb-3">
                                    <h5><?php echo __('Start the import', 'dropship-tool-by-mantella'); ?></h5>
                                </div>
                                <?php
                                //Start with SQL Query to see if there a import steps in the database. This is in the cron_dtl table.
                                $this->c_rec = $wpdb->get_results("SELECT COUNT(*) as tot FROM " . $wpdb->prefix . "dropship_import_cron_dtl");
                                $this->count = $this->c_rec[0]->tot;

                                //Check if there is a current import running
                                if($this->count > 0)
                                {
                                    $page_rec = $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "dropship_import_cron_dtl_temp ORDER BY id DESC LIMIT 1");
                                    ?>
                                    <div class="col-sm-1">
                                        <i class="fa fa-spinner fa-pulse fa-3x fa-fw"></i>
                                    </div>
                                    <div class="col-sm-11">
                                        <p>
                                            <?php echo __('At this moment the plugin is importing your products into your webshop.', 'dropship-tool-by-mantella'); ?>
                                            <br>
                                            <?php echo __('So far ', 'dropship-tool-by-mantella');  ?>
                                            <span id="sofar_products_count">
                                            <?php echo $this->tot_imp_prod_count; ?>
                                            </span>
                                            <?php echo __(' products have been imported and updated.', 'dropship-tool-by-mantella'); ?>
                                        </p>
                                    </div>

                                    <div class="col-sm-12 mt-3">
                                        <p>
                                        <form class="form-wrap license_form" id="form_stop_import" autocomplete="off" method="post">
                                            <div class="input-wrap">
                                                <input id="stop_import" type="submit" name="stop_import" value="<?php echo __('Stop the import and update process', 'dropship-tool-by-mantella'); ?>" class="btn mntll-color">
                                            </div>
                                        </form>
                                        </p>
                                    </div>
                                <?php }else { ?>
                                    <div class="col-sm-12 mt-3">
                                        <p>
                                        <form class="form-wrap license_form" id="form_start_import" autocomplete="off" method="post">
                                            <div class="input-wrap">
                                                <input id="generalbtn" type="submit" name="import_product" value="<?php echo __('Import & Update products in your webshop', 'dropship-tool-by-mantella'); ?>" class="btn mntll-color">
                                            </div>
                                        </form>
                                        </p>
                                    </div>
                                <?php } ?>

                                <div class="col-sm-12 mt-3">
                                    <hr class="mb-4">

                                    <p>
                                    <!-- div class="alert alert-info alert-dismissible fade show" role="alert">
                                        <strong>Important</strong>
                                        We have added a new functionality to import the shipping classes from TOM BV for the products.<br>
                                        You need to add the shipping costs for each shipping class in your WooCommerce settings.
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div -->
                                    <?php if(MAX_EXECUTION_TIME_LIMIT < 180 && MAX_EXECUTION_TIME_LIMIT != '-1' && MAX_EXECUTION_TIME_LIMIT != 0){ ?>
                                    <div class="alert alert-info alert-dismissible fade show" role="alert">
                                        <strong>Warning!</strong>                                       
                                        <?php printf( __( 'WarningMsg', 'dropship-tool-by-mantella' ), MAX_EXECUTION_TIME_LIMIT ); ?>
                                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <?PHP } ?>
                                    </p>
                                </div>

                            </div>
                        </div>
                    </div>
                </div>
                <!-- #general ends -->

                <!-- #Settings starts -->
                <div id="settings" class="tab-pane fade show active" role="tabpanel">
                    <!-- Alert for errors -->
                    <div id="seterrmsgkey" class="alert alert-danger alert-dismissible fade show" style="display:none;" role="alert">
                        <strong><?php echo __('Error!', 'dropship-tool-by-mantella'); ?></strong> <?php echo $this->license_status_err_message; ?>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <!-- Alert for errors -->
                    <div id="seterrmsg" class="alert alert-danger alert-dismissible fade show" style="display:none;" role="alert">
                        <strong><?php echo __('Error!', 'dropship-tool-by-mantella'); ?></strong>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <!-- Alert for success -->
                    <div id="setsucmsg" class="alert alert-success alert-dismissible fade show" style="display:none;" role="alert">
                        <strong><?php echo __('Success!', 'dropship-tool-by-mantella'); ?></strong>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>

                    <!-- Alert for success -->
                    <div id="setsucmsg2" class="alert alert-info alert-dismissible fade show" style="display:none;" role="alert">
                        <strong><?php echo __('Info!', 'dropship-tool-by-mantella'); ?></strong>
                        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                    </div>
                    <form class="needs-validation form-wrap license_form" novalidate="" id="UpdateForm" autocomplete="off" method="post">
                    <div class="tab-inner-content">
                        <div class="tab-summery">
                            <?php
                            
                                if($this->record[0]->license_status=='The license key is valid and active')
                                {
                                    $this->style ='style="color:#2c7c1d; font-weight:bold;"';
                                    $this->license_status_dtl = __('The license key is valid and active', 'dropship-tool-by-mantella');

                                }else{
                                    if(isset($this->record[0]->license_status) && $this->record[0]->license_status=='Licensekey not available')
                                    {
                                        $this->license_status_dtl = __('The license key is invalid. Please contact Mantells Support', 'dropship-tool-by-mantella');
                                        $this->style ='style="color:#dc3545 !important; font-weight:bold;"';

                                    }else{
                                        if(isset($this->record[0]->license_status))
                                        {
                                            $this->license_status_dtl = __('Invalid license key', 'dropship-tool-by-mantella');
                                            $this->style ='style="color:#dc3545 !important; font-weight:bold;"';
                                        }
                                    }
                                }
                                //all import and update value unserilize here
                                $import_attrdata = unserialize($this->record[0]->import_settings);  

                                $this->exemsg = isset($_SESSION['EXEMSG'])? $_SESSION['EXEMSG']: "";

                                $this->exemsg1 = ini_get('max_execution_time');

                                if($this->exemsg1==0 || $this->exemsg1==-1)
                                {
                                    $this->exemsg1 = 1000;
                                }
                                ?>
                                
                                    <div class="form-group row">
                                        <div class="col-sm-12">
                                            <h5><?php echo __('License', 'dropship-tool-by-mantella'); ?></h5>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label for="inputname" class="col-sm-3 col-form-label"><?php echo __('LICENSE KEY', 'dropship-tool-by-mantella'); ?></label>
                                        <div class="col-sm-8">
                                            <input type="text" class="form-control" id="Updateinputname" name="key" placeholder="<?php echo __('Enter your license key', 'dropship-tool-by-mantella'); ?>" value="<?php echo $this->record[0]->license_no; ?>" required="required">
                                            <div class="invalid-feedback"><?php echo __('License key field is blank', 'dropship-tool-by-mantella'); ?></div>
                                            <p><span class="text-success warnmsg clserror" <?php echo $this->style; ?> ><?php echo $this->license_status_dtl; ?></span></p>
                                        </div>
                                        <div class="col-sm-1">
                                            <button type="button" class="btn" data-toggle="tooltip" data-html="true" title="<em><?php echo __('Tooltip 1', 'dropship-tool-by-mantella'); ?></em>">
                                                <i class="fa fa-info-circle" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <hr class="mb-4">

                                    <div class="form-group row">
                                        <div class="col-sm-12">
                                            <h5><?php  __('Settings', 'dropship-tool-by-mantella'); ?></h5>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label for="" class="col-sm-3 col-form-label"><?php echo __('Choose import product language', 'dropship-tool-by-mantella'); ?>:</label>
                                        <div class="col-sm-8">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" id="lng1" name="language" value="EN" <?php if ($this->record[0]->language == 'EN') { echo "checked"; } ?>>
                                                <label class="form-check-label" for="lng1">EN</label>
                                            </div>

                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" id="lng2" name="language" value="NL" <?php if ($this->record[0]->language == 'NL') { echo "checked"; } ?>>
                                                <label class="form-check-label" for="lng2">NL</label>
                                            </div>

                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" id="lng3" name="language" value="DE" <?php if ($this->record[0]->language == 'DE') { echo "checked"; } ?>>
                                                <label class="form-check-label" for="lng3">DE</label>
                                            </div>
                                        </div>
                                        <div class="col-sm-1">
                                            <button type="button" class="btn" data-toggle="tooltip" data-html="true" title="<em><?php echo __('Tooltip 3', 'dropship-tool-by-mantella'); ?></em>">
                                                <i class="fa fa-info-circle" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label for="" class="col-sm-3 col-form-label"><?php echo __('Update product information', 'dropship-tool-by-mantella'); ?>:</label>

                                        <div class="col-sm-8">
                                            <select class="selectpicker" id="time_var" name="time_var">
                                                <option value="15" <?php  if ($this->record[0]->time_var == "5") 	{ echo "selected";} echo'>'. __('Every 5 minutes', 'dropship-tool-by-mantella');?></option>
                                                <option value="15" <?php  if ($this->record[0]->time_var == "15") 	{ echo "selected";} echo'>'. __('Every quarter', 'dropship-tool-by-mantella');?></option>
                                                <option value="30" <?php  if ($this->record[0]->time_var == "30") 	{ echo "selected";} echo'>'. __('Every half hour', 'dropship-tool-by-mantella');?></option>
                                                <option value="60" <?php  if ($this->record[0]->time_var > 60) 		{ echo "selected";} echo'>'. __('Every hour', 'dropship-tool-by-mantella');?></option>
                                            </select>
                                        </div>

                                        <div class="col-sm-1">
                                            <button type="button" class="btn" data-toggle="tooltip" data-html="true" title="<em><?php echo __('Tooltip 4', 'dropship-tool-by-mantella'); ?></em>">
                                                <i class="fa fa-info-circle" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label for="" class="col-sm-3 col-form-label"><?php echo __('Want to show EAN in frontend?', 'dropship-tool-by-mantella'); ?></label>
                                        <div class="col-sm-8">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" id="inlineRadio1" name="ean_show_in_front" value="Yes" <?php if ($this->record[0]->ean_show_in_front == 'Yes') { echo "checked";} ?> >
                                                <label class="form-check-label" for="inlineRadio1"><?php echo __('Yes', 'dropship-tool-by-mantella'); ?></label>
                                            </div>

                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="ean_show_in_front" value="No" id="inlineRadio2"<?php if ($this->record[0]->ean_show_in_front == 'No') { echo "checked"; } ?>>
                                                <label class="form-check-label" for="inlineRadio2"><?php echo __('No', 'dropship-tool-by-mantella'); ?></label>
                                            </div>
                                        </div>
                                        <div class="col-sm-1">
                                            <button type="button" class="btn" data-toggle="tooltip" data-html="true" title="<em><?php echo __('Tooltip 5', 'dropship-tool-by-mantella'); ?></em>">
                                                <i class="fa fa-info-circle" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="form-group row">
                                        <label for="" class="col-sm-3 col-form-label"><?php echo __('Want to show Brand in frontend?', 'dropship-tool-by-mantella'); ?></label>
                                        <div class="col-sm-8">
                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" id="inlineRadio3" name="brand_show_in_front" value="Yes" <?php if ($this->record[0]->brand_show_in_front == 'Yes') { echo "checked";} ?> >
                                                <label class="form-check-label" for="inlineRadio3"><?php echo __('Yes', 'dropship-tool-by-mantella'); ?></label>
                                            </div>

                                            <div class="form-check form-check-inline">
                                                <input class="form-check-input" type="radio" name="brand_show_in_front" value="No" id="inlineRadio4"<?php if ($this->record[0]->brand_show_in_front == 'No') { echo "checked"; } ?>>
                                                <label class="form-check-label" for="inlineRadio4"><?php echo __('No', 'dropship-tool-by-mantella'); ?></label>
                                            </div>
                                        </div>
                                        <div class="col-sm-1">
                                            <button type="button" class="btn" data-toggle="tooltip" data-html="true" title="<em><?php echo __('Tooltip 51', 'dropship-tool-by-mantella'); ?></em>">
                                                <i class="fa fa-info-circle" aria-hidden="true"></i>
                                            </button>
                                        </div>
                                    </div>
                                    <div class="form-group row" >
                                    <label for="" class="col-sm-3 col-form-label" style=" padding-left: 0px !important;"><?php echo __('Add brand name to title?', 'dropship-tool-by-mantella'); ?></label>
                                    <div class="col-sm-8">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" id="brand_title_yes" name="brand_with_title_status" value="Yes" <?php if ($this->record[0]->brand_with_title_status == 'Yes') { echo "checked";} ?> >
                                            <label class="form-check-label" for="brand_title_yes"><?php echo __('Yes', 'dropship-tool-by-mantella'); ?></label>
                                        </div>

                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="brand_with_title_status" value="No" id="brand_title_no"<?php if ($this->record[0]->brand_with_title_status == 'No') { echo "checked"; } ?>>
                                            <label class="form-check-label" for="brand_title_no"><?php echo __('No', 'dropship-tool-by-mantella'); ?></label>
                                        </div>
                                        <p class="content_brand_title" style="display: none;"><span class="text-success warnmsg" <?php echo $this->style; ?> id="content_brand_title"><?php echo __('Please select your option', 'dropship-tool-by-mantella'); ?></span></p>
                                    </div>
                                    <div class="col-sm-1">
                                        <button type="button" class="btn" data-toggle="tooltip" data-html="true" title="<em><?php echo __('Tooltip 52', 'dropship-tool-by-mantella'); ?></em>">
                                            <i class="fa fa-info-circle" aria-hidden="true"></i>
                                        </button>
                                    </div>                                   
                                </div>
                                <div class="form-group row" >
                                    <label for="" class="col-sm-3 col-form-label" style=" padding-left: 0px !important;"><?php echo __('What status should newly added products have?', 'dropship-tool-by-mantella'); ?></label>
                                    <div class="col-sm-8">
                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" id="added_product_status_publish" name="added_product_status" value="publish" <?php if ($import_attrdata['added_product_status'] != 'draft') { echo "checked"; } ?>>
                                            <label class="form-check-label" for="added_product_status_publish"><?php echo __('Published', 'dropship-tool-by-mantella'); ?></label>
                                        </div>

                                        <div class="form-check form-check-inline">
                                            <input class="form-check-input" type="radio" name="added_product_status" value="draft" id="added_product_status_draft"<?php if ($import_attrdata['added_product_status'] == 'draft') { echo "checked"; } ?>>
                                            <label class="form-check-label" for="added_product_status_draft"><?php echo __('Concept', 'dropship-tool-by-mantella'); ?></label>
                                        </div>                                        
                                    </div>
                                    <div class="col-sm-1">
                                        <button type="button" class="btn" data-toggle="tooltip" data-html="true" title="<em><?php echo __('Tooltip 521', 'dropship-tool-by-mantella'); ?></em>">
                                            <i class="fa fa-info-circle" aria-hidden="true"></i>
                                        </button>
                                    </div>                                   
                                </div>

                                    <!-- replacement for import per batch. This is set to 10 for optimal results -->
                                    <input type="hidden" name="perpage" value="10">
                                    <div class="btn-group" role="group" aria-label="Basic example">                                        
                                        <input id="save_perpage_btn" class="btn mntll-color " type="submit" name="add" value="<?php echo __('Save', 'dropship-tool-by-mantella');?>">
                                    </div>
                                

                        </div>
                    </div>
                    <!-- Additional tab -->         
                    <div class="tab-inner-content" style="margin-top: 10px;">          
                        <div class="tab-summery customSummery">
                            <div class="form-group row">
                                <div class="col-sm-12">
                                    <h5><?php echo __('Import/ Update Settings', 'dropship-tool-by-mantella'); ?></h5>
                                </div>
                            </div>
                            

                            <div class="row">
                                <div class="col3"><strong></strong></div>
                                <div class="col3"></div>
                                <div class="col2 text-center"><strong><?php echo __('Import', 'dropship-tool-by-mantella'); ?></strong></div>
                                <div class="col2 text-center"><strong><?php echo __('Update', 'dropship-tool-by-mantella'); ?></strong></div>
                                <div class="col2 text-center"><strong><?php echo __('Extra', 'dropship-tool-by-mantella'); ?></strong></div>
                            </div>
                            <div class="row">
                                <div class="col3"><strong><?php echo  __('What information do you want to update?', 'dropship-tool-by-mantella'); ?> </strong></div>
                                <div class="col3"><span><?php echo __('Stock', 'dropship-tool-by-mantella'); ?></span></div>
                                <div class="col2 text-center"><label for=""><input class="form-check-stock" type="checkbox" id="stock_status" name="stock_status" value="<?php echo $import_attrdata['stock_status']; ?>" checked="checked" onclick="return false;"></label></div>
                                <div class="col2 text-center"><label for=""><input class="form-check-update" type="checkbox" id="stock_status_update" name="stock_status_update" value="<?php echo $import_attrdata['stock_status_update']; ?>" checked="checked" onclick="return false;"></label></div>
                                <div class="col2 text-center">
                                    <button type="button" class="btn" data-toggle="tooltip" data-html="true" title="<em><?php echo __('Tooltip 2', 'dropship-tool-by-mantella'); ?></em>">
                                        <i class="fa fa-info-circle" aria-hidden="true"></i>
                                    </button>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col3"><strong></strong></div>
                                <div class="col3"><span><?php echo __('Prices', 'dropship-tool-by-mantella'); ?></span></div>
                                <div class="col2 text-center"><label for=""><input class="form-check-prices-status" type="checkbox" id="prices_status" name="prices_status" value="<?php echo $import_attrdata['prices_status']; ?>" checked="checked" onclick="return false;"></label></div>
                                <div class="col2 text-center"><label for=""><input class="form-check-update" type="checkbox" name="prices_status_update" value="<?php echo $import_attrdata['prices_status_update']; ?>" <?php if ($import_attrdata['prices_status_update'] == 'Yes') { echo "checked";} ?>></label></div>
                                <div class="col2"></div>
                            </div>
                            <div class="row">
                                <div class="col3"><strong></strong></div>
                                <div class="col3"><span><?php echo __('Categories', 'dropship-tool-by-mantella'); ?></span></div>
                                <div class="col2 text-center"><label for=""><input class="form-check-import" type="checkbox" id="categories_status" name="categories_status" value="<?php echo $import_attrdata['categories_status']; ?>" <?php if ($import_attrdata['categories_status'] == 'Yes') { echo "checked";} ?>></label></div>
                                <div class="col2 text-center"><label for=""><input class="form-check-update" type="checkbox" name="categories_status_update" value="<?php echo $import_attrdata['categories_status_update']; ?>" <?php if ($import_attrdata['categories_status_update'] == 'Yes') { echo "checked";} ?>></label></div>
                                <div class="col2"></div>
                            </div>
                            <div class="row">
                                <div class="col3"><strong></strong></div>
                                <div class="col3"><span><?php echo __('Title', 'dropship-tool-by-mantella'); ?></span></div>
                                <div class="col2 text-center"><label for=""><input class="form-check-title-status" type="checkbox" id="title_status" name="title_status" value="<?php echo $import_attrdata['title_status']; ?>" checked="checked" onclick="return false;"></label></div>
                                <div class="col2 text-center"><label for=""><input class="form-check-update" type="checkbox" name="title_status_update" value="<?php echo $import_attrdata['title_status_update']; ?>" <?php if ($import_attrdata['title_status_update'] == 'Yes') { echo "checked";} ?>></label></div>
                                <div class="col2 text-center"></div>
                            </div>
                            <div class="row">
                                <div class="col3"><strong></strong></div>
                                <div class="col3"><span><?php echo __('URL', 'dropship-tool-by-mantella'); ?></span></div>
                                <div class="col2 text-center"><label for=""><input class="form-check-url-status" type="checkbox" id="url_status" name="url_status" value="<?php echo $import_attrdata['url_status']; ?>" checked="checked" onclick="return false;"></label></div>
                                <div class="col2 text-center"><label for=""><input class="form-check-update" type="checkbox" name="url_status_update" value="<?php echo $import_attrdata['url_status_update']; ?>" <?php if ($import_attrdata['url_status_update'] == 'Yes') { echo "checked";} ?>></label></div>
                                <div class="col2"></div>
                            </div>
                            <div class="row">
                                <div class="col3"><strong></strong></div>
                                <div class="col3"><span><?php echo __('Product attributes', 'dropship-tool-by-mantella'); ?></span></div>
                                <div class="col2 text-center"><label for=""><input class="form-check-import" type="checkbox" id="product_attributes_status" name="product_attributes_status" value="<?php echo $import_attrdata['product_attributes_status']; ?>" <?php if ($import_attrdata['product_attributes_status'] == 'Yes') { echo "checked";} ?>></label></div>
                                <div class="col2 text-center"><label for=""><input class="form-check-update" type="checkbox" name="product_attributes_status_update" value="<?php echo $import_attrdata['product_attributes_status_update']; ?>" <?php if ($import_attrdata['product_attributes_status_update'] == 'Yes') { echo "checked";} ?>></label></div>
                                <div class="col2 text-center"><select name="attribute_type">
                                    <option value="local" <?php if ($import_attrdata['attribute_type'] == 'local') { echo "selected";} ?>>Local Attribute</option>
                                    <option value="global" <?php if ($import_attrdata['attribute_type'] == 'global') { echo "selected";} ?>>Global Attribute</option>
                                </select>
                                <button type="button" class="btn" data-toggle="tooltip" data-html="true" title="<em><?php echo __('More information about the attributes', 'dropship-tool-by-mantella'); ?></em>">
                                    <i class="fa fa-info-circle" aria-hidden="true"></i>
                                </button>
                                    <a href="<?php echo __('URL1', 'dropship-tool-by-mantella'); ?>" target="_blank" class="external-url"><?php echo __('More info', 'dropship-tool-by-mantella'); ?></a>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col3"><strong></strong></div>
                                <div class="col3"><span><?php echo __('Description', 'dropship-tool-by-mantella'); ?></span></div>
                                <div class="col2 text-center"><label for=""><input class="form-check-import" type="checkbox" id="description_status" name="description_status" value="<?php echo $import_attrdata['description_status']; ?>" <?php if ($import_attrdata['description_status'] == 'Yes') { echo "checked";} ?>></label></div>
                                <div class="col2 text-center"><label for=""><input class="form-check-update" type="checkbox" name="description_status_update" value="<?php echo $import_attrdata['description_status_update']; ?>" <?php if ($import_attrdata['description_status_update'] == 'Yes') { echo "checked";} ?>></label></div>
                                <div class="col2"></div>
                            </div>
                            <div class="row">
                                <div class="col3"><strong></strong></div>
                                <div class="col3"><span><?php echo __('Shipping Class', 'dropship-tool-by-mantella'); ?></span></div>
                                <div class="col2 text-center"><label for=""><input class="form-check-import" type="checkbox" id="shipping_class_status" name="shipping_class_status" value="<?php echo $import_attrdata['shipping_class_status']; ?>" <?php if ($import_attrdata['shipping_class_status'] == 'Yes') { echo "checked";} ?>></label></div>
                                <div class="col2 text-center"><label for=""><input class="form-check-update" type="checkbox" name="shipping_class_status_update" value="<?php echo $import_attrdata['shipping_class_status_update']; ?>" <?php if ($import_attrdata['shipping_class_status_update'] == 'Yes') { echo "checked";} ?>></label></div>
                                <div class="col2"></div>
                            </div>
                            <div class="row">
                                <div class="col3"><strong></strong></div>
                                <div class="col3"><span><?php echo __('Brand', 'dropship-tool-by-mantella'); ?></span></div>
                                <div class="col2 text-center"><label for=""><input class="form-check-import" type="checkbox" id="brand_status" name="brand_status" value="<?php echo $import_attrdata['brand_status']; ?>" <?php if ($import_attrdata['brand_status'] == 'Yes') { echo "checked";} ?>></label></div>
                                <div class="col2 text-center"><label for=""><input class="form-check-update" type="checkbox" name="brand_status_update" value="<?php echo $import_attrdata['brand_status_update']; ?>" <?php if ($import_attrdata['brand_status_update'] == 'Yes') { echo "checked";} ?>></label></div>
                                <div class="col2"></div>
                            </div>
                            <div class="row">
                                <div class="col3"><strong></strong></div>
                                <div class="col3"><span><?php echo __('Images', 'dropship-tool-by-mantella'); ?></span></div>
                                <div class="col2 text-center"><label for=""><input class="form-check-image-status" type="checkbox" id="image_status" name="image_status" value="<?php echo $import_attrdata['image_status']; ?>" checked="checked" onclick="return false;"></label></div>
                                <div class="col2 text-center"><label for=""><input class="form-check-update" type="checkbox" name="image_status_update" value="<?php echo $import_attrdata['image_status_update']; ?>" <?php if ($import_attrdata['image_status_update'] == 'Yes') { echo "checked";} ?>></label></div>
                                <div class="col2"></div>
                            </div>
                            <div class="row">
                                <div class="col3"><strong></strong></div>
                                <div class="col3"><span><?php echo __('All Product information', 'dropship-tool-by-mantella'); ?></span></div>
                                <div class="col2 text-center"><label for=""><input class="form-check-import" type="checkbox" id="allproduct_status" name="allproduct_status" value="<?php echo $import_attrdata['allproduct_status']; ?>" <?php if ($import_attrdata['allproduct_status'] == 'Yes') { echo "checked";} ?>></label></div>
                                <div class="col2 text-center"><label for=""><input class="form-check-update" type="checkbox" name="allproduct_status_update" id="allproduct_status_update" value="<?php echo $import_attrdata['allproduct_status_update']; ?>" <?php if ($import_attrdata['allproduct_status_update'] == 'Yes') { echo "checked";} ?>></label></div>
                                <div class="col2"></div>
                            </div>

                            
                            <hr class="mb-4">
                            <div class="btn-group" role="group" aria-label="Basic example">
                                <input id="save_perpage_btn" class="btn mntll-color " type="submit" name="add" value="<?php echo __('Save', 'dropship-tool-by-mantella');?>">
                            </div>
                        </div>     
                    </div>           
                    </form>   
                    <!-- Additional tab end -->
                </div><!-- #settings end -->

                <script>
                    jQuery(document).ready(function(){
                        jQuery(".customSummery .row input").on("click",function(){
                             
                            if(jQuery(this).is(':checked') == true)
                            {
                                jQuery(this).attr('value', 'Yes');

                            }else{
                                jQuery(this).attr('value', 'No');
                                if(jQuery(this).hasClass("form-check-import") == true){
                                    jQuery('#allproduct_status').attr('value', 'No');
                                    jQuery('#allproduct_status').prop('checked', false);
                                }
                                if(jQuery(this).hasClass("form-check-update") == true){
                                    jQuery('#allproduct_status_update').attr('value', 'No');
                                    jQuery('#allproduct_status_update').prop('checked', false);
                                }
                                
                            }
                            
                        });
                        jQuery("#allproduct_status").on("click",function(){
                            
                            if(jQuery(this).is(':checked') == true)
                            {
                                jQuery('.form-check-import').attr('value', 'Yes');
                                jQuery('.form-check-import').prop('checked', true);

                            }else{
                                jQuery('.form-check-import').attr('value', 'No');
                                jQuery('.form-check-import').prop('checked', false);
                            }
                            
                        });
                        jQuery("#allproduct_status_update").on("click",function(){
                                                    
                            if(jQuery(this).is(':checked') == true)
                            {
                                jQuery('.form-check-update').attr('value', 'Yes');
                                jQuery('.form-check-update').prop('checked', true);

                            }else{
                                jQuery('.form-check-update').attr('value', 'No');
                                jQuery('.form-check-update').prop('checked', false);
                            }

                        });
                        
                    });
                </script>
                
                <!-- #functies end -->
            </div>
        </div> <!-- #exTab2 end-->
        <?php
    } 
  
    //This function is used to check record in every 5 minutes
    public function checkImportStatusInTable()
    {
        ?>
        <script>

            setInterval(check_Import_Status, 300000);

            function check_Import_Status(){  
              var ajaxurl ="<?php echo admin_url( 'admin-ajax.php' ); ?>";
                jQuery.ajax({
                    type: "post",
                    dataType: "json",
                    url: ajaxurl,
                    data: {
                      "action": "check_import_status"
                       },
                    success:function(response) {
                       
                        var responsedata = JSON.parse(JSON.stringify(response));
                        if(responsedata){
                          console.log('done');
                          }
                        
                    },
                    error: function(errorThrown){
                        console.log(errorThrown);
                    }
                });                

            }
        </script>

        <?php
  }          
  
    //This function is used to replace the import message and products
    public function callImportStatusScript($timeinterval)
    {
        $interval_values = $timeinterval*2;
        ?>
        <script>

            setInterval(get_Product_Import_Status, '<?php echo $interval_values.'000'; ?>');

            function get_Product_Import_Status(){  
                var ajaxurl ="<?php echo admin_url( 'admin-ajax.php' ); ?>";
                jQuery.ajax({
                    type: "post",
                    dataType: "json",
                    url: ajaxurl,
                    data: {
                      "action": "get_imported_products_status"
                       },
                    success:function(response) {
                        var responsedata = JSON.parse(JSON.stringify(response));
                        if(responsedata){
                           jQuery("#sofar_products_count").text(responsedata);
                        }else{
                            location.reload();
                        }
                    },
                    error: function(errorThrown){
                        console.log(errorThrown);
                    }
                });                

            }
        </script>

        <?php
    }
}
