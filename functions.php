<?php
/**
 * Enqueue script and styles for child theme
 */
function woodmart_child_enqueue_styles() {
    wp_enqueue_style( 'child-style', get_stylesheet_directory_uri() . '/style.css', array( 'woodmart-style' ), woodmart_get_theme_info( 'Version' ) );
}
add_action( 'wp_enqueue_scripts', 'woodmart_child_enqueue_styles', 10010 );


define( 'WOODMART_THEME_DIR_CHILD', get_template_directory_uri() );
define( 'WOODMART_THEMEROOT_CHILD',  get_template_directory() );
define( 'WOODMART_IMAGES', WOODMART_THEME_DIR_CHILD . '-child/images/' );
define( 'WOODMART_INC_CHILD', WOODMART_THEMEROOT_CHILD . '-child/inc/' );
define( 'WOODMART_MODULES_CHILD', WOODMART_THEMEROOT_CHILD . '-child/inc/modules/' );
define( 'WOODMART_INTEGRATIONS_CHILD', WOODMART_THEMEROOT_CHILD . '-child/inc/integrations/' );

define( 'FIXED_DELIVERY_CHARGE', 10 );
define( 'MINIMUM_ORDER_CHARGE', 49 );
define( 'EXPRESS_DELIVERY_CHARGE', 15 );
define( 'PINCODE_NOT_FIXED_DELIVERY_CHARGE', 25 );

require_once( WOODMART_MODULES_CHILD.'sticky-toolbar.php' );
require_once( WOODMART_INTEGRATIONS_CHILD.'woocommerce/template-tags.php' );

require_once( WOODMART_INC_CHILD.'pincode-delivery-charge.php' );
require_once( WOODMART_INC_CHILD.'delivery-charge-admin.php' );
require_once( WOODMART_INC_CHILD.'woocommerce-customer-custom-send-emails.php' );
// require_once( WOODMART_INC_CHILD.'woocommerce-customize-front-end.php' );

if( ! function_exists( 'fixed_delivery_charge_totals' ) ) {
    add_action( 'woocommerce_cart_calculate_fees', 'fixed_delivery_charge_totals', 10, 1 );
    function fixed_delivery_charge_totals( $cart_object ) {
    
        if( is_admin() && !defined( 'DOING_AJAX' ) ) return;

        $deliveryOption = WC()->session->get('delivery_option') ?? 'defaultdelivery';
	    
	    if( !WC()->cart->is_empty() && $deliveryOption=='defaultdelivery' )
	    {
            // Displayed TOTAL CART CONTENT
            $fixedCharge = FIXED_DELIVERY_CHARGE;
           
		    /* old code for shipping charge */
			
			//$cartTotal = $cart_object->cart_contents_total;
			

			 /* new  code for shipping charge */
            $cartTotal = $cart_object->subtotal;
			/* ----- over */

            if( $cartTotal <= MINIMUM_ORDER_CHARGE )
            {
            	$cart_object->add_fee( "Fixed delivery charge", $fixedCharge, true );
            }
        }
        else
        {
        	$charge = WC()->session->get('SENDLE_PINCODE_NOT_DELIVERY_CHARGE') ?? PINCODE_NOT_FIXED_DELIVERY_CHARGE;
        	WC()->cart->add_fee(__('Fixed delivery charge', 'woodmart_child'), $charge);
        }
    }
}


// postcode code admin start
if( !function_exists('checkboxToRadiobutton') )
{
	add_action('admin_footer', 'checkboxToRadiobutton');
	function checkboxToRadiobutton()
	{
	    echo '<script>jQuery("#taxonomy-delivery_zone li label input, #delivery_zonechecklist li label input, .delivery_zone-checklist li label input").each(function() { this.type = "radio"; });</script>';
	}
}
// postcode code admin end

// Postcode code start
if( !function_exists('postcode_form') )
{
    function postcode_form()
    {
        ob_start();
?>
        <form action="">
        	<div class="row">
        		<div class="col-md-8 mb-10">
					<input type="text" id="postcode" name="postcode" placeholder="POSTCODE" required>
					<span id="postcode_error" class="text-danger"></span>
				</div>
				<div class="col-md-4">
					<input type="submit" value="Submit" class="btnSubmitPostcode">
				</div>
			</div>
        </form>
<?php
        return ob_get_clean();
    }
    add_shortcode('postcodeform', 'postcode_form');
}

if( !function_exists('mobile_menu_postcode_form') )
{
    function mobile_menu_postcode_form()
    {
        ob_start();
?>
        <form action="">
        	<div class="mobile_delivery_options">
        		<span class="mobile_delivery_option_title">Delivery Options:</span>
        	
	        	<div class="row">
	        		<div class="col-md-8 mb-10">
						<input type="text" id="postcode" name="postcode" placeholder="ENTER POSTCODE" class="text-center" required>
						<span id="postcode_error" class="text-danger"></span>
					</div>
					<div class="col-md-4 d-flex justify-content-center">
						<input type="submit" value="Submit" class="btnSubmitPostcode">
					</div>
				</div>
			</div>
        </form>

        <style type="text/css">
        	.mobile_delivery_options .mobile_delivery_option_title {
				text-transform: uppercase;
				font-weight: 600;
				font-size: 13px;
				margin-bottom: 10px;
				color: #2d2a2a;
				width: 100%;
				display: inline-block;
			}
			.mobile_delivery_options .mobile_delivery_option_title:before {
			    margin-right: 10px;
			    content: "\f139";
			    font-family: woodmart-font;
			}
			.mobile_delivery_options .btnSubmitPostcode {
				background-color: #2d2a2a;
			}
			.mobile-nav .widgetarea-mobile {
				padding-top: 10px;
			}
        </style>
<?php
        return ob_get_clean();
    }
    add_shortcode('mobilemenu_postcodeform', 'mobile_menu_postcode_form');
}


if( !function_exists('ajax_postcode_form') )
{
    add_action( 'wp_footer', 'ajax_postcode_form' );
    function ajax_postcode_form()
    {
    ?>
        <script type="text/javascript" >
            jQuery(document).ready(function($)
            {
                jQuery(".btnSubmitPostcode").click( function(e)
                {
                    e.preventDefault();

                    var postCode = jQuery('#postcode').val();

                    jQuery('#postcode_error').text('');
                    if( postCode=='') {
                    	jQuery('#postcode_error').text('Please enter postcode');
                    	return false;
                    }

                    var data = {
                        'action': 'postcode_list',
                        'postcode': postCode
                    };

                    jQuery.ajax({
                        url: '<?php echo admin_url( 'admin-ajax.php' ) ?>', // this will point to admin-ajax.php
                        type: 'POST',
                        data: data,
                        success: function(response)
                        {
                            // console.log(response); return false;
                            window.location.href = response;             
                        }
                    });
                });
            });
        </script> 
    <?php
    }
}

