<?php 
ob_start();
error_reporting(E_ALL);
ini_set("display_errors", 1); 
require_once("../include/session.php");
if(!isset($_POST['submit'])) {
require_once("../include/functions/storeSettingsInitialization.php");
require_once("../include/functions.php"); 
require_once("../include/functions/StoreHours.php");
require_once("../include/checkout/checkout_controller.php");
require_once("../include/checkout/checkout_post_values.php");
include_once("../include/checkout/payment_gateways");
require_once("../include/db_connection.php"); // DB Configuration
require_once("../include/cart_functions.php");
require_once('../FirePHP/fb.php');
//include_once("../include/cart_functions/cart_functions.php");
}
require_once('../include/api/rpower/rpower.php');
include("../include/facts_inspirational.php"); // Fun facts to display on the checkout page


if(!isset($_POST['submit'])) {
require_once("../include/header.php");  // The Header

$validator = new validation($operationalSettings);//Location : checkout_controller.php
$post_values = new PostValues(); //Location : checkout_post_values.php
$order_fullfillment = new OrderFullfillment();//Location: checkout_controller.php
$create_html = new html_generator(); // Location: checkout_controller.php

if($_SERVER['HTTP_HOST'] == "localhost") {
$mysqli = new mysqli("localhost", "root", "" , "szge");
} else {
   $mysqli = new mysqli("localhost", "eldan88_admin", "347}blue}163}mexican1", "eldan88_szge" );
}

if($validator->store_name == "demo"){
  $google_maps_validation = false;
} else {
  $google_maps_validation = false; 
}
}
if(isset($_POST['submit'])) {
	
	$rpower = array();
	$name = $_POST['name'];
	$phone = $_POST['phone'];
	$addressOne = $_POST['address'];
	$addressTwo = $_POST['suite'];
	$email = $_POST['email'];
	$cc    = $_POST['credit_card_number'];
	$expire = $_POST['card_expiry_month'];
	
	$checkOutTotal = $_SESSION['checkout_total'];
	$grandTotal    = $_SESSION['grand_total'];
	$storeName     = $_SESSION['store_name'];
	$storeId       = $_SESSION['store_id'];	
	$cartItems     = $_SESSION['my_cart'];
	
	$x = new displayCartItems();
	$items = $x->outputMenuItems($cartItems);
	var_dump($items);
	exit();
	$rpower['name'] = $name;
	$rpower['phone'] = $phone;
	$rpower['address-one'] = $addressOne;
	$rpower['address-two'] = $addressTwo;
	$rpower['email'] = $email;
	$rpower['cc'] = $cc;
	$rpower['expire'] = $expire;
	
	var_dump($_SESSION);
	exit();
	
	
	//header('Location: http://www.simplemenu.com/menus/test.php');
	
	$rpowerObj = new rpower();
	
	$xmlData = $rpowerObj->buildXML($rpower);
	exit();

	## RPOWER END
	

/********************************Start of form initialization************************************************/
//the post_values method is instantiated from checkout_post_values It contains assigns all the $_POST values 
//from the form below to the properties in the class, and new $_POST method needs to be added in this class. 
$post_values->form_post_values();

/********************************Start of form validation************************************************/
$validator->validate_fields('Name', (prep_mysql_values($post_values->name)), array('required'=>null, 'length'=>array('max'=>40)));
$validator->validate_fields('Phone', (prep_mysql_values($post_values->phone)), array('required'=>null, 'length'=>array('max'=>12)));

if($post_values->delivery_method_post == "Delivery") {

$validator->validate_fields('Address', (prep_mysql_values($post_values->address)), array('required'=>null, 'validate_delivery'=>$post_values->address, 'length'=>array('max'=>50)));
$validator->validate_fields('Suite/Apt #',(prep_mysql_values($post_values->suite)), array('required'=>null, 'length'=>array('max'=>12)));
$validator->validate_fields('Cross Streets', (prep_mysql_values($post_values->cross_streets)), array('required'=>null, 'length'=>array('max'=>40)));

}
$validator->validate_fields('Email',  (prep_mysql_values($post_values->email)), array('required'=>null,'email'=>null, 'length'=>array('max'=>50)));



// Validate the the credit card fields only when the payment option credit card is selected. Otherwise if user selects cash there is no need to validate the credit card fields below. Therefore we put an if statement to only validate when credit card is selected

if(isset($_POST['payment_method']) && $_POST['payment_method'] == "Credit Card"){

//We want to do run a preg_replace function on credit cards so that user will not enter bull shit characters that will piss off restaurant owners. Numbers only. 
$validator->validate_fields('Credit Card Number',  
  (prep_mysql_values(preg_replace("/[^0-9]/", "",$post_values->credit_card_number))), array('required'=>null, 'credit_card_length'=>array('min'=>15,'max'=>16)));

//Disables American Express credit cards if store does not accpet them check to see if the first digit is contains a 3 if it does it will call the method disable_amex which will then pass an error in the array
if(defined("STORE_ACCEPT_AMERICAN_EXPRESS") && STORE_ACCEPT_AMERICAN_EXPRESS == "no"  && substr($post_values->credit_card_number, 0, 1) == "3") {
  $validator->disable_amex();
}



$validator->validate_fields('Expiration Month',  (prep_mysql_values($post_values->exp_month)), array('card_exp_date_dropdown'=>null));

$validator->validate_fields('Expiration Year', (prep_mysql_values($post_values->exp_year)), array('card_exp_date_dropdown'=>null));
}// End of if(isset($_POST['payment_method']) && $_POST['payment_method'] == "credit_card"){

if($validator->is_delivery_option_selected()){
$validator->validate_minumum_delivery();

}

$validator->session_expired();

/**************************END of form validation***************************************/

      
      
 // Start processing the query one there get errors array is not in the array
if(!$validator->getErrors()) {
    
   


 $query = "INSERT INTO checkout_page(
 store_id , confirmation_number , is_confirmed , sent_to_reminder , set_reminder , reminder_duration , is_registered , full_name , address , suite_app , cross_streets , email , phone , order_time , delivery_method , payment_method , hashed_cardnumber , exp_month , exp_year, menu_items_ordered , cart_total , checkout_date , alert_date

 ) VALUES (
 {$post_values->store_id}, {$post_values->confirmation_number}, 0 , {$post_values->sent_to_reminder} ,  {$post_values->set_reminder} , '{$post_values->reminder_duration}' , {$post_values->registered_user}, '{$post_values->name}' , '{$post_values->address}' , '{$post_values->suite}' , '{$post_values->cross_streets}' , '{$post_values->email}'  , '{$post_values->phone}' ,'{$post_values->order_time}' , '{$post_values->delivery_method_post}', '{$post_values->payment_method}'  ,'{$post_values->credit_card_number}' ,  {$post_values->exp_month} , {$post_values->exp_year} , '{$post_values->my_cart}' , {$post_values->total_amount} , '{$post_values->current_datetime}', 
   '{$post_values->current_datetime}') ;";
   
  //If client has Authorize.net defined then we need to instantiate an object and charge the customers card via authorize.net
   if (defined("API_LOGIN") && API_LOGIN != "" && $post_values->payment_method == "Credit Card") {

     $authorizenet = new AuthorizeNet($post_values->credit_card_number, $post_values->exp_month, $post_values->exp_year, $post_values->total_amount);
     // We want to charge the card after there are no errors on the form above because if there are some form errors and we call the charge_card() method then it will charge the card regardless of the errors. 
      $authorizenet->charge_card();
      
      
 // Charge cart will assign a declined message to the $message_declined property if the credit card is declin 
if(!empty(AuthorizeNet::$message_declined)) { 
    
  // If there is a declined transaction the send it to the method declined cardand it will output below
    
$validator->card_declined(AuthorizeNet::$message_declined);
//If the card is declined set the complete checkout to false. It is always set true by default
$validator->complete_checkout = false; 
}// end of if(!empty(AuthorizeNet::$message_declined)
  }// end of if (defined("API_LOGIN") && API_LOGIN != "" && $post_values->payment_method == "Credit Card")

  // If it is false then don't complete the checkout. 
        if($validator->complete_checkout){
    

 $mysqli->query($query) ; 
 //echo $mysqli->error; 
 $checkout_id = $mysqli->insert_id;


if ($mysqli->affected_rows == 1 && $checkout_id != 0) {

                    
    // if the checked out item is saved into database then update recent items too. 
    // only for logged in user

      //is_user_logged in is located in the checkout_post_values, and get constructed automatically then get set to the is_user_logged_in property 
    if ($post_values->is_user_logged_in) {
        // Now Time to save updated items to database
        $recent_items = getUsers($post_values->user_id, "recent_items");
        $recent_items = ($recent_items == "") ? $_SESSION['my_cart'] : $_SESSION['my_cart'] . "[NEWITEM]" . $recent_items;

        $my_account = array(
           "user_id" => $_SESSION['logged']['id'],
            "store_id" => $_SESSION['store_id'],
            "updated_items" => $recent_items
        );
        // Save recent items to registered_users
       edit_recent_items($my_account);

        /*
          Edit Credit Card Info if user wants to.
         */

$card_edit = "hashed_cardnumber='{$post_values->credit_card_number}', exp_month=  {$post_values->exp_month}, exp_year={$post_values->exp_year} ";

$query_update_user = "UPDATE registered_users SET 
full_name = '{$post_values->name}', address='{$post_values->address}', suite_app = '{$post_values->suite}', cross_streets = '{$post_values->cross_streets}', phone = '{$post_values->phone}' WHERE id = {$post_values->user_id} ";


        $mysqli->query($query_update_user);
}//if (user_logged_in())
   

}// if (mysql_affected_rows() == 1 && $checkout_id 


//include '../include/checkout/queries.php';
// This does all the updating
//$queries = new CheckoutDBQueries(); 


/*********************
Code for faxing orders. 
**********************/

// Don't send orders via fax when testing on localhost
//if($_SERVER['HTTP_HOST'] != 'localhost') {
  // Fax is defined include the API
  if(defined("STORE_FAX_CLIENT") && STORE_FAX_CLIENT=="metrofax" || STORE_FAX_CLIENT=="interfax" ){ 
    //if(!demo_store()) {
     require_once("../include/api/metrofax/send_fax_new.php");
    //}else {
    //require_once("../include/api/metrofax/send_fax_test.php");

    //}
 // } // end of if(defined("STORE_FAX_CLIENT") && STORE_FAX_CLIENT=="metrofax" || 

}// end of if($_SERVER['HTTP_HOST'] != 'localhost') {




// Send out an email notification along with the browsers information for debugging purposes. 
if(STORE_CONFIRMATION_METHOD=="order management" && $_SERVER['HTTP_HOST'] != 'localhost'){
 include("order_information.php");
$browser_info ="<br><br>Browser: ".$_SERVER['HTTP_USER_AGENT']."<br><br>MySQL: ".$query."<br><br>";
$subject = "(".STORE_NAME.") - Order Number#".$checkout_id;
send_notification($subject,$order_information.$browser_info,"simplemenuorders@gmail.com");
}


//If the STORE_TEST_ORDER constant is defined... doesn't matter what the value of the constant is then set it to false. We don't need any confirmation calls. 
if(defined("STORE_TEST_ORDER")) {
  unset($_SESSION["confirmation_call"]);
} else {
  $_SESSION["confirmation_call"] = true;
}


if(!demo_store()){
goto_page("order_confirmation.php?id=" . urlencode($checkout_id));
}
            }// end of if($validator complete checkout

} //if empty($validator->getErrors)) {
         

    
}// end of $_POST['submit'])
?>


