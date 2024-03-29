<?php
/*
 * Template Name: Home Template
 * 
 */
get_header();
$GeneralThemeObject = new GeneralTheme();
if(is_user_logged_in()){
	$userDetails = $GeneralThemeObject->user_details();

				if($userDetails->data['role'] == 'customer'){
					$redirectTo = CUSTOMER_ACCOUNT_PAGE;
				} else if($userDetails->data['role'] == 'counselor'){
					$redirectTo = COUNSELLOR_ACCOUNT_PAGE;
				}
} else{
	$redirectTo = USER_REGISTRATION_PAGE;
}
				
?>

<section class="content_sec">
    <div class="container">
    	<div class="row">
            <div class="col-sm-5 home_con proccess_con col-xs-push-1">
                <h2>Our Process</h2>
                <ul>
                    <li><span>1</span> Pick an Assessment</li>
                    <li><span>2</span> Create an Account</li>
                    <li><span>3</span> Do an Online Interview</li>
                </ul>
            </div>
                
            <div class="col-sm-5 home_con assessments_con col-xs-push-1">
                <h2>Our Assessments</h2>
                <ul>
                    <li>General Substance Abuse</li>
                    <li>DUI Alcohol/DUID Drug</li>
                    <li>Employer required Substance Abuse</li>
                    <li>DOT Substance Abuse</li>
                    <li>Child Custody/ Divorce Substance Abuse</li>
                </ul>
            </div>
            
            
            <div class="home_button_sec">
                <div class="col-sm-12">
                    <div class="get_button">
                        <?php if(is_user_logged_in()){ ?>
                        <a href="<?php echo $redirectTo; ?>" class="get_button">Get Started</a>
                        <?php } else {
                		?>
                        <a href="#userRegistrationModal" data-toggle="modal" class="get_button">Get Started</a>
                        <?php
              			} ?>
                    </div>
                </div>
            </div>
            
            
        </div>
    </div>
    
    
</section>
<?php
get_footer();