if( !function_exists('postcode_list') )
{
    add_action("wp_ajax_postcode_list" , "postcode_list");
    add_action("wp_ajax_nopriv_postcode_list" , "postcode_list");

    function postcode_list()
    {
        session_start();

        $postType = 'delivery-options';
        // $notFoundURL = get_permalink( 23725 ); // test
        $notFoundURL = get_permalink( 24495 ); // live

        if( $_REQUEST['action']=='postcode_list' )
        {
        	WC()->session->set('PostcodeDeliveryID', '');
            $postCode = $_REQUEST['postcode'];

            if( $postCode!='' )
            {
                $fountPost = post_exists($postCode, '', '', $postType);
                // $_SESSION['DeliveryPostID'] = $fountPost;
                // $_SESSION['DeliveryPinCode'] = $fountPost ? $postCode : '';

                if( empty($fountPost) )
                {
	                global $wpdb;
				    $table_name = $wpdb->prefix . 'suburbs_no_deliver';

				    $wpdb->insert($table_name,
							    	array(
							        	'postcode' => $postCode,
							    	)
							    );
				}

                // echo $fountPost ? site_url($postType.'/'.$postCode) : $notFoundURL;
                echo !empty($fountPost) ? get_permalink( $fountPost ) : $notFoundURL;
            }
            else
            {
                // $_SESSION['DeliveryPostID'] = '';
                // $_SESSION['DeliveryPinCode'] = '';
                echo $notFoundURL;
            }
        }
        
        wp_die();
    }
}