<!--***************************
Start Checkout Form

***************************-->


<div id="outer-container"> 

  <div id="contents"> <!-- contents starts -->

     <div id="checkout" class="store_text_color"> <!-- checkout starts -->



       <?php


// Display the errors


          // Display coupon if it is set at back end
          $display_image = (STORE_COUPON_SIMP10) ? "coupon.png" : "logo.png";
          ?>
          <center>
              <div style="background-color:#666; color:#FFF; font-weight:bold; font-size:15px"> <?php echo $facts[0]; ?><p>
              </div>
              <img src="assets/<?= $validator->store_name ?>/<?= $display_image ?>" border="0"></center>

<?php

/************************************************
Error Reporting
All error reporting code for this page goes here
*************************************************/

echo "<noscript><p>Some of the features may not work correctly becasue Javascript has been disabled. Please enable Javascript in your browser for better use of this page</p></noscript>";
$validationErrors = $validator->getErrors();
if(count($validationErrors))
{
    echo "<font color='red'>" .implode("<br/>",$validationErrors) ."</font>";  
     }
 //Authorize.net Functionality goes here
  


     ?>

        

<form action="#" method="POST">

  

  <?php 




/********************* STEP 1 ***********************
Order details drop-down starts here
Future Time Selection and Pick Up or Delivery option
User selects when they want their order to be placed. 
*******************************************************/
echo $create_html->display_checkout_heading("Order Fullfillment", "step1").PHP_EOL; 
echo "<div class='form_wrapper'>";
//Selection to choose a future time default is TODAY, ASAP. The id/name is "future_time_dropdown"
echo  "Order Time: &nbsp " . $order_fullfillment->future_time_dropdown() . "<br><br>";
// Delivery or Pick up drpw down menu. The id/name - "delivery_method"

