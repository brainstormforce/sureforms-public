<?php
/**
 * Medical History Form pattern.
 *
 * @link       https://sureforms.com
 * @since      X.X.X
 * @package    SureForms/Templates/Forms
 * @author     Sureforms <https://sureforms.com/>
 */

return [
	'title'      => __( 'Medical History Form', 'sureforms' ),
	'categories' => [ 'sureforms_form' ],
	'postTypes'  => SUREFORMS_FORMS_POST_TYPE,
	'content'    => '<!-- wp:column -->
    <div class="wp-block-column"><!-- wp:heading {"textAlign":"center"} -->
    <h2 class="wp-block-heading has-text-align-center">Medical History Form</h2>
    <!-- /wp:heading -->
    
    <!-- wp:separator {"backgroundColor":"vivid-red","className":"is-style-wide"} -->
    <hr class="wp-block-separator has-text-color has-vivid-red-color has-alpha-channel-opacity has-vivid-red-background-color has-background is-style-wide"/>
    <!-- /wp:separator -->
    
    <!-- wp:columns -->
    <div class="wp-block-columns"><!-- wp:column -->
    <div class="wp-block-column"><!-- wp:sureforms/input {"required":true,"label":"First Name"} /--></div>
    <!-- /wp:column -->
    
    <!-- wp:column -->
    <div class="wp-block-column"><!-- wp:sureforms/input {"required":true,"label":"Last Name"} /--></div>
    <!-- /wp:column --></div>
    <!-- /wp:columns -->
    
    <!-- wp:columns -->
    <div class="wp-block-columns"><!-- wp:column -->
    <div class="wp-block-column"><!-- wp:sureforms/number {"required":true,"label":"Age"} /--></div>
    <!-- /wp:column -->
    
    <!-- wp:column -->
    <div class="wp-block-column"><!-- wp:sureforms/multi-choice {"required":true,"singleSelection":true,"options":["Male","Female"],"label":"Gender"} /--></div>
    <!-- /wp:column --></div>
    <!-- /wp:columns -->
    
    <!-- wp:columns -->
    <div class="wp-block-columns"><!-- wp:column -->
    <div class="wp-block-column"><!-- wp:sureforms/phone {"required":true,"placeholder":"","help":""} /--></div>
    <!-- /wp:column -->
    
    <!-- wp:column -->
    <div class="wp-block-column"><!-- wp:sureforms/email {"required":true} /--></div>
    <!-- /wp:column --></div>
    <!-- /wp:columns -->
    
    <!-- wp:sureforms/multi-choice {"required":true,"options":["Asthama","Cardiac disease","Hypertension","Epilepsy","Cancer","Diabetes","Psychiatric disorder","Other"],"label":"Check the conditions that apply to you or your immediate family members."} /-->
    
    <!-- wp:sureforms/multi-choice {"required":true,"options":["Chest pain","Respiratory","Hematological","Cardiac disease","Cardiovascular","Lymphatic","Gastrointestinal","Genitourinary","Musculoskeletal","Neurological","Psychiatric","Weight gain","Weight loss"],"label":"Check the symptoms you are currently experiencing."} /-->
    
    <!-- wp:sureforms/multi-choice {"required":true,"singleSelection":true,"options":["Yes","No"],"label":"Are you currently using medication?"} /-->
    
    <!-- wp:sureforms/textarea {"label":"Please list them, if any?"} /-->
    
    <!-- wp:sureforms/multi-choice {"required":true,"singleSelection":true,"options":["Yes","No","Maybe"],"label":"Do you have any medication allergies?"} /-->
    
    <!-- wp:sureforms/textarea {"label":"Please list them, if any?"} /-->
    
    <!-- wp:sureforms/multi-choice {"required":true,"singleSelection":true,"options":["Yes","No"],"label":"Do you use any kind of tobacco products?"} /-->
    
    <!-- wp:sureforms/textarea {"label":"Please list them, if any?"} /-->
    
    <!-- wp:sureforms/multi-choice {"label":""} /-->
    
    <!-- wp:sureforms/multi-choice {"required":true,"singleSelection":true,"options":["Yes","No"],"label":"Do you use any kind of illegal drugs or have used them?"} /-->
    
    <!-- wp:sureforms/multi-choice {"singleSelection":true,"options":["Daily","Weekly","Monthly","Occasionaly","Never"],"label":"How often do you consume alcohol?"} /-->
    
    <!-- wp:sureforms/submit {"text":"Submit!","full":true} /--></div>
    <!-- /wp:column -->',
];