if( !function_exists('delivery_options') )
{
    function delivery_options()
    {
    	// if( is_admin() ) { return; }
    	// if( current_user_can('administrator') ) { return; }

        ob_start();
?>
        <div class="row custom_delivery_options">
            <div class="col-md-12 mb-10 custom_delivery_option">
                <div class="custom-radio d-none">
                    <input type="radio" id="delivery_option_default" name="delivery_option" value="Default Delivery" checked>
                    <label class="d-inline" for="delivery_option_default">Default Delivery Option (Free delivery orders above $<?php echo MINIMUM_ORDER_CHARGE; ?>)</label>
                </div>
                <div class="container">
                	<?php
	                // Returns Array of Term ID's for "my_taxonomy".
	                // $DeliveryPostID = WC()->session->get('PostcodeDeliveryID')!='' ? WC()->session->get('PostcodeDeliveryID') : get_the_ID();
	                $DeliveryPostID = empty(get_the_ID()) ? WC()->session->get('PostcodeDeliveryID') : get_the_ID();
					$deliveryZoneIDs = wp_get_post_terms( $DeliveryPostID, 'delivery_zone', array('fields'=>'ids') );

                    $currentDayTime = 'Please Select';
                    $deaultOptions = get_field('default_delivery_option', 'delivery_zone_'.$deliveryZoneIDs[0]);
                    // echo "<pre>"; print_r($deaultOptions); echo "</pre>";

                    if( empty($deaultOptions) )
                    {
                    	$currentDayTime = "Time not Available";
                    }

                    date_default_timezone_set('Australia/Melbourne');
                    ?>

                    <div class="row align-items-center order-1">
	                    <div class="col-2 text-center">
	                        <img src="<?php echo WOODMART_IMAGES.'Default-Delivery.png'; ?>">
	                    </div>
	                    <div class="col-4">
	                        <label>Nearest Available Delivery:</label>
	                    </div>

	                    <div class="col-6 day_time_title d-flex justify-content-around align-items-center">
	                    	<img src="<?php echo WOODMART_IMAGES.'clock-icon.png'; ?>">
	                    	<span class="current_day_time"><?php echo $currentDayTime; ?></span>
	                    </div>
	                </div>

                    <?php
                    if( !empty($deaultOptions) )
                    {
                    	// echo '<div class="container">
                		echo '<div class="row week_day_times order-2">';

		                        $weekDay = array();
		                        $weekDayTimes = array();
		                        foreach( $deaultOptions as $Key=>$deaultOption )
		                        {
		                            $weekDayName = strtolower($deaultOption['week_day_name']);

		                            if( array_key_exists('week_day_name', $deaultOption) )
		                            {
		                                array_push($weekDay, $weekDayName);
		                            }

		                            if( array_key_exists('week_day_times', $deaultOption) )
		                            {
		                                $weekDayTimes[$weekDayName] = $deaultOption['week_day_times'];
		                            }
		                        }

		                        if( $weekDay )
		                        {
		                            $currentDate = date("d");
		                            $currentDay = strtolower(date("l")); // get week day
		                            $nextDay = strtolower(date('l', strtotime('+1 Day')));
		                            $newWeekDays = array();
		                            $count = count($weekDay);

		                            foreach($weekDay as $Key=>$Day)
		                            {
		                                if( strpos($Day, $currentDay) !== false) // get the key where input day matched
		                                {
		                                    // add next all records to the new array till the end of the original array
		                                    for( $i=$Key; $i<$count; $i++ )
		                                    {
		                                        if( isset($weekDay[$i]) )
		                                        {
		                                            $newWeekDays[] = $weekDay[$i];
		                                        }
		                                    }

		                                     // add previous one before the matched key to the new array
		                                    for( $j=0; $j<=$Key-1; $j++)
		                                    {
		                                        if( isset($weekDay[$j]) )
		                                        {
		                                            $newWeekDays[] = $weekDay[$j];
		                                        }
		                                    }
		                                }
		                            }

		                            if( $newWeekDays )
		                            {
		                                echo '<div class="col-12">';
		                                    $activeTabCount = 1;
		                                    echo '<nav class="row nav_tabs">
		                                    	<div class="nav nav-tabs nav-fill" id="nav-tab" role="tablist">';
			                                        foreach( $newWeekDays as $newWeekDay )
			                                        {
			                                            $activeTab = $activeTabCount==1 ? ' active' : '';

			                                            $TabDate = '';
			                                            if( $currentDay==$newWeekDay )
			                                            {
			                                                $newWeekDayName = 'Today';
			                                            }
			                                            else if( $nextDay==$newWeekDay )
			                                            {
			                                                $newWeekDayName = 'Tomorrow';
			                                            }
			                                            else
			                                            {
			                                                $newWeekDayName = substr($newWeekDay, 0, 3);
			                                                $TabDate = date('d M', strtotime($newWeekDay));
			                                            }

			                                            if( $weekDayTimes )
		                                                {
		                                                	foreach( $weekDayTimes as $Key=>$weekDayTime )
		                                                    {
		                                                        if( $Key==$newWeekDay )
		                                                        {
		                                                        	if( $weekDayTime[0]['time'] )
		                                                            {
		                                                            	$disabled = '';
		                                                            }
		                                                            else
		                                                            {
		                                                            	$disabled = ' not-active';
		                                                            }
		                                                        }
		                                                    }
		                                                }

			                                            // '.$activeTab.'
			                                            echo '<a class="nav-item nav-link'.$activeTab.$disabled.'" data-id="nav-'.$newWeekDay.'" data-toggle="tab" href="javascript:;" role="tab" aria-controls="nav-'.$newWeekDay.'" aria-selected="false" '.$disabled.'>'.ucfirst($newWeekDayName).'<br>'.$TabDate.'</a>';

			                                            $activeTabCount++;
			                                        }
		                                    echo '</div>
		                                    </nav>';

		                                    $activeTabContentCount = 1;
		                                    echo '<div class="tab-content py-3 px-3 px-sm-0" id="nav-tabContent">';
		                                        echo '<p class="slot_title">Preferred Delivery Slot:';
		                                        foreach( $newWeekDays as $newWeekDay )
		                                        {
		                                            $activeTabContent = $activeTabContentCount==1 ? ' show active' : '';

		                                            // '.$activeTabContent.'
		                                            echo '<div class="tab-panel fade'.$activeTabContent.'" id="nav-'.$newWeekDay.'" role="tabpanel" aria-labelledby="nav-'.$newWeekDay.'-tab">';

		                                                // echo "<pre>"; print_r($newWeekDays); echo "</pre>";
		                                                if( $weekDayTimes )
		                                                {
		                                                	$currentHour = date('H:i', strtotime('+181 minutes'));

		                                                    foreach( $weekDayTimes as $Key=>$weekDayTime )
		                                                    {
		                                                        if( $Key==$newWeekDay )
		                                                        {
		                                                        	$lineThrough = '';
																	$textDanger = '';
		                                                            $disabled = '';
                                                                    $slotFull = '';
		                                                            // echo "<pre>".$Key; print_r($weekDayTime); echo "</pre>";
		                                                            echo '<div class="row">';
		                                                                $selectDeliveryTime = ' select_delivery_time';

		                                                                if( $weekDayTime[0]['time'] )
		                                                            	{
					                                                        foreach( $weekDayTime as $DayTime )
					                                                        {
					                                                            $DayTimes = explode('-', $DayTime['time']);
				                                                            	$DayTimeHours = date('H:i', strtotime($DayTimes[0]));
				                                                            	
				                                                            	/*if( $currentHour<=$DayTimeHours )
				                                                            	{
				                                                            		$currentDayTime = ucfirst($Key).' '.$DayTime['time'];
				                                                            	} */

			                                                                    /*$dataDate = '';
			                                                                    if( $currentDay==$Key )
			                                                                    {
			                                                                        $dataDay = 'Today';
			                                                                    }
			                                                                    else if( $nextDay==$Key )
			                                                                    {
			                                                                        $dataDay = 'Tomorrow';
			                                                                    }
			                                                                    else
			                                                                    {*/
			                                                                        // $dataDay = substr($newWeekDay, 0, 3);
			                                                                        // $dataDate = date('d M', strtotime($newWeekDay));

			                                                                        $dataDay = $newWeekDay;
			                                                                        $dataDate = date('Y-m-d', strtotime($newWeekDay));
			                                                                    // }

			                                                                    $radioDeliverySlotTimeID = 'delivery_slot_time_'.$dataDay.'_'.bin2hex(base64_encode($DayTime['time']));

			                                                                    $orderCountTimeSlot = order_count_delivery_options_time_slot($dataDate, $newWeekDay, $DayTime['time']);
			                                                                    
			                                                                    if( $currentDay==$Key )
				                                                            	{
				                                                            		$lineThrough = $currentHour>=$DayTimeHours ? ' text-decoration-line-through' : '';
																					$textDanger = $currentHour>=$DayTimeHours ? ' text-danger-bold' : '';
			                                                                        $disabled = $currentHour>=$DayTimeHours ? 'disabled' : '';

			                                                                        $selectDeliveryTime = $currentHour>=$DayTimeHours ? '' : $selectDeliveryTime;

                                                                                    $slotFull = $currentHour>=$DayTimeHours ? ' Slot Full' : '';
				                                                            	}
			                                                                    
			                                                                    if( !empty($DayTime['time_maximum_order']) && $orderCountTimeSlot >= $DayTime['time_maximum_order'] )
			                                                                    {
			                                                                    	$lineThrough = ' text-decoration-line-through';
																					$textDanger = ' text-danger-bold';
			                                                                        $disabled = 'disabled';

                                                                                    $slotFull = ' Slot Full';
			                                                                    }
			                                                                    /*else
			                                                                    {
			                                                                    	$lineThrough = '';
		                                                            				$disabled = '';
			                                                                    }*/

			                                                                    /*if( !empty($DayTime['slot_decativate']) && in_array("Decativate", $DayTime['slot_decativate']) )
			                                                                    {
			                                                                    	$lineThrough = ' text-decoration-line-through';
			                                                                        $disabled = 'disabled';
			                                                                    }*/

			                                                                    if( !empty($DayTime['date_time_slot_decativate']) && strtotime(date('Y-m-d H:i:s'))<strtotime($DayTime['date_time_slot_decativate']) )
			                                                                    {
			                                                                    	$lineThrough = ' text-decoration-line-through';
																					$textDanger = ' text-danger-bold';
			                                                                        $disabled = 'disabled';

                                                                                    $slotFull = ' Slot Full';
			                                                                    }

					                                                            echo '<div class="col-md-12 delivery_time_slot'.$selectDeliveryTime.$textDanger.'">';
			                                                                        echo '<div class="form-check">
			                                                                            <input class="form-check-input js_select_delivery_time" type="radio" name="delivery_slot_time" id="'.$radioDeliverySlotTimeID.'" value="'.$DayTime['time'].'" data-day="'.ucfirst($dataDay).'" data-date="'.$dataDate.'" '.$disabled.'>
			                                                                            <label class="d-inline" for="'.$radioDeliverySlotTimeID.'">
			                                                                                <span class="'.$lineThrough.'">'.$DayTime['time'].'</span>'.$slotFull.'
			                                                                            </label>
			                                                                        </div>';
			                                                                    echo '</div>';

			                                                                    $lineThrough = '';
																				$textDanger = '';
		                                                            			$disabled = '';
																				$slotFull = '';
					                                                        }
					                                                    }
					                                                    else
					                                                    {
					                                                    	echo '<div class="col-md-12 delivery_time_slot">
					                                                    			<div class="form-check text-danger">Delivery slot not available</div>
					                                                    		</div>';
					                                                    }
				                                                    echo '</div>';
		                                                        }
		                                                    }
		                                                }
		                                            echo '</div>';

		                                            $activeTabContentCount++;
		                                        }
		                                    echo '</div>';
		                                echo '</div>';
		                            } // end if $newWeekDays
		                        } // end if $weekDay

		                echo '</div>'; 
                		// </div>';
                    } // if end $deaultOptions
                    ?>
	            </div>
            </div>

            <?php /* <div class="col-md-12 custom_delivery_option">
                <div class="custom-radio">
                    <input type="radio" id="delivery_option_express_new" name="delivery_option_new" value="Express Delivery" disabled>
                    <label class="d-inline" for="delivery_option_express">Delivering within Melbourne and over 468 surrounding suburbs. For interstate orders, please click on the button below.</label>
                    <a href="<?php echo home_url('/deliverying-indian-products-across-australia'); ?>" class="btn d-block custom_btn_zone_delivery">Out Of Zone Delivery</a>
                    <!-- <p>Additional fixed fee of $15 applies for all Express Deliveries. Same day delivery for orders placed before 4Pm. Next morning Delivery for orders placed after 4Pm.</p> -->
                </div>
                <div class="container">

                	<?php
                	$expressCurrentDayTime = 'Please Select';
                	if( have_rows('express_delivery', 'delivery_zone_'.$deliveryZoneIDs[0]) ):
					    while( have_rows('express_delivery', 'delivery_zone_'.$deliveryZoneIDs[0]) ) : the_row();

					        $today = get_sub_field('today');
					        $tomorrow = get_sub_field('tomorrow');
					        
	                    	if( $today || $tomorrow )
	                    	{
	                    		// $expressCurrentDayTime = 'Today: '.$today;

	                    		echo '<div class="container">';
		                    		echo '<div class="row express_day_times text-left">';
		                    			echo '<div class="col-12 express_day_time">';
	                                        echo '<div class="form-check">
	                                            <input class="form-check-input js_express_day_time" type="radio" name="express_delivery_slot_time" id="express_delivery_slot_time_today" value="Today: '.$today.'">
	                                            <label class="d-inline" for="express_delivery_slot_time_today">
	                                                Today: '.$today.'
	                                            </label>
	                                        </div>';
	                                    echo '</div>';

	                                    echo '<div class="col-12 express_day_time">';
	                                        echo '<div class="form-check">
	                                            <input class="form-check-input js_express_day_time" type="radio" name="express_delivery_slot_time" id="express_delivery_slot_time_tomorrow" value="Tomorrow: '.$tomorrow.'">
	                                            <label class="d-inline" for="express_delivery_slot_time_tomorrow">
	                                                Tomorrow: '.$tomorrow.'
	                                            </label>
	                                        </div>';
	                                    echo '</div>';
				                    echo '</div>';
				                echo '</div>';
	                    	}
	                    	else
	                    	{
	                    		$expressCurrentDayTime = "Time not Available";
	                    	}

					    endwhile;
					else :
					    $expressCurrentDayTime = "Time not Available";
					endif;
					?>

	                <div class="row align-items-center">
	                    <div class="col-2 text-center">
	                        <img src="<?php echo WOODMART_IMAGES.'Express-Delivery.png'; ?>">
	                    </div>
	                    <div class="col-4">
	                        <label>Nearest Available Delivery:</label>
	                    </div>

	                    <?php
	                    echo '<div class="col-6 day_time_title d-flex justify-content-around align-items-center">';
		                    echo '<img src="'.WOODMART_IMAGES.'clock-icon.png">';
	                    	echo '<span class="express_current_day_time">'.$expressCurrentDayTime.'</span>';
						echo '</div>';
	                    ?>
	                </div>
	            </div>
            </div> */ ?>
        </div>

        <style type="text/css">
        	.single-delivery-options .custom_delivery_options {
				margin-top: -30px;
			}
        	.custom_delivery_options .custom_delivery_option {
        		background-color: #fff;
				padding-top: 10px;
				padding-bottom: 10px;
        	}
        	.custom_delivery_options .nav_tabs
            {
            	padding: 0 10px;
            }
            .custom_delivery_options nav > .nav.nav-tabs
            {
                background-color: #fff;
                border-radius: 0;
                text-align: center;
                overflow-x: auto;
			    overflow-y:hidden;
			    flex-wrap: nowrap;
			    display: flex;
            }
            .custom_delivery_options nav > div a.nav-item.nav-link
            {
                color:#fff;
                background:#008000;
                white-space: nowrap;
                padding: 5px 10px;
				border: 1px solid #ccc;
            }
            .custom_delivery_options nav > div a.nav-item.nav-link.not-active
            {
            	pointer-events: none;
            	background-color: #656f65d9;
            }
            .custom_delivery_options nav > div a.nav-item.nav-link:hover,
            .custom_delivery_options nav > div a.nav-item.nav-link:focus,
            .custom_delivery_options nav > div a.nav-item.nav-link.active
            {
                background-color: #000066;
                color: #fff;
            }
            .custom_delivery_options .tab-content
            {
              padding: 5px 0;
            }
            .custom_delivery_options .tab-content .slot_title
            {
            	color: #222;
            	margin-bottom: 0px;
            }
            .custom_delivery_options .tab-content .tab-panel
            {
                display: none;
            }
            .custom_delivery_options .tab-content .tab-panel.show
            {
                display: block;
            }
            .custom_delivery_options .week_day_times {
            	/*display: none;*/
            	position: relative;
				top: 0;
				z-index: 9;
                background-color: #ccc;
                padding: 5px 0 0;
                border: 1px solid #006;
                right: 0;
                width: 100%;
                float: right;
            }
            .single-delivery-options .custom_delivery_options .week_day_times {
            	top: 0;
            }
            .custom_delivery_options .delivery_time_slot {
                cursor: pointer;
            }
			.custom_delivery_options .text-decoration-line-through
            {
			  text-decoration: line-through;
			  color: red;
			}
            .custom_delivery_options .text-danger-bold
            {
			  color: red;
			}
			.custom_delivery_options .text-danger-bold label {
				color: red;
				font-weight: bold;
			}
			.custom_delivery_options .express_day_times {
            	display: none;
            	position: absolute;
				z-index: 9;
				background-color: #ccc;
				padding: 10px;
                border: 1px solid #006;
                margin: 0 auto;
                top: 145px;
                right: 15px;
            }
            .custom_delivery_options .express_day_time {
            	background-color: #fff;
				padding: 10px;
				margin-bottom: 5px;
            }
            .custom_delivery_options .delivery_time_slot .form-check {
                background-color: #fff;
                padding: 5px 10px;
                margin-bottom: 5px;
            }
            .custom_delivery_options .day_time_title {
                border: 1px solid #666;
                text-align: center;
                padding: 5px 0;
                cursor: pointer;
            }
            .custom_delivery_options .day_time_title img {
            	max-height: 20px;
            }
            .custom_delivery_options .day_time_title .current_day_time:after, .custom_delivery_options .day_time_title .express_current_day_time:after {
			    margin-left: 10px;
			    content: "\f150";
			    font-family: woodmart-font;
			}
			.single-delivery-options .woodmart-sticky-sidebar-opener {
				display: none !important;
			}

			@media only screen and (max-width: 768px)
			{
				.single-delivery-options .custom_delivery_options .custom_delivery_option .container {
					display: flex;
					flex-wrap: wrap;
				}
				.custom_delivery_options .week_day_times {
					width: 100%;
				}
				.single-delivery-options .custom_delivery_options .week_day_times {
					position: relative;
					margin: 0 auto;
					right: 0;
					top: 0;
				}

				.single-delivery-options .custom_delivery_options .custom_delivery_option .order-1 {
					order: 1;
				}
				.single-delivery-options .custom_delivery_options .custom_delivery_option .order-2 {
					order: 2;
				}
			}

			@media only screen and (max-width: 580px)
			{
				.custom_delivery_options .week_day_times {
					top: 70px;
				}
				.custom_delivery_options .week_day_times .col-12 {
					padding: 0 3px;
				}
				.custom_delivery_options .nav_tabs {
				    padding: 0 15px;
				}
				.custom_delivery_options nav > .nav.nav-tabs {
					padding: 4px;
				}
				.custom_delivery_options .express_day_times {
					width: 95%;
					top: 200px;
				}
			}
        </style>

        <script type="text/javascript">
        jQuery(document).ready(function()
        {
            jQuery('.nav-link').on('click', function()
            {
                jQuery('.nav-link').removeClass('active');
                jQuery(this).addClass('active');

                jQuery('#nav-tabContent .tab-panel').removeClass('show active');
                jQuery('#nav-tabContent #'+jQuery(this).data('id')).addClass('show active');
            });

            jQuery('input[name=delivery_option]').on('click', function()
            {
            	jQuery("#delivery_type").val('');
            	jQuery("#delivery_available_date").val('');
            	jQuery("#delivery_available_day").val('');
		        jQuery("#delivery_available_time").val('');
            });

            jQuery('.current_day_time').on('click', function()
            {
            	var timeStatus = jQuery('.express_current_day_time');
            	if( timeStatus.text()!='Time not Available' )
            	{
            		timeStatus.text('Please Select');
            	}
            	jQuery('.express_day_times').hide();
            	jQuery('.week_day_times').toggle();
            });

            jQuery('.js_select_delivery_time').on('click', function()
            {
                jQuery("#delivery_option_default").prop("checked", true);
                jQuery("#delivery_option_default").attr('checked', 'checked');

                var deliveryOption = jQuery("input[name='delivery_option']:checked").val();
                jQuery('#delivery_type').val(deliveryOption);

                var date = jQuery(this).data('date');
                var day = jQuery(this).data('day');
                var time = jQuery(this).val();

                jQuery('.current_day_time').text(day+' '+time);
                jQuery('#delivery_available_date').val(date);
                jQuery('#delivery_available_day').val(day);
                jQuery('#delivery_available_time').val(time);

                // jQuery('.week_day_times').hide();
            });

            jQuery('.express_current_day_time').on('click', function()
            {
            	var timeStatus = jQuery('.current_day_time');
            	if( timeStatus.text()!='Time not Available' )
            	{
            		timeStatus.text('Please Select');
            	}
            	jQuery('.week_day_times').hide();
                jQuery('.express_day_times').toggle();
            });

            jQuery('.js_express_day_time').on('click', function()
            {
                jQuery("#delivery_option_express").prop("checked", true);
                jQuery("#delivery_option_express").attr('checked', 'checked');

                var deliveryOption = jQuery("input[name='delivery_option']:checked").val();
                jQuery('#delivery_type').val(deliveryOption);

                // var expressTime = jQuery(this).text();
                var expressTime = jQuery(this).val();
                jQuery('.express_current_day_time').text(expressTime);
                jQuery('#delivery_available_time').val(expressTime);

                // jQuery('.express_day_times').hide();
            });

            jQuery('body').on('click', function()
            {
			    jQuery('.week_day_times').hide();
			    jQuery('.express_day_times').hide();
			});
			jQuery('.current_day_time, .week_day_times, .express_current_day_time, .express_day_times').on('click', function(e)
			{
			    e.stopPropagation();
			});
        });
        </script>
<?php
        return ob_get_clean();
    }
    add_shortcode('deliveryoptions', 'delivery_options');
}
// Postcode code end