if($validator->is_the_store_delivering) {
echo "This Order is for: &nbsp " . $create_html->delivery_method_dropdown() . "<br><br>";
} else {
  //If the store is not doing any deliveries we want to hard code the $_POST values to pick up becuase the Delivery/Pick up option has not been selected. 
  echo "<input type='hidden' name='delivery_method' value='Pick Up'>";
  echo "We're sorry. We are currently not doing deliveries. <br> 
  This order is for Pick Up only." . "<br><br>"."<input type='hidden' name='Pick Up'>";

}
echo "</div>";
 



/*********************STEP 2 ****************************/

$title =  validation::$is_delivery ?  "Delivery Information" : "Pick Up Information";
echo $create_html->display_checkout_heading($title, "step2").PHP_EOL;



/*Input Labels output starts here*/


echo "<div class='form_wrapper'>".PHP_EOL;


// Google Autocomplete
//include('GoogleAutocomplete.php');


//Name
echo "<label name='name' for='name'>Name: </label>" . //Name
"<input type='text' id='name' name='name' class='". html_generator::$input_class . "' value='{$post_values->name}' >".PHP_EOL;// Value





//Phone
echo "<label for='phone'>Phone: </label>" .  //Name
"<input type='tel' id='phone' name='phone' class='". html_generator::$input_class ."' value='{$post_values->phone}'>".PHP_EOL; // Value


