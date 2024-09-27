<?php
    use SRFM\Inc\Database\Tables\Entries;
    use SRFM\Inc\Helper;
    if ( ! defined( 'ABSPATH' ) ) {
        exit; // Exit if accessed directly.
    }
    if ( $_GET['entry_id'] ) {
        $entry_id = intval( $_GET[ 'entry_id' ] );
        $entry = Entries::get( $entry_id );

        if ( $entry ) {
            $status = $entry['status'];
            $submitted_on = date( 'Y/m/d \a\t g:i a', strtotime( $entry['created_at'] ) );
            $form_name = ! empty( get_the_title( $entry['form_id'] ) ) ? get_the_title( $entry['form_id'] ) : 'SureForms Form #' . intval( $entry["form_id"] );
            $meta_data = $entry['user_data'];
            $excluded_fields = [ 'srfm-honeypot-field', 'g-recaptcha-response', 'srfm-sender-email-field' ];
            ?>
                <div class="wrap">
                    <h1 class="wp-heading-inline"><?php esc_html_e( 'View Entry', 'sureforms' ); ?></h1>
                    <form name="post" action="post.php" method="post" id="post"> <!-- check for nonce, referrer, etc. --> 
                        <div id="poststuff">
                            <div id="post-body" class="metabox-holder columns-2">
                                <div id="post-body-content">
                                    <div id="titlediv">
                                        <div id="titlewrap">
                                            <label class="screen-reader-text" id="title-prompt-text" for="title"><?php esc_html_e( 'Add title', 'sureforms' ); ?></label>
                                            <input type="text" name="post_title" size="30" value="Entry #<?php echo esc_attr( $entry_id ); ?>" id="title" spellcheck="true" autocomplete="off" readonly>
                                        </div>
                                        <div class="inside">
                                        </div>
                                        <!-- <input type="hidden" id="samplepermalinknonce" name="samplepermalinknonce" value="315728b3dd"> -->
                                    </div><!-- /titlediv -->
                                </div><!-- /post-body-content -->

                                <div id="postbox-container-1" class="postbox-container">
                                    <div id="side-sortables" class="meta-box-sortables ui-sortable">
                                        <div id="submitdiv" class="postbox ">
                                            <div class="postbox-header">
                                                <!-- Removing class "hndle ui-sortable-handle" from the below h2 tag to prevent the draggable cursor. --> 
                                                <h2>Publish</h2>
                                                <div class="handle-actions hide-if-no-js"><button type="button" class="handle-order-higher" aria-disabled="true" aria-describedby="submitdiv-handle-order-higher-description"><span class="screen-reader-text">Move up</span><span class="order-higher-indicator" aria-hidden="true"></span></button><span class="hidden" id="submitdiv-handle-order-higher-description">Move Publish box up</span><button type="button" class="handle-order-lower" aria-disabled="false" aria-describedby="submitdiv-handle-order-lower-description"><span class="screen-reader-text">Move down</span><span class="order-lower-indicator" aria-hidden="true"></span></button><span class="hidden" id="submitdiv-handle-order-lower-description">Move Publish box down</span><button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Publish</span><span class="toggle-indicator" aria-hidden="true"></span></button></div>
                                            </div>
                                            <div class="inside">
                                                <div class="submitbox" id="submitpost">

                                                    <div id="minor-publishing">

                                                        <div style="display:none;">
                                                            <p class="submit"><input type="submit" name="save" id="save" class="button" value="Save"></p>
                                                        </div>

                                                        <div id="minor-publishing-actions">
                                                            <div id="save-action">
                                                            </div>

                                                            <div class="clear"></div>
                                                        </div>

                                                        <div id="misc-publishing-actions">
                                                            <div class="misc-pub-section misc-pub-post-status">
                                                                Status: <span id="post-status-display">
                                                                    Published </span>

                                                                <a href="#post_status" class="edit-post-status hide-if-no-js" role="button"><span aria-hidden="true">Edit</span> <span class="screen-reader-text">
                                                                        Edit status </span></a>

                                                                <div id="post-status-select" class="hide-if-js">
                                                                    <input type="hidden" name="hidden_post_status" id="hidden_post_status" value="publish">
                                                                    <label for="post_status" class="screen-reader-text">
                                                                        Set status </label>
                                                                    <select name="post_status" id="post_status">
                                                                        <option selected="selected" value="publish">Published</option>
                                                                        <option value="pending">Pending Review</option>
                                                                        <option value="draft">Draft</option>
                                                                    </select>
                                                                    <a href="#post_status" class="save-post-status hide-if-no-js button">OK</a>
                                                                    <a href="#post_status" class="cancel-post-status hide-if-no-js button-cancel">Cancel</a>
                                                                </div>
                                                            </div>

                                                            <div class="misc-pub-section misc-pub-visibility" id="visibility">
                                                                Visibility: <span id="post-visibility-display">
                                                                    Public </span>

                                                                <a href="#visibility" class="edit-visibility hide-if-no-js" role="button"><span aria-hidden="true">Edit</span> <span class="screen-reader-text">
                                                                        Edit visibility </span></a>

                                                                <div id="post-visibility-select" class="hide-if-js">
                                                                    <input type="hidden" name="hidden_post_password" id="hidden-post-password" value="">

                                                                    <input type="hidden" name="hidden_post_visibility" id="hidden-post-visibility" value="public">
                                                                    <input type="radio" name="visibility" id="visibility-radio-public" value="public" checked="checked"> <label for="visibility-radio-public" class="selectit">Public</label><br>


                                                                    <input type="radio" name="visibility" id="visibility-radio-password" value="password"> <label for="visibility-radio-password" class="selectit">Password protected</label><br>
                                                                    <span id="password-span"><label for="post_password">Password:</label> <input type="text" name="post_password" id="post_password" value="" maxlength="255"><br></span>

                                                                    <input type="radio" name="visibility" id="visibility-radio-private" value="private"> <label for="visibility-radio-private" class="selectit">Private</label><br>

                                                                    <p>
                                                                        <a href="#visibility" class="save-post-visibility hide-if-no-js button">OK</a>
                                                                        <a href="#visibility" class="cancel-post-visibility hide-if-no-js button-cancel">Cancel</a>
                                                                    </p>
                                                                </div>
                                                            </div>

                                                            <div class="misc-pub-section curtime misc-pub-curtime">
                                                                <span id="timestamp">
                                                                    Published on: <b>Sep 26, 2024 at 12:34</b> </span>
                                                                <a href="#edit_timestamp" class="edit-timestamp hide-if-no-js" role="button">
                                                                    <span aria-hidden="true">Edit</span>
                                                                    <span class="screen-reader-text">
                                                                        Edit date and time </span>
                                                                </a>
                                                                <fieldset id="timestampdiv" class="hide-if-js">
                                                                    <legend class="screen-reader-text">
                                                                        Date and time </legend>
                                                                    <div class="timestamp-wrap"><label><span class="screen-reader-text">Month</span><select class="form-required" id="mm" name="mm">
                                                                                <option value="01" data-text="Jan">01-Jan</option>
                                                                                <option value="02" data-text="Feb">02-Feb</option>
                                                                                <option value="03" data-text="Mar">03-Mar</option>
                                                                                <option value="04" data-text="Apr">04-Apr</option>
                                                                                <option value="05" data-text="May">05-May</option>
                                                                                <option value="06" data-text="Jun">06-Jun</option>
                                                                                <option value="07" data-text="Jul">07-Jul</option>
                                                                                <option value="08" data-text="Aug">08-Aug</option>
                                                                                <option value="09" data-text="Sep" selected="selected">09-Sep</option>
                                                                                <option value="10" data-text="Oct">10-Oct</option>
                                                                                <option value="11" data-text="Nov">11-Nov</option>
                                                                                <option value="12" data-text="Dec">12-Dec</option>
                                                                            </select></label> <label><span class="screen-reader-text">Day</span><input type="text" id="jj" name="jj" value="26" size="2" maxlength="2" autocomplete="off" class="form-required"></label>, <label><span class="screen-reader-text">Year</span><input type="text" id="aa" name="aa" value="2024" size="4" maxlength="4" autocomplete="off" class="form-required"></label> at <label><span class="screen-reader-text">Hour</span><input type="text" id="hh" name="hh" value="12" size="2" maxlength="2" autocomplete="off" class="form-required"></label>:<label><span class="screen-reader-text">Minute</span><input type="text" id="mn" name="mn" value="34" size="2" maxlength="2" autocomplete="off" class="form-required"></label></div><input type="hidden" id="ss" name="ss" value="15">

                                                                    <input type="hidden" id="hidden_mm" name="hidden_mm" value="09">
                                                                    <input type="hidden" id="cur_mm" name="cur_mm" value="09">
                                                                    <input type="hidden" id="hidden_jj" name="hidden_jj" value="26">
                                                                    <input type="hidden" id="cur_jj" name="cur_jj" value="27">
                                                                    <input type="hidden" id="hidden_aa" name="hidden_aa" value="2024">
                                                                    <input type="hidden" id="cur_aa" name="cur_aa" value="2024">
                                                                    <input type="hidden" id="hidden_hh" name="hidden_hh" value="12">
                                                                    <input type="hidden" id="cur_hh" name="cur_hh" value="10">
                                                                    <input type="hidden" id="hidden_mn" name="hidden_mn" value="34">
                                                                    <input type="hidden" id="cur_mn" name="cur_mn" value="58">

                                                                    <p>
                                                                        <a href="#edit_timestamp" class="save-timestamp hide-if-no-js button">OK</a>
                                                                        <a href="#edit_timestamp" class="cancel-timestamp hide-if-no-js button-cancel">Cancel</a>
                                                                    </p>
                                                                </fieldset>
                                                            </div>
                                                        </div>
                                                        <div class="clear"></div>
                                                    </div>

                                                    <div id="major-publishing-actions">
                                                        <div id="delete-action">
                                                            <a class="submitdelete deletion" href="http://sureforms-dev.local/wp-admin/post.php?post=1122&amp;action=trash&amp;_wpnonce=0a024c572d">Move to Trash</a>
                                                        </div>

                                                        <div id="publishing-action">
                                                            <span class="spinner"></span>
                                                            <input name="original_publish" type="hidden" id="original_publish" value="Update">
                                                            <input type="submit" name="save" id="publish" class="button button-primary button-large" value="Update">
                                                        </div>
                                                        <div class="clear"></div>
                                                    </div>

                                                </div>
                                            </div>
                                        </div>
                                        <div id="sureform_form_name_meta" class="postbox ">
                                            <div class="postbox-header">
                                                <h2 class="hndle ui-sortable-handle">Submission Info</h2>
                                                <div class="handle-actions hide-if-no-js"><button type="button" class="handle-order-higher" aria-disabled="false" aria-describedby="sureform_form_name_meta-handle-order-higher-description"><span class="screen-reader-text">Move up</span><span class="order-higher-indicator" aria-hidden="true"></span></button><span class="hidden" id="sureform_form_name_meta-handle-order-higher-description">Move Submission Info box up</span><button type="button" class="handle-order-lower" aria-disabled="false" aria-describedby="sureform_form_name_meta-handle-order-lower-description"><span class="screen-reader-text">Move down</span><span class="order-lower-indicator" aria-hidden="true"></span></button><span class="hidden" id="sureform_form_name_meta-handle-order-lower-description">Move Submission Info box down</span><button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Submission Info</span><span class="toggle-indicator" aria-hidden="true"></span></button></div>
                                            </div>
                                            <div class="inside">
                                                <table style="border-collapse: separate; border-spacing: 5px 5px;">
                                                    <tbody>
                                                        <tr style="margin-bottom: 10px;">
                                                            <td><b>Form Name:</b></td>
                                                            <td>SF-1396</td>
                                                        </tr>
                                                        <tr style="margin-bottom: 10px;">
                                                            <td><b>Form ID:</b></td>
                                                            <td></td>
                                                        </tr>
                                                        <tr style="margin-bottom: 10px;">
                                                            <td><b>User IP:</b></td>
                                                            <td><a target="_blank" rel="noopener" href="https://ipinfo.io/"></a></td>
                                                        </tr>
                                                        <tr style="margin-bottom: 10px;">
                                                            <td><b>Browser:</b></td>
                                                            <td></td>
                                                        </tr>
                                                        <tr style="margin-bottom: 10px;">
                                                            <td><b>Device:</b></td>
                                                            <td></td>
                                                        </tr>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                                <div id="postbox-container-2" class="postbox-container">
                                    <div id="normal-sortables" class="meta-box-sortables ui-sortable">
                                        <div id="slugdiv" class="postbox  hide-if-js" style="">
                                            <div class="postbox-header">
                                                <h2 class="hndle ui-sortable-handle">Slug</h2>
                                                <div class="handle-actions hide-if-no-js"><button type="button" class="handle-order-higher" aria-disabled="false" aria-describedby="slugdiv-handle-order-higher-description"><span class="screen-reader-text">Move up</span><span class="order-higher-indicator" aria-hidden="true"></span></button><span class="hidden" id="slugdiv-handle-order-higher-description">Move Slug box up</span><button type="button" class="handle-order-lower" aria-disabled="false" aria-describedby="slugdiv-handle-order-lower-description"><span class="screen-reader-text">Move down</span><span class="order-lower-indicator" aria-hidden="true"></span></button><span class="hidden" id="slugdiv-handle-order-lower-description">Move Slug box down</span><button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Slug</span><span class="toggle-indicator" aria-hidden="true"></span></button></div>
                                            </div>
                                            <div class="inside">
                                                <label class="screen-reader-text" for="post_name">
                                                    Slug</label><input name="post_name" type="text" class="large-text" id="post_name" value="1122">
                                            </div>
                                        </div>
                                        <div id="sureform_entry_meta" class="postbox ">
                                            <div class="postbox-header">
                                                <h2 class="hndle ui-sortable-handle">Form Data</h2>
                                                <div class="handle-actions hide-if-no-js"><button type="button" class="handle-order-higher" aria-disabled="false" aria-describedby="sureform_entry_meta-handle-order-higher-description"><span class="screen-reader-text">Move up</span><span class="order-higher-indicator" aria-hidden="true"></span></button><span class="hidden" id="sureform_entry_meta-handle-order-higher-description">Move Form Data box up</span><button type="button" class="handle-order-lower" aria-disabled="false" aria-describedby="sureform_entry_meta-handle-order-lower-description"><span class="screen-reader-text">Move down</span><span class="order-lower-indicator" aria-hidden="true"></span></button><span class="hidden" id="sureform_entry_meta-handle-order-lower-description">Move Form Data box down</span><button type="button" class="handlediv" aria-expanded="true"><span class="screen-reader-text">Toggle panel: Form Data</span><span class="toggle-indicator" aria-hidden="true"></span></button></div>
                                            </div>
                                            <div class="inside">
                                                <table class="widefat striped">
                                                    <tbody>
                                                        <tr>
                                                            <th><b><?php esc_html_e( 'Fields', 'sureforms' ); ?></b></th>
                                                            <th><b><?php esc_html_e( 'Values', 'sureforms' ); ?></b></th>
                                                        </tr>
                                                        <?php
                                                            foreach( $meta_data as $field_name => $value ) :
                                                                if( in_array( $field_name, $excluded_fields, true ) ) {
                                                                    continue;
                                                                }
                                                                if ( false === str_contains( $field_name, '-lbl-' ) ) {
                                                                    continue;
                                                                }
                                                                $label = explode( '-lbl-', $field_name )[1];
                                                                // Getting the encrypted label. we are removing the block slug here.
                                                                $label = explode( '-', $label )[0];                                                            
                                                        ?>
                                                        <tr>
                                                            <td><b><?php echo $label ? esc_html( Helper::decrypt( $label ) ) : ''; ?></b></td>
                                                                <?php
                                                                    if ( false !== strpos( $field_name, 'srfm-upload' ) ) : ?>
                                                                        <style>
                                                                            .file-cards-container {
                                                                                display: flex;
                                                                                flex-wrap: wrap;
                                                                                gap: 10px;
                                                                            }

                                                                            .file-card {
                                                                                border: 1px solid #ddd;
                                                                                border-radius: 4px;
                                                                                padding: 10px;
                                                                                width: 100px; /* Reduced width */
                                                                                text-align: center;
                                                                                background: #f9f9f9;
                                                                                font-size: 12px; /* Reduced font size for smaller cards */
                                                                            }

                                                                            .file-card-image img {
                                                                                max-width: 80px; /* Reduced max width */
                                                                                max-height: 80px; /* Reduced max height */
                                                                                object-fit: cover;
                                                                            }

                                                                            .file-card-icon {
                                                                                font-size: 24px; /* Reduced icon size */
                                                                                margin-bottom: 5px;
                                                                            }

                                                                            .file-card-details {
                                                                                margin-bottom: 5px;
                                                                                font-weight: bold;
                                                                            }

                                                                            .file-card-url a {
                                                                                color: #007bff;
                                                                                text-decoration: none;
                                                                                font-size: 12px; /* Reduced font size */
                                                                            }

                                                                            .file-card-url a:hover {
                                                                                text-decoration: underline;
                                                                            }
                                                                        </style>
                                                                        <td>
                                                                            <div class="file-cards-container">
                                                                                <?php
                                                                                    $upload_values = $value;
                                                                                    if ( ! empty( $upload_values ) && is_array( $upload_values ) ) {
                                                                                        foreach ( $upload_values as $file_url ) {
                                                                                            $file_url = Helper::get_string_value( $file_url );
                                                                                            if ( ! empty( $file_url ) ) {
                                                                                                $file_type = pathinfo( $file_url, PATHINFO_EXTENSION );
                                                                                                $is_image  = in_array( $file_type, [ 'gif', 'png', 'bmp', 'jpg', 'jpeg', 'svg' ], true );
                                                                                                ?>
                                                                                                <div class="file-card">
                                                                                                    <?php if ( $is_image ) : ?>
                                                                                                        <div class="file-card-image">
                                                                                                            <a target="_blank" href="<?php echo esc_attr( urldecode( $file_url ) ); ?>">
                                                                                                                <img src="<?php echo esc_attr( urldecode( $file_url ) ); ?>" alt="img" />
                                                                                                            </a>
                                                                                                        </div>
                                                                                                    <?php else : ?>
                                                                                                        <div class="file-card-icon">
                                                                                                            <?php // Display a file icon for non-image files ?>
                                                                                                            <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M4 16.333V4.667a1.333 1.333 0 011.333-1.333h13.334a1.333 1.333 0 011.333 1.333v11.666a1.333 1.333 0 01-1.333 1.333H5.333A1.333 1.333 0 014 16.333zm8-8h2v6h-2v-6zm-2 8h6v2H10v-2zm-6-6h4v6H4v-6zm0-4h16v2H4V6z"/></svg>
                                                                                                        </div>
                                                                                                        <div class="file-card-details">
                                                                                                            <span><?php echo esc_html( strtoupper( $file_type ) ); ?></span>
                                                                                                        </div>
                                                                                                    <?php endif; ?>
                                                                                                    <div class="file-card-url">
                                                                                                        <a target="_blank" href="<?php echo esc_attr( urldecode( $file_url ) ); ?>"><?php echo esc_html__( 'Open', 'sureforms' ); ?></a>
                                                                                                    </div>
                                                                                                </div>
                                                                                                <?php
                                                                                            }
                                                                                        }
                                                                                    }
                                                                                ?>
                                                                            </div>
                                                                        </td>
                                                                <?php elseif( false !== strpos( $field_name, 'srfm-url' ) ) : ?>
                                                                    <td><a target="_blank" href="<?php echo esc_url( $value ); ?>"><?php echo esc_url( $value ); ?></a></td>
                                                                <?php else: ?>
                                                                    <td><?php echo false !== strpos( $value, PHP_EOL ) ? wp_kses_post( wpautop( $value ) ) : wp_kses_post( $value ); ?></td>
                                                                <?php endif; ?>
                                                            </tr>
                                                            <?php endforeach; ?>
                                                    </tbody>
                                                </table>
                                            </div>
                                        </div>
                                    </div>
                                    <div id="advanced-sortables" class="meta-box-sortables ui-sortable empty-container"></div>
                                </div>
                            </div><!-- /post-body -->
                            <br class="clear">
                        </div><!-- /poststuff -->
                    </form>
                </div>
            <?php
        }
    }
?>