// delivery options order count time slots start
if( !function_exists('order_count_delivery_options_time_slot') )
{
    function order_count_delivery_options_time_slot($date='', $day='', $time='')
    {
    	$orderArgs = array(
                'post_type' => 'shop_order',
                'posts_per_page' => -1,
                'post_status' => 'any',
                'meta_query' => array(
                    'relation'  =>   'AND',
                    array(
                        'key' => 'delivery_available_date',
                        'value' => $date,
                        'compare' => '=',
                        'type' => 'DATE'
                    ),
                    array(
                        'key' => 'delivery_available_time',
                        'value' => $time,
                        'compare' => '=',
                        //'type' => 'DATE'
                    ),
                )
            );

		$the_order_count = new WP_Query( $orderArgs );
    	return $the_order_count->post_count;
    }
}
// delivery options order count time slots end


// checkout page add delivery options start
if( !function_exists('custom_delivery_options_checkout_field') )
{
    /**
     * Add the field to the checkout
     */
    // add_action( 'woocommerce_after_checkout_billing_form', 'custom_delivery_options_checkout_field' ); // NOT USE
    add_action( 'woocommerce_checkout_before_order_review_heading', 'custom_delivery_options_checkout_field', 10 );

    function custom_delivery_options_checkout_field( $checkout )
    {
        echo '<div id="my_custom_checkout_field" class="mb-30">
                <h2>' . __('Delivery Options') . ' <abbr class="required" title="required">*</abbr> <a href="'.esc_url(get_pincode_delivery_page_url()).'" class="btn btnChangePostcode">Change Postcode</a></h2>
            ';

        	woocommerce_form_field( 'delivery_type', array(
                        'type'          => 'hidden',
                        'class'         => array('form-row-wide'),
                        'label'         => __(''),
                        'placeholder'   => __('Enter Delivery Type'),
                        'required' 		=> true,
                    ),
                    // $checkout->get_value( 'delivery_type' )
                );

        	woocommerce_form_field( 'delivery_available_date', array(
                        'type'          => 'hidden',
                        'class'         => array('form-row-wide'),
                        'label'         => __(''),
                        'placeholder'   => __('Enter Delivery Date'),
                        'required' 		=> true,
                    ),
                    // $checkout->get_value( 'delivery_available_date' )
                );

        	woocommerce_form_field( 'delivery_available_day', array(
                        'type'          => 'hidden',
                        'class'         => array('form-row-wide'),
                        'label'         => __(''),
                        'placeholder'   => __('Enter Delivery Day'),
                        'required' 		=> true,
                    ),
                    // $checkout->get_value( 'delivery_available_day' )
                );

            woocommerce_form_field( 'delivery_available_time', array(
                        'type'          => 'hidden',
                        'class'         => array('form-row-wide'),
                        'label'         => __(''),
                        'placeholder'   => __('Enter Delivery Time'),
                        'required' 		=> true,
                    ),
                    // $checkout->get_value( 'delivery_available_time' )
                );

            echo '<span id="ajax_delivery_options">'.do_shortcode('[deliveryoptions]').'</span>';
        echo '</div>';
    }
}