if( $validator->is_the_store_delivering) { 

// Not all the restaurants have google maps validation enabled so we needed to create a logic to see if the maps validation is set to true or not. 
if($google_maps_validation) {
echo "<label id='glbladdress' name='gaddress' for='address'>Address: </label>".
"<input type='text' id='address' name='address' class='". html_generator::$input_class . "'  placeholder=''
  onFocus='geolocate'>
  ";



echo "<h5 id='gaddress_status' style='display:none;'></h5>";
  
} else {

//Address
echo "<label id='address' name='address' for='address'>Address: </label>" . // Name. 
"<input type='text' id='address' name='address' class='". html_generator::$input_class . "'  value='{$post_values->address}'>".PHP_EOL;

}

//Suite
echo "<label id='suite' for='suite'>Suite/Apt#: </label>" . //Name
"<input type='text' id='suite' name='suite' class='". html_generator::$input_class . "'  value='{$post_values->suite}'>".PHP_EOL;

// $create_html->input_field("suite", "{$post_values->suite}") . "</div>"; //Value
//Cross Streets
echo "<label id='cross_streets' for='cross_streets'>Cross Streets: </label>" . //Name
"<input type='text' id='cross_streets' name='cross_streets' class='". html_generator::$input_class . "'  value='{$post_values->cross_streets}'>".PHP_EOL; //Value


}// End of if( $validator->is_the_store_delivering)

