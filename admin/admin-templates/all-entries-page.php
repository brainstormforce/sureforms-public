<?php
use SRFM\Inc\Helper;
use SRFM\Inc\Database\Tables\Entries;
    if ( ! defined( 'ABSPATH' ) ) {
        exit; // Exit if accessed directly.
    }
?>
<html>
    <body>
        <div id="wpbody" role="main">
            <div id="wpbody-content">
                <div id="screen-meta" class="metabox-prefs">
                    <div id="contextual-help-wrap" class="hidden no-sidebar" tabindex="-1" aria-label="Contextual Help Tab">
                        <div id="contextual-help-back"></div>
                        <div id="contextual-help-columns">
                            <div class="contextual-help-tabs">
                                <ul>
                                </ul>
                            </div>
                            <div class="contextual-help-tabs-wrap">
                            </div>
                        </div>
                    </div>
                    <div id="screen-options-wrap" class="hidden" tabindex="-1" aria-label="Screen Options Tab">
                        <form id="adv-settings" method="post">
                            <fieldset class="screen-options">
                                <legend>Pagination</legend>
                                <label for="edit_sureforms_entry_per_page">Number of items per page:</label>
                                <input type="number" step="1" min="1" max="999" class="screen-per-page" name="wp_screen_options[value]" id="edit_sureforms_entry_per_page" value="20">
                                <input type="hidden" name="wp_screen_options[option]" value="edit_sureforms_entry_per_page">
                            </fieldset>
                            <p class="submit"><input type="submit" name="screen-options-apply" id="screen-options-apply" class="button button-primary" value="Apply"></p>
                            <input type="hidden" id="screenoptionnonce" name="screenoptionnonce" value="9bc99acf33">
                        </form>
                    </div>
                </div>
                <div id="screen-meta-links">
                    <div id="screen-options-link-wrap" class="hide-if-no-js screen-meta-toggle">
                        <button type="button" id="show-settings-link" class="button show-settings" aria-controls="screen-options-wrap" aria-expanded="false">Screen Options</button>
                    </div>
                </div>
                <div class="wrap">
                    <h1 class="wp-heading-inline">Entries</h1>
                    <hr class="wp-header-end">


                    <h2 class="screen-reader-text">Filter posts list</h2>
                    <ul class="subsubsub">
                        <li class="all"><a href="edit.php?post_type=sureforms_entry" class="current" aria-current="page">All <span class="count">(389)</span></a> |</li>
                        <li class="publish"><a href="edit.php?post_status=publish&amp;post_type=sureforms_entry">Published <span class="count">(389)</span></a></li>
                    </ul>
                    <form id="posts-filter" method="get">

                        <p class="search-box">
                            <label class="screen-reader-text" for="post-search-input">Search Entries:</label>
                            <input type="search" id="post-search-input" name="s" value="">
                            <input type="submit" id="search-submit" class="button" value="Search Entries">
                        </p>

                        <input type="hidden" name="post_status" class="post_status_page" value="all">
                        <input type="hidden" name="post_type" class="post_type_page" value="sureforms_entry">



                        <input type="hidden" id="_wpnonce" name="_wpnonce" value="1470c8c293"><input type="hidden" name="_wp_http_referer" value="/wp-admin/edit.php?post_type=sureforms_entry">
                        <div class="tablenav top">

                            <div class="alignleft actions bulkactions">
                                <label for="bulk-action-selector-top" class="screen-reader-text">Select bulk action</label><select name="action" id="bulk-action-selector-top">
                                    <option value="-1">Bulk actions</option>
                                    <option value="edit" class="hide-if-no-js">Edit</option>
                                    <option value="trash">Move to Trash</option>
                                </select>
                                <input type="submit" id="doaction" class="button action" value="Apply">
                            </div>
                            <div class="alignleft actions">
                                <label for="filter-by-date" class="screen-reader-text">Filter by date</label>
                                <select name="m" id="filter-by-date">
                                    <option selected="selected" value="0">All dates</option>
                                    <!-- POPULATE DROPDOWN WITH "Month Year" -->
                                </select>
                                <select name="sureforms_tax" id="srfm-tax-filter">
                                    <option value=""> All Form Entries</option>
                                    <!-- POPULATE DROPDOWN WITH FORM ID => FORM NAME -->
                                </select><input type="submit" name="filter_action" id="post-query-submit" class="button" value="Filter">
                            </div>
                            <h2 class="screen-reader-text">Posts list navigation</h2>
                            <div class="tablenav-pages"><span class="displaying-num">389 items</span>
                                <span class="pagination-links"><span class="tablenav-pages-navspan button disabled" aria-hidden="true">«</span>
                                    <span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>
                                    <span class="paging-input"><label for="current-page-selector" class="screen-reader-text">Current Page</label><input class="current-page" id="current-page-selector" type="text" name="paged" value="1" size="2" aria-describedby="table-paging"><span class="tablenav-paging-text"> of <span class="total-pages">20</span></span></span>
                                    <a class="next-page button" href="http://sureforms-dev.local/wp-admin/edit.php?post_type=sureforms_entry&amp;paged=2"><span class="screen-reader-text">Next page</span><span aria-hidden="true">›</span></a>
                                    <a class="last-page button" href="http://sureforms-dev.local/wp-admin/edit.php?post_type=sureforms_entry&amp;paged=20"><span class="screen-reader-text">Last page</span><span aria-hidden="true">»</span></a></span>
                            </div>
                            <br class="clear">
                        </div>
                        <h2 class="screen-reader-text">Posts list</h2>
                        <table class="wp-list-table widefat fixed striped table-view-list posts">
                            <caption class="screen-reader-text">Table ordered by Date. Descending.</caption>
                            <thead>
                                <tr>
                                    <td id="cb" class="manage-column column-cb check-column"><input id="cb-select-all-1" type="checkbox">
                                        <label for="cb-select-all-1"><span class="screen-reader-text">Select All</span></label>
                                    </td>
                                    <!-- Note: Take care of classes for sortable fields and the label for the sorting type as well. -->
                                    <!-- Note: For desc or asc the specific class should be added and the screen reader text should change. -->
                                    <th scope="col" id="entry_id" class="manage-column column-entry_id sortable">
                                        <a href="">
                                            <span><?php esc_html_e( 'ID', 'sureforms' ) ?></span>
                                            <span class="sorting-indicators">
                                                <span class="sorting-indicator asc" aria-hidden="true"></span>
                                                <span class="sorting-indicator desc" aria-hidden="true"></span>
                                            </span>
                                            <span class="screen-reader-text">Sort ascending.</span>
                                        </a>
                                    </th>
                                    <th scope="col" id="form_name" class="manage-column column-form_name"><?php esc_html_e( 'Form Name', 'sureforms' ) ?></th>
                                    <th scope="col" id="status" class="manage-column column-status sortable">
                                        <a href="">
                                            <span><?php esc_html_e( 'Status', 'sureforms' ) ?></span>
                                            <span class="sorting-indicators">
                                                <span class="sorting-indicator asc" aria-hidden="true"></span>
                                                <span class="sorting-indicator desc" aria-hidden="true"></span>
                                            </span> <span class="screen-reader-text">Sort ascending.</span>
                                        </a>
                                    </th>
                                    <th scope="col" id="title" class="manage-column column-title column-primary sortable desc" abbr="Title">
                                        <a href="http://sureforms-dev.local/wp-admin/edit.php?post_type=sureforms_entry&amp;orderby=title&amp;order=asc">
                                            <span><?php esc_html_e( 'First Field', 'sureforms' ) ?></span>
                                            <span class="sorting-indicators">
                                                <span class="sorting-indicator asc" aria-hidden="true"></span>
                                                <span class="sorting-indicator desc" aria-hidden="true"></span>
                                            </span>
                                            <span class="screen-reader-text">Sort ascending.</span>
                                        </a>
                                    </th>
                                    <th scope="col" id="date" class="manage-column column-date sorted desc" aria-sort="descending" abbr="Date">
                                        <a href="http://sureforms-dev.local/wp-admin/edit.php?post_type=sureforms_entry&amp;orderby=date&amp;order=asc">
                                            <span><?php esc_html_e( 'Submitted On', 'sureforms' ) ?></span>
                                            <span class="sorting-indicators">
                                                <span class="sorting-indicator asc" aria-hidden="true"></span>
                                                <span class="sorting-indicator desc" aria-hidden="true"></span>
                                            </span>
                                        </a>
                                    </th>
                                </tr>
                            </thead>

                            <tbody id="the-list">
                                <?php
                                    $entries = Entries::get_all();
                                    foreach ( $entries as $entry ) {
                                        $entry_id = $entry['ID'];
                                        $status = $entry['status'];
                                        $first_field = reset( $entry['user_data'] );
                                        $submitted_on = date( 'Y/m/d \a\t g:i a', strtotime( $entry['created_at'] ) );
                                        $form_name = ! empty( get_the_title( $entry['form_id'] ) ) ? get_the_title( $entry['form_id'] ) : 'SureForms Form #' . intval( $entry["form_id"] );
                                        ob_start();?>
                                        <tr id="post-<?php esc_attr_e( $entry_id )?>" class="iedit author-self level-0 type-sureforms_entry status-publish hentry remove-featured-img-padding post-<?php esc_attr_e( $entry_id ) ?>">
                                            <th scope="row" class="check-column"> <input id="cb-select-<?php echo esc_attr( $entry_id ) ?>" type="checkbox" name="post[]" value="<?php esc_attr_e( $entry_id ) ?>">
                                                <label for="cb-select-<?php esc_attr_e( $entry_id ) ?>">
                                                    <span class="screen-reader-text">
                                                        <?php echo sprintf( __( 'Select Entry #%s', 'sureforms' ), esc_attr( $entry_id ) ); ?>
                                                    </span>
                                                </label>
                                                <div class="locked-indicator">
                                                    <span class="locked-indicator-icon" aria-hidden="true"></span>
                                                    <span class="screen-reader-text">
                                                        “Entry #<?php esc_attr_e( $entry_id ) ?>” is locked </span>
                                                </div>
                                            </th>
                                            <td class="entry_id column-entry_id has-row-actions" data-colname="ID">
                                                <strong>
                                                    <a class="row-title" aria-label="“Entry #<?php esc_attr_e( $entry_id ); ?> (Edit)"><?php esc_html_e( 'Entry #' ); esc_attr_e( $entry_id ); ?></a>
                                                </strong>
                                                <div class="row-actions">
                                                    <span class="edit">
                                                        <a href="http://sureforms-dev.local/wp-admin/post.php?post=1115&amp;action=edit">View</a> |
                                                    </span>
                                                    <span class="trash">
                                                        <a href="http://sureforms-dev.local/wp-admin/post.php?post=1115&amp;action=trash&amp;_wpnonce=a4e6e06a91" class="submitdelete" aria-label="Move “Entry #1115” to the Trash">
                                                            Trash
                                                        </a>
                                                    </span>
                                                </div>
                                                <button type="button" class="toggle-row">
                                                    <span class="screen-reader-text">Show more details</span>
                                                </button>
                                            </td>
                                            <!-- <td class="title column-title has-row-actions column-primary page-title" data-colname="First Field">
                                                <div class="locked-info"><span class="locked-avatar"></span> <span class="locked-text"></span></div>
                                                <strong><a class="row-title" href="http://sureforms-dev.local/wp-admin/post.php?post=1115&amp;action=edit" aria-label="“Entry #1115” (Edit)">Entry #1115</a></strong>
                                                <div class="hidden" id="inline_1115">
                                                    <div class="post_title">Entry #1115</div>
                                                    <div class="post_name">1115</div>
                                                    <div class="post_author">1</div>
                                                    <div class="comment_status">closed</div>
                                                    <div class="ping_status">closed</div>
                                                    <div class="_status">publish</div>
                                                    <div class="jj">25</div>
                                                    <div class="mm">09</div>
                                                    <div class="aa">2024</div>
                                                    <div class="hh">15</div>
                                                    <div class="mn">32</div>
                                                    <div class="ss">02</div>
                                                    <div class="post_password"></div>
                                                    <div class="page_template">default</div>
                                                    <div class="sticky"></div>
                                                </div>
                                                <div class="row-actions"><span class="edit"><a href="http://sureforms-dev.local/wp-admin/post.php?post=1115&amp;action=edit">View</a> | </span><span class="inline hide-if-no-js"><button type="button" class="button-link editinline" aria-label="Quick edit “Entry #1115” inline" aria-expanded="false">Quick&nbsp;Edit</button> | </span><span class="trash"><a href="http://sureforms-dev.local/wp-admin/post.php?post=1115&amp;action=trash&amp;_wpnonce=a4e6e06a91" class="submitdelete" aria-label="Move “Entry #1115” to the Trash">Trash</a></span></div><button type="button" class="toggle-row"><span class="screen-reader-text">Show more details</span></button>
                                            </td> -->
                                            <td class="form_name column-form_name" data-colname="Form Name">
                                                <p><?php echo esc_attr( $form_name ) ?></p>
                                            </td>
                                            <td class="status column-status srfm-entry-status" data-colname="Status">
                                                <span class="<?php echo "read" === $status ? "status-read" : "status-unread" ?>"><?php echo esc_attr( $status ) ?></span>
                                            </td>
                                            <td class="first-field column-first-field" data-colname="First Field">
                                                <p><?php echo esc_attr( $first_field ) ?></p>
                                            </td>
                                            <td class="date column-date" data-colname="Submitted On">Published<br><?php echo esc_attr( $submitted_on ) ?></td>

                                        </tr>    
                                    <?php
                                        echo ob_get_clean();
                                    }
                                ?>
                            </tbody>

                            <tfoot>
                            <tr>
                                    <td id="cb" class="manage-column column-cb check-column"><input id="cb-select-all-1" type="checkbox">
                                        <label for="cb-select-all-1"><span class="screen-reader-text">Select All</span></label>
                                    </td>
                                    <!-- Note: Take care of classes for sortable fields and the label for the sorting type as well. -->
                                    <!-- Note: For desc or asc the specific class should be added and the screen reader text should change. -->
                                    <th scope="col" id="entry_id" class="manage-column column-entry_id sortable">
                                        <a href="">
                                            <span><?php esc_html_e( 'ID', 'sureforms' ) ?></span>
                                            <span class="sorting-indicators">
                                                <span class="sorting-indicator asc" aria-hidden="true"></span>
                                                <span class="sorting-indicator desc" aria-hidden="true"></span>
                                            </span>
                                            <span class="screen-reader-text">Sort ascending.</span>
                                        </a>
                                    </th>
                                    <th scope="col" id="form_name" class="manage-column column-form_name"><?php esc_html_e( 'Form Name', 'sureforms' ) ?></th>
                                    <th scope="col" id="status" class="manage-column column-status sortable">
                                        <a href="">
                                            <span><?php esc_html_e( 'Status', 'sureforms' ) ?></span>
                                            <span class="sorting-indicators">
                                                <span class="sorting-indicator asc" aria-hidden="true"></span>
                                                <span class="sorting-indicator desc" aria-hidden="true"></span>
                                            </span> <span class="screen-reader-text">Sort ascending.</span>
                                        </a>
                                    </th>
                                    <th scope="col" id="title" class="manage-column column-title column-primary sortable desc" abbr="Title">
                                        <a href="http://sureforms-dev.local/wp-admin/edit.php?post_type=sureforms_entry&amp;orderby=title&amp;order=asc">
                                            <span><?php esc_html_e( 'First Field', 'sureforms' ) ?></span>
                                            <span class="sorting-indicators">
                                                <span class="sorting-indicator asc" aria-hidden="true"></span>
                                                <span class="sorting-indicator desc" aria-hidden="true"></span>
                                            </span>
                                            <span class="screen-reader-text">Sort ascending.</span>
                                        </a>
                                    </th>
                                    <th scope="col" id="date" class="manage-column column-date sorted desc" aria-sort="descending" abbr="Date">
                                        <a href="http://sureforms-dev.local/wp-admin/edit.php?post_type=sureforms_entry&amp;orderby=date&amp;order=asc">
                                            <span><?php esc_html_e( 'Submitted On', 'sureforms' ) ?></span>
                                            <span class="sorting-indicators">
                                                <span class="sorting-indicator asc" aria-hidden="true"></span>
                                                <span class="sorting-indicator desc" aria-hidden="true"></span>
                                            </span>
                                        </a>
                                    </th>
                                </tr>
                            </tfoot>

                        </table>
                        <div class="tablenav bottom">

                            <div class="alignleft actions bulkactions">
                                <label for="bulk-action-selector-bottom" class="screen-reader-text">Select bulk action</label><select name="action2" id="bulk-action-selector-bottom">
                                    <option value="-1">Bulk actions</option>
                                    <option value="edit" class="hide-if-no-js">Edit</option>
                                    <option value="trash">Move to Trash</option>
                                </select>
                                <input type="submit" id="doaction2" class="button action" value="Apply">
                            </div>
                            <div class="alignleft actions">
                            </div>
                            <div class="tablenav-pages"><span class="displaying-num">389 items</span>
                                <span class="pagination-links"><span class="tablenav-pages-navspan button disabled" aria-hidden="true">«</span>
                                    <span class="tablenav-pages-navspan button disabled" aria-hidden="true">‹</span>
                                    <span class="screen-reader-text">Current Page</span><span id="table-paging" class="paging-input"><span class="tablenav-paging-text">1 of <span class="total-pages">20</span></span></span>
                                    <a class="next-page button" href="http://sureforms-dev.local/wp-admin/edit.php?post_type=sureforms_entry&amp;paged=2"><span class="screen-reader-text">Next page</span><span aria-hidden="true">›</span></a>
                                    <a class="last-page button" href="http://sureforms-dev.local/wp-admin/edit.php?post_type=sureforms_entry&amp;paged=20"><span class="screen-reader-text">Last page</span><span aria-hidden="true">»</span></a></span>
                            </div>
                            <br class="clear">
                        </div>

                    </form>


                    <form method="get">
                        <table style="display: none">
                            <tbody id="inlineedit">
                                <tr id="inline-edit" class="inline-edit-row inline-edit-row-page quick-edit-row quick-edit-row-page inline-edit-sureforms_entry" style="display: none">
                                    <td colspan="5" class="colspanchange">
                                        <div class="inline-edit-wrapper" role="region" aria-labelledby="quick-edit-legend">
                                            <fieldset class="inline-edit-col-left">
                                                <legend class="inline-edit-legend" id="quick-edit-legend">Quick Edit</legend>
                                                <div class="inline-edit-col">

                                                    <label>
                                                        <span class="title">Title</span>
                                                        <span class="input-text-wrap"><input type="text" name="post_title" class="ptitle" value=""></span>
                                                    </label>

                                                    <fieldset class="inline-edit-date">
                                                        <legend><span class="title">Date</span></legend>
                                                        <div class="timestamp-wrap"><label><span class="screen-reader-text">Month</span><select class="form-required" name="mm">
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
                                                                </select></label> <label><span class="screen-reader-text">Day</span><input type="text" name="jj" value="25" size="2" maxlength="2" autocomplete="off" class="form-required"></label>, <label><span class="screen-reader-text">Year</span><input type="text" name="aa" value="2024" size="4" maxlength="4" autocomplete="off" class="form-required"></label> at <label><span class="screen-reader-text">Hour</span><input type="text" name="hh" value="15" size="2" maxlength="2" autocomplete="off" class="form-required"></label>:<label><span class="screen-reader-text">Minute</span><input type="text" name="mn" value="32" size="2" maxlength="2" autocomplete="off" class="form-required"></label></div><input type="hidden" id="ss" name="ss" value="02">
                                                    </fieldset>
                                                    <br class="clear">

                                                    <div class="inline-edit-group wp-clearfix">
                                                        <label class="alignleft">
                                                            <span class="title">Password</span>
                                                            <span class="input-text-wrap"><input type="text" name="post_password" class="inline-edit-password-input" value=""></span>
                                                        </label>

                                                        <span class="alignleft inline-edit-or">
                                                            –OR– </span>
                                                        <label class="alignleft inline-edit-private">
                                                            <input type="checkbox" name="keep_private" value="private">
                                                            <span class="checkbox-title">Private</span>
                                                        </label>
                                                    </div>

                                                </div>
                                            </fieldset>

                                            <fieldset class="inline-edit-col-right">
                                                <div class="inline-edit-col">
                                                    <div class="inline-edit-group wp-clearfix">
                                                        <label class="inline-edit-status alignleft">
                                                            <span class="title">Status</span>
                                                            <select name="_status">

                                                                <option value="publish">Published</option>
                                                                <option value="future">Scheduled</option>

                                                                <option value="pending">Pending Review</option>
                                                                <option value="draft">Draft</option>
                                                            </select>
                                                        </label>
                                                    </div>
                                                </div>
                                            </fieldset>

                                            <div class="submit inline-edit-save">
                                                <input type="hidden" id="_inline_edit" name="_inline_edit" value="1b5e6d34ca"> <button type="button" class="button button-primary save">Update</button>

                                                <button type="button" class="button cancel">Cancel</button>

                                                <span class="spinner"></span>

                                                <input type="hidden" name="post_view" value="list">
                                                <input type="hidden" name="screen" value="edit-sureforms_entry">
                                                <input type="hidden" name="post_author" value="">

                                                <div class="notice notice-error notice-alt inline hidden">
                                                    <p class="error"></p>
                                                </div>
                                            </div>
                                        </div> <!-- end of .inline-edit-wrapper -->

                                    </td>
                                </tr>

                                <tr id="bulk-edit" class="inline-edit-row inline-edit-row-page bulk-edit-row bulk-edit-row-page bulk-edit-sureforms_entry" style="display: none">
                                    <td colspan="5" class="colspanchange">
                                        <div class="inline-edit-wrapper" role="region" aria-labelledby="bulk-edit-legend">
                                            <fieldset class="inline-edit-col-left">
                                                <legend class="inline-edit-legend" id="bulk-edit-legend">Bulk Edit</legend>
                                                <div class="inline-edit-col">

                                                    <div id="bulk-title-div">
                                                        <div id="bulk-titles"></div>
                                                    </div>

                                                </div>
                                            </fieldset>

                                            <fieldset class="inline-edit-col-right">
                                                <div class="inline-edit-col">
                                                    <div class="inline-edit-group wp-clearfix">
                                                        <label class="inline-edit-status alignleft">
                                                            <span class="title">Status</span>
                                                            <select name="_status">
                                                                <option value="-1">— No Change —</option>

                                                                <option value="publish">Published</option>

                                                                <option value="private">Private</option>

                                                                <option value="pending">Pending Review</option>
                                                                <option value="draft">Draft</option>
                                                            </select>
                                                        </label>
                                                    </div>
                                                </div>
                                            </fieldset>

                                            <div class="submit inline-edit-save">
                                                <input type="submit" name="bulk_edit" id="bulk_edit" class="button button-primary" value="Update">
                                                <button type="button" class="button cancel">Cancel</button>

                                                <input type="hidden" name="post_view" value="list">
                                                <input type="hidden" name="screen" value="edit-sureforms_entry">

                                                <div class="notice notice-error notice-alt inline hidden">
                                                    <p class="error"></p>
                                                </div>
                                            </div>
                                        </div> <!-- end of .inline-edit-wrapper -->
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                    </form>
                    <div id="ajax-response"></div>
                    <div class="clear"></div>
                </div>
                <div class="clear"></div>
            </div><!-- wpbody-content -->
            <div class="clear"></div>
        </div>
    </body>
</html>