if( !function_exists('custom_delivery_options_checkout_field_process') )
{
    add_action('woocommerce_checkout_process', 'custom_delivery_options_checkout_field_process');
    function custom_delivery_options_checkout_field_process()
    {
    	if( !empty(WC()->session->get('PostcodeDeliveryID')) )
    	{
	        // Check if set, if its not set add an error.
	        if( !$_POST['delivery_type'] )
	        {
	            wc_add_notice( __( 'Please select delivery type' ), 'error' );
	        }

	        if( !$_POST['delivery_available_date'] || !$_POST['delivery_available_time'] )
	        {
	            wc_add_notice( __( 'Please select delivery time' ), 'error' );
	        }
	    }
    }
}

if( !function_exists('customise_checkout_field_update_order_meta') )
{
    /**
     * Update value of field
     */
    add_action('woocommerce_checkout_update_order_meta', 'customise_checkout_field_update_order_meta');
    function customise_checkout_field_update_order_meta($order_id)
    {
    	if( !empty($_POST['delivery_type']) )
        {
            update_post_meta($order_id, 'delivery_type', sanitize_text_field($_POST['delivery_type']));
        }

        if( !empty($_POST['delivery_available_date']) )
        {
            update_post_meta($order_id, 'delivery_available_date', sanitize_text_field($_POST['delivery_available_date']));
        }

        if( !empty($_POST['delivery_available_day']) )
        {
            update_post_meta($order_id, 'delivery_available_day', sanitize_text_field($_POST['delivery_available_day']));
        }

        if( !empty($_POST['delivery_available_time']) )
        {
            update_post_meta($order_id, 'delivery_available_time', sanitize_text_field($_POST['delivery_available_time']));
        }
    }
}