echo "<label for='email'>Email: </label>" . 
"<input type='email'  id='email' name='email' class='". html_generator::$input_class."'  value='{$post_values->email}'>".PHP_EOL;



echo "</div>"; // End of "<div class='form_wrapper'>";


  




 /*************STEP 3 ************************
 This is where cart gets  loaded with Jquery AJAX  -->
 This file is located in js/menus-js/checkout.js
 *******************************************/
   
   echo  "<div id='checkout_cart'>";
    echo  "<center>Loading Order Summary... </center>";
   echo  "</div>";






/*********** STEP 4 ****************/

  
echo $create_html->display_checkout_heading("Payment Information", "step4").PHP_EOL;

if (!defined("STORE_PAYMENT_METHOD")) {

// The constant "STORE_PAYMENT_METHOD" is a misleading constant, and needs to be changed to "CASH_ONLY". I will refer to the "STORE_PAYMENT_METHOD" to "CASH_ONLY". If the CASH only is not defined then show the credit card fields. User has an option to select cash or credit since the store accepts both

//If the the CONSTANT "CASH_ONLY" is defined then don't give users no option to use credit cards only cash. (By default it allows the user to select cash or credit) This condition below overrides that feature and informs the user that the store only accepts cash  


/*VERY IMPORTANT --> PLEASE DON'T CHANGE THE VERBAGE of "Credit Card or Cash" The reason why is becasue there is a lot of logic based on the option the user selects. I.E if Cash option selected don't validate credit card fields. or only save the posted values if Credit Card option has been selected. Etc Etc. Code will break if the wording will change. */

echo "<div class='form_wrapper'>";

echo  "<label for='payment_method'> Payment Method: &nbsp &nbsp &nbsp </label>". 
"  <select name='payment_method' class='". html_generator::$input_class . "' id='payment_method'>

<option name='credit_card'>Credit Card</option> 

<option name='cash'>Cash</option>

</select><br>";
 



echo  "<label id='credit_card_number' for='credit_card_number'>Card Number</label>". //Name
"<input type='text' id='credit_card_number' name='credit_card_number' class='". html_generator::$input_class . "'  value='{$post_values->credit_card_number}'>".PHP_EOL; //Value

//@$post_values->exp_month and users->exp_year. It is POST value it gets from the PostValues class. Or the exp month and year from the registered user from checkout_post_values

echo  "<label id='expiration_date'> Expiration Date: </label>" . //Name
 $create_html->expiration_date($post_values->exp_month, $post_values->exp_year) ; //Value







//echo "<p>";
/* if($_SESSION['store_name'] = 'snacksoho' || $_SESSION['store_name'] == 'demo') {
echo "<label id='cvv'> Cvv Code </label>".
"<input type='cvv' id='cvv' name='cvv' class='". html_generator::$input_class."' value='{$post_values->email}'>".PHP_EOL;*/
//}


  } // end of (!defined("STORE_PAYMENT_METHOD"))
 else {
echo   "<div class='form_input'>Cash Only</div>";
}

echo "</div>";





  if (display_validation_form()) {
    $button_type = "button";
    $button_id = "checkout_button";
} else {
    $button_type = "submit";
    $button_id = "checkout_submit";
}


?>

<div id="checkout_td_display">
<input class="water_brush water_brush_checkout" title="submit order" type="<?php echo $button_type; ?>" value="Submit Order" id="<?php echo $button_id; ?>"  name="submit" />
<div id="submitted" style="display:none"><img src="http://simplemenu.com/menus/includes/images/loading.gif" /> Your order is now being sent to the kitchen!</div>
</div>


    </form>
               </div> <!-- end of <div id="checkout" class="store_text_color"> -->
         </div> <!-- contents ends -->
</div> <!-- Most outer container Ends -->

<!--****************************End CheckoutForm*****************************************-->





          
<!-- this code below will hide the credit card input fields when the cash option get selected so it won't get parsed through the SQL -->      

            <script type="text/javascript" src="../js/jquery-masking.js"></script>

    <?php include_once("../js/menus-js/checkout_js.php"); ?>
    </body>
</html>


<?php
ob_end_flush();

