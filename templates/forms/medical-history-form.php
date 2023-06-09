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
    <div class="wp-block-column"><!-- wp:heading {"textAlign":"left"} -->
    <h2 class="wp-block-heading has-text-align-left">Medical History Form</h2>
    <!-- /wp:heading -->
    
    <!-- wp:columns -->
    <div class="wp-block-columns"><!-- wp:column -->
    <div class="wp-block-column"><!-- wp:sureforms/input {"id":"blockf9024331b0b5445598a48a67e9d4aab2","required":true,"label":"First Name"} /--></div>
    <!-- /wp:column -->
    
    <!-- wp:column -->
    <div class="wp-block-column"><!-- wp:sureforms/input {"id":"block5168db6bf9de416ebceb54bb816bf966","required":true,"label":"Last Name"} /--></div>
    <!-- /wp:column --></div>
    <!-- /wp:columns -->
    
    <!-- wp:columns -->
    <div class="wp-block-columns"><!-- wp:column -->
    <div class="wp-block-column"><!-- wp:sureforms/number {"id":"block205790d174a04ff889a2d2a64eda28fb","required":true,"label":"Age"} /--></div>
    <!-- /wp:column -->
    
    <!-- wp:column -->
    <div class="wp-block-column"><!-- wp:sureforms/multi-choice {"id":"block99e7aca036bd44f1a84c0181ed64b153","required":true,"singleSelection":true,"options":["Male","Female"],"label":"Gender","style":"buttons"} /--></div>
    <!-- /wp:column --></div>
    <!-- /wp:columns -->
    
    <!-- wp:columns -->
    <div class="wp-block-columns"><!-- wp:column -->
    <div class="wp-block-column"><!-- wp:sureforms/phone {"id":"block1452e7c9304445c886a299316b2788a9","required":true,"placeholder":"","help":""} /--></div>
    <!-- /wp:column -->
    
    <!-- wp:column -->
    <div class="wp-block-column"><!-- wp:sureforms/email {"id":"block380aa473a4424422ab8316aeba012cd9","required":true} /--></div>
    <!-- /wp:column --></div>
    <!-- /wp:columns -->
    
    <!-- wp:sureforms/multi-choice {"id":"blockd5d781c618f64e938ebc0d8b7fa3c93f","required":true,"options":["Asthama","Cardiac disease","Hypertension","Epilepsy","Cancer","Diabetes","Psychiatric disorder","Other"],"label":"Check the conditions that apply to you or your immediate family members.","style":"buttons"} /-->
    
    <!-- wp:sureforms/multi-choice {"id":"block3171c2033b574c5b8113f5a221508cf5","required":true,"options":["Chest pain","Respiratory","Hematological","Cardiac disease","Cardiovascular","Lymphatic","Gastrointestinal","Genitourinary","Musculoskeletal","Neurological","Psychiatric","Weight gain","Weight loss"],"label":"Check the symptoms you are currently experiencing.","style":"buttons"} /-->
    
    <!-- wp:sureforms/multi-choice {"id":"block74743d853dec4b5282f40bf00e9b0324","required":true,"singleSelection":true,"options":["Yes","No"],"label":"Are you currently using medication?","style":"buttons"} /-->
    
    <!-- wp:sureforms/textarea {"id":"blockc84e091986e3483ea0cbeec5f2e96f23","label":"Please list them, if any?"} /-->
    
    <!-- wp:sureforms/multi-choice {"id":"blockf67c3ced289f4a1fa583be7eb30faa63","required":true,"singleSelection":true,"options":["Yes","No","Maybe"],"label":"Do you have any medication allergies?","style":"buttons"} /-->
    
    <!-- wp:sureforms/textarea {"id":"block17cd8a9c2c174ebfba36a0c4393e7f42","label":"Please list them, if any?"} /-->
    
    <!-- wp:sureforms/multi-choice {"id":"blockb8a64f0b5ce940f7b9bc7bce9c6921ef","required":true,"singleSelection":true,"options":["Yes","No"],"label":"Do you use any kind of tobacco products?","style":"buttons"} /-->
    
    <!-- wp:sureforms/textarea {"id":"block32c2c253cf5a4170bd188e0ae4b95254","label":"Please list them, if any?"} /-->
    
    <!-- wp:sureforms/multi-choice {"id":"block67c045ac9e804527bdb47f824f9b76aa","label":""} /-->
    
    <!-- wp:sureforms/multi-choice {"id":"blocke96cbec0d7e940f5a3e4a7badf193bda","required":true,"singleSelection":true,"options":["Yes","No"],"label":"Do you use any kind of illegal drugs or have used them?","style":"buttons"} /-->
    
    <!-- wp:sureforms/multi-choice {"id":"block0ddf173698694acf96c34518c3c969e3","singleSelection":true,"options":["Daily","Weekly","Monthly","Occasionaly","Never"],"label":"How often do you consume alcohol?","style":"buttons"} /-->
    
    <!-- wp:sureforms/submit {"text":"Submit!","full":true} /--></div>
    <!-- /wp:column -->',
];