if( !function_exists('edit_woocommerce_checkout_page') )
{
    add_action( 'woocommerce_admin_order_data_after_shipping_address', 'edit_woocommerce_checkout_page', 10, 1 );
    function edit_woocommerce_checkout_page($order)
    {
        $delivery_type = get_post_meta($order->get_id(), 'delivery_type', true );
        $delivery_time = deliveryOptionsDateDayTime($order->get_id());

        echo '<div class="address">';
        	if( !empty($delivery_type) )
        	{
        		echo '<p><strong>'.__('Delivery Type').':</strong> ' .$delivery_type. '</p>';
        	}

            echo '<p><strong>'.__('Delivery Time').':</strong> ' . $delivery_time . '</p>';
        echo '</div>';
    }
}


if( !function_exists('add_delivery_options_to_order_received_page') )
{
    // add_filter( 'woocommerce_order_details_before_order_table', 'add_delivery_options_to_order_received_page', 10 , 1 ); // NOT USE
    add_action( 'woocommerce_thankyou', 'add_delivery_options_to_order_received_page', 0 );
    add_action( 'woocommerce_view_order', 'add_delivery_options_to_order_received_page', 0 );

    function add_delivery_options_to_order_received_page ( $order_id )
    {
    	$deliveryType = get_post_meta( $order_id, 'delivery_type', true );
        if( !empty($deliveryType) )
        {
            echo '<p><strong>' . __( 'Delivery Type' ) . ':</strong> ' . $deliveryType;
        }

        $delivery_time = deliveryOptionsDateDayTime($order_id);
        if( !empty($delivery_time) )
        {
            echo '<p><strong>' . __( 'Delivery Time' ) . ':</strong> ' .$delivery_time;
        }
    }
}

	// Express Delivery Charge calculate start
	if( !function_exists( 'express_delivery_charge' ) )
	{
	    add_action( 'woocommerce_cart_calculate_fees', 'express_delivery_charge', 10, 1 );
	    function express_delivery_charge( $cart_object )
	    {
	        if( is_admin() && !defined( 'DOING_AJAX' ) ) return;

	        $deliveryOption = WC()->session->get('delivery_option');
	    	
	    	if( !WC()->cart->is_empty() ) :
	    		if( $deliveryOption=='expressdelivery' )
	    		{
	            	$cart_object->add_fee( "Express Delivery Charge", EXPRESS_DELIVERY_CHARGE, true );
	    		}
	    		/*else if( $deliveryOption=='postcodenotfounddelivery' )
	    		{
	    			// $cart_object->add_fee( "Fixed delivery charge", PINCODE_NOT_FIXED_DELIVERY_CHARGE, true );
	    			WC()->cart->add_fee(__('Fixed delivery charge', 'woodmart_child'), PINCODE_NOT_FIXED_DELIVERY_CHARGE);
	    		}*/
	        endif;
	    }
	}

	if( !function_exists( 'express_delivery_checkout_radio_choice_refresh' ) )
	{
		add_action( 'wp_footer', 'express_delivery_checkout_radio_choice_refresh' );
		function express_delivery_checkout_radio_choice_refresh()
		{
			if ( !is_checkout() ) return;
		?>
		    <script type="text/javascript">
		    jQuery(document).ready(function($)
		    {
		        jQuery('form.checkout').on('change', 'input[name=delivery_option], input[name=express_delivery_slot_time], input[name=delivery_slot_time]', function(e)
		        {
		            e.preventDefault();
		            // var deliveryOption = jQuery(this).val();
		            var deliveryOption = jQuery("input[name='delivery_option']:checked").val();
		            
		            jQuery.ajax({
		                type: 'POST',
		                url: wc_checkout_params.ajax_url,
		                data: {
		                    'action': 'express_delivery_checkout_get_ajax_data',
		                    'delivery_option': deliveryOption,
		                },
		                success: function(result)
		                {
		                    jQuery('body').trigger('update_checkout');
		                }
		            });
		        });


		        function postcodeAjax($postcode)
		        {
		        	jQuery("#delivery_type").val('');
		        	jQuery("#delivery_available_date").val('');
		        	jQuery("#delivery_available_day").val('');
		        	jQuery("#delivery_available_time").val('');
		        	
			        jQuery.ajax({
		                type: 'POST',
		                url: wc_checkout_params.ajax_url,
		                data: {
		                    'action': 'postcode_delivery_get_ajax_data',
		                    'shipping_postcode': $postcode,
		                },
		                success: function(result)
		                {
		                    // jQuery('body').trigger('ajax_delivery_options');
		                    jQuery('#ajax_delivery_options').html(JSON.parse(result));
		                    // jQuery('body').trigger('update_checkout');
		                    $(document.body).trigger('update_checkout');
		                }
		            });
			    }

			    if( jQuery('#shipping_postcode').val() > 0 )
			    {
        			postcodeAjax(jQuery('#shipping_postcode').val());
        			jQuery('body').trigger('update_checkout');
			    }

		        /*jQuery('form.checkout').on('blur', '#shipping_postcode', function(e)
		        // $('#shipping_postcode').on('blur', function ()
		        {
		            e.preventDefault();
		            postcodeAjax(jQuery(this).val());
		            jQuery('body').trigger('update_checkout');
		        });*/
		    });
		    </script>
	<?php
		}
	}

	if( !function_exists( 'express_delivery_checkout_radio_choice_set_session' ) )
	{
		add_action( 'wp_ajax_express_delivery_checkout_get_ajax_data', 'express_delivery_checkout_radio_choice_set_session' );
		add_action( 'wp_ajax_nopriv_express_delivery_checkout_get_ajax_data', 'express_delivery_checkout_radio_choice_set_session' );

		function express_delivery_checkout_radio_choice_set_session()
		{
			/*if( empty(WC()->session->get('PostcodeDeliveryID')) )
			{
				$delivery_option = sanitize_key( 'postcodenotfounddelivery' );
		        WC()->session->set('delivery_option', $delivery_option );
			}
		    else if( isset($_POST['delivery_option']) )
		    {*/
		        // $delivery_option = sanitize_key( $_POST['delivery_option'] );
		        // WC()->session->set('delivery_option', $delivery_option );
		        // echo json_encode( $delivery_option );
		    // }

		    $deliveryOption = WC()->session->get('delivery_option');
	    
		    if( !WC()->cart->is_empty() && $deliveryOption!='defaultdelivery' )
		    {
	            WC()->session->set('delivery_option', 'Postcode Not Found Delivery' );
	        }

		    wp_die();
		}
	}

	if( !function_exists( 'postcode_delivery_get_ajax_data_set_session' ) )
	{
		add_action( 'wp_ajax_postcode_delivery_get_ajax_data', 'postcode_delivery_get_ajax_data_set_session' );
		add_action( 'wp_ajax_nopriv_postcode_delivery_get_ajax_data', 'postcode_delivery_get_ajax_data_set_session' );

		function postcode_delivery_get_ajax_data_set_session()
		{
			if( $_REQUEST['action']=='postcode_delivery_get_ajax_data' )
	        {
	        	if( !WC()->cart->is_empty() ) :
	                $fountPostID = post_exists($_REQUEST['shipping_postcode'], '', '', 'delivery-options');

	            	WC()->session->set('PostcodeDeliveryID', $fountPostID );
	            	WC()->session->set('PostcodeDelivery', $_REQUEST['shipping_postcode'] );
	            	WC()->session->set('delivery_option', 'defaultdelivery' );

	                if( empty($fountPostID) )
	                {
	                	WC()->session->set('delivery_option', 'Postcode Not Found Delivery' );
					}

					echo json_encode( do_shortcode('[deliveryoptions]'));
	            endif;
	        }
		    /*if( isset($_POST['shipping_postcode']) )
		    {
		        $fountPostID = post_exists($_POST['shipping_postcode'], '', '', 'delivery-options');
		        WC()->session->set('PostcodeDeliveryID', $fountPostID );

		        // if( empty(WC()->session->get('PostcodeDeliveryID')) )
				// {
					// $delivery_option = sanitize_key( 'postcodenotfounddelivery' );
			        // WC()->session->set('delivery_option', $delivery_option );
				// }
			    // else
			    // {
			        $delivery_option = sanitize_key( 'defaultdelivery' );
			        WC()->session->set('delivery_option', $delivery_option );
			    // }

		        echo json_encode( do_shortcode('[deliveryoptions]'));
		    }*/

		    wp_die();
		}
	}
	// Express Delivery Charge calculate end

// checkout page add delivery options start

// Register new status
if( !function_exists( 'woodmart_child_register_picked_order_status' ) )
{
	function woodmart_child_register_picked_order_status()
	{
	    register_post_status( 'wc-picked', array(
	        'label'                     => 'Picked & Packed',
	        'public'                    => true,
	        'exclude_from_search'       => false,
	        'show_in_admin_all_list'    => true,
	        'show_in_admin_status_list' => true,
	        'label_count'               => _n_noop( 'Picked & Packed (%s)', 'Picked & Packed (%s)' )
	    ) );
	}
	add_action('init', 'woodmart_child_register_picked_order_status');
}

// Add to list of WC Order statuses
if( !function_exists( 'woodmart_child_add_picked_to_order_statuses' ) )
{
	function woodmart_child_add_picked_to_order_statuses($order_statuses)
	{
	    $new_order_statuses = array();
	 
	    // add new order status after processing
	    foreach( $order_statuses as $key=>$status )
	    {
	        $new_order_statuses[$key] = $status;
	 
	        if( 'wc-processing' === $key )
	        {
	            $new_order_statuses['wc-picked'] = 'Picked & Packed';
	        }
	    }
	 
	    return $new_order_statuses;
	}
	add_filter('wc_order_statuses', 'woodmart_child_add_picked_to_order_statuses');
}

if( !function_exists( 'woodmart_child_styling_admin' ) )
{
	function woodmart_child_styling_admin()
	{
	    global $pagenow, $post;

	    if( $pagenow != 'edit.php') return; // Exit
	    if( get_post_type($post->ID) != 'shop_order' ) return; // Exit

	    // HERE we set your custom status
	    $order_status = 'Picked';
?>
	    <style>
	        .order-status.status-<?php echo sanitize_title( $order_status ); ?>
	        {
	            background: #2e4453c7;
	            color: #eee;
	        }
	    </style>
<?php
	}
	add_action('admin_head', 'woodmart_child_styling_admin');
}


if( !function_exists( 'woodmart_child_rename_order_status_type' ) )
{
	function woodmart_child_rename_order_status_type($order_statuses)
	{
		$key_order = array(
						"",
						"wc-pending",
						"wc-processing",
						"wc-picked",
						"wc-on-hold",
						"wc-completed",
						"wc-cancelled",
						"wc-refunded",
						"wc-failed"
					);

		return reorder_associate_arr($key_order, $order_statuses);
	}
	add_filter( 'woocommerce_register_shop_order_post_statuses', 'woodmart_child_rename_order_status_type');

	function reorder_associate_arr($key_order, $order_statuses)
	{
	    $new_arr = array();

	    $keys = array_keys($order_statuses);
	    $index = 0;
	    foreach( $key_order as $key )
	    {
	        if( trim($key) == "" )
	        {
		        $new_arr[$keys[$index]] = $order_statuses[$keys[$index]];
	        }
	        else
	        {
		        $new_arr[$key] = $order_statuses[$key];
	        }

		    $index++;
	    }

	    return $new_arr;
	}
}

if( !function_exists( 'woodmart_child_filter_plugin_updates' ) )
{
	function woodmart_child_filter_plugin_updates( $value ) {
	    unset( $value->response['woocommerce-pdf-invoices-packing-slips/woocommerce-pdf-invoices-packingslips.php'] );
	    return $value;
	}
	add_filter( 'site_transient_update_plugins', 'woodmart_child_filter_plugin_updates' );
}

// customer order feedback start
if( !function_exists('ajax_feedback') )
{
    add_action( 'wp_footer', 'ajax_feedback' );
    function ajax_feedback()
    {
    ?>
        <script type="text/javascript" >
            jQuery(document).ready(function($)
            {
            	jQuery('input.rating__input').click( function () {
			        var starRating = jQuery('input[name="star_rating"]:checked').val();

			        jQuery('label.rating__label').removeClass('rating_orange');
			        var i;
			        for( i=1; i<=starRating; i++ ) {
						jQuery('label.star_rating_'+i).addClass('rating_orange');
					}
			    });

                jQuery("#btnFeedback").click( function(e)
                {
                    e.preventDefault();

                    var feedbackRating = jQuery('input[name="star_rating"]:checked').val();
                    var feedbackMessage = jQuery('#feedback_message').val();
                    var orderID = jQuery('#customer_order_id').val();

                    jQuery('#rating_error').removeClass('d-block').addClass('d-none');
                    if( feedbackRating==undefined ) {
                    	jQuery('#rating_error').addClass('d-block').removeClass('d-none');
                    	return false;
                    }

                    jQuery('#feedback_message_error').addClass('d-none');
                    if( feedbackMessage=='' ) {
                    	jQuery('#feedback_message_error').removeClass('d-none');
                    	return false;
                    }

                    var data = {
                        'action': 'customer_order_feedback',
                        'feedbackRating': feedbackRating,
                        'feedbackMessage': feedbackMessage,
                        'orderID': orderID
                    };

                    jQuery.ajax({
                        url: '<?php echo admin_url( 'admin-ajax.php' ) ?>',
                        type: 'POST',
                        data: data,
                        success: function(response)
                        {
                           jQuery('#thank_message').removeClass('d-none');
                        }
                    });
                });
            });
        </script> 
    <?php
    }
}

if( !function_exists('customer_order_feedback') )
{
    add_action("wp_ajax_customer_order_feedback" , "customer_order_feedback");
    add_action("wp_ajax_nopriv_customer_order_feedback" , "customer_order_feedback");

    function customer_order_feedback()
    {
        if( $_REQUEST['action']=='customer_order_feedback' )
        {
        	$order_id = $_REQUEST['orderID'];

        	update_post_meta($order_id, 'feedback_rating', sanitize_text_field($_POST['feedbackRating']));

	        update_post_meta($order_id, 'feedback_message', sanitize_text_field($_POST['feedbackMessage']));
        }
        
        wp_die();
    }
}
// customer order feedback end


// Yoast SEO Title Optimization
function limit_title_yoast( $str ) {
 return substr($str, 0, 62); 
}
add_filter( 'wpseo_title', 'limit_title_yoast' );

// Pickling Slip Customisation

add_filter('wf_pklist_alter_order_grouping_row_text','wf_pklist_alter_order_grouping_row_text_fn',10, 3);
function wf_pklist_alter_order_grouping_row_text_fn($order_info_arr, $order, $template_type)
{
	if($template_type=='picklist')
	{
		$order_info_arr[]=__('Customer').': '.$order->billing_first_name.' '.$order->billing_last_name;
	}
	return $order_info_arr;
}
add_filter('wf_pklist_alter_order_grouping_row_text','wf_pklist_add_customer_note_picklist',10, 3);
function wf_pklist_add_customer_note_picklist($order_info_arr, $order, $template_type)
{
if($template_type=='picklist')
{
$customer_note=$order->get_customer_note();
if(!empty($customer_note))
{
$order_info_arr['customer_note']=__('Customer Note : ').$customer_note;
}
}
return $order_info_arr;
}

//For slow add to cart issue
add_filter( 'woocommerce_add_to_cart_fragments', 'woodmart_cart_data', 30 );

function woodmart_cart_data( $array ) {

	ob_start();

	woodmart_cart_count();

	$count = ob_get_clean();



	ob_start();

	woodmart_cart_subtotal();

	$subtotal = ob_get_clean();



	$array['span.wd-cart-number_wd']   = $count;

	$array['span.wd-cart-subtotal_wd'] = $subtotal;



	return $array;

}