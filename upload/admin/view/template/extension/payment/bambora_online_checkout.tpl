<?php 
echo $header;
echo $column_left;
?>
<div id="content">
  <div class="page-header">
    <div class="container-fluid">
      <div class="pull-right">
        <button type="submit" form="form-payment" data-toggle="tooltip" title="<?php echo $button_save; ?>" class="btn btn-primary"><i class="fa fa-save"></i></button>
        <a href="<?php echo $cancel; ?>" data-toggle="tooltip" title="<?php echo $button_cancel; ?>" class="btn btn-default"><i class="fa fa-reply"></i></a></div>
      <h1><?php echo $heading_title; ?></h1>
      <ul class="breadcrumb">
        <?php foreach($breadcrumbs as $breadcrumb) { ?>
        <li><a href="<?php echo $breadcrumb['href']; ?>"><?php echo $breadcrumb['text']; ?></a></li>
        <?php }?>
      </ul>
    </div>
  </div>
  <div class="container-fluid"> 
    <?php if($error_permission) { ?>
    <div class="alert alert-danger alert-dismissible"><i class="fa fa-exclamation-circle"></i> <?php echo $error_permission; ?>
      <button type="button" class="close" data-dismiss="alert">&times;</button>
    </div>
    <?php } ?>
    <div class="panel panel-default">
      <div class="panel-heading">
        <h3 class="panel-title"><i class="fa fa-pencil"></i> <?php echo $text_edit . ' - v.' .$module_version; ?></h3>
      </div>
      <div class="panel-body">
        <form action="<?php echo $action; ?>" method="post" enctype="multipart/form-data" id="form-bambora-online-checkout" class="form-horizontal">
        
         <div class="form-group">
            <label class="col-sm-2 control-label" for="select-status"><span data-toggle="tooltip" title="<?php echo $help_status; ?>"><?php echo $entry_status; ?></span></label>
            <div class="col-sm-10">
                <select name="bambora_online_checkout_status" id="select-status" class="form-control">
                
                <?php if($bambora_online_checkout_status) { ?>
                
                <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
                <option value="0"><?php echo $text_disabled; ?></option>
                
                <?php } else { ?>
                
                <option value="1"><?php echo $text_enabled; ?></option>
                <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
                
                <?php } ?>
              
              </select>
            </div>
          </div>      
          <div class="form-group required">
            <label class="col-sm-2 control-label" for="input-merchant"><span data-toggle="tooltip" title="<?php echo $help_merchant; ?>"><?php echo $entry_merchant; ?></span></label>
            <div class="col-sm-10">
              <input type="text" name="bambora_online_checkout_merchant" value="<?php echo $bambora_online_checkout_merchant; ?>" placeholder="<?php echo $entry_merchant; ?>" id="input-merchant" class="form-control" />
              <?php if($error_merchant) { ?>
              <div class="text-danger"><?php echo $error_merchant; ?></div>
              <?php } ?>
            </div>
          </div>
          <div class="form-group required">
            <label class="col-sm-2 control-label" for="input-access-token"><span data-toggle="tooltip" title="<?php echo $help_access_token; ?>"> <?php echo $entry_access_token; ?></span></label>
            <div class="col-sm-10">
              <input type="text" name="bambora_online_checkout_access_token" value="<?php echo $bambora_online_checkout_access_token; ?>" placeholder="<?php echo $entry_access_token; ?>" id="input-accesstoken" class="form-control" />
              <?php if($error_access_token) { ?> 
              <div class="text-danger"><?php echo $error_access_token; ?></div>
              <?php } ?> 
            </div>
          </div>
          <div class="form-group required">
            <label class="col-sm-2 control-label" for="input-secret-token"><span data-toggle="tooltip" title="<?php echo $help_secret_token; ?>"><?php echo $entry_secret_token; ?></span></label>
            <div class="col-sm-10">
              <input type="password" name="bambora_online_checkout_secret_token" value="<?php echo $bambora_online_checkout_secret_token; ?>" placeholder="<?php echo $entry_secret_token; ?>" id="input-secrettoken" class="form-control" />
              <?php if($error_secret_token) { ?>
              <div class="text-danger"><?php echo $error_secret_token; ?></div>
              <?php } ?>
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-md5"><span data-toggle="tooltip" title="<?php echo $help_md5; ?>"><?php echo $entry_md5; ?></span></label>
            <div class="col-sm-10">
              <input type="password" name="bambora_online_checkout_md5" value="<?php echo $bambora_online_checkout_md5; ?>" placeholder="<?php echo $entry_md5; ?>" id="input-md5" class="form-control" />
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-window-state"><span data-toggle="tooltip" title="<?php echo $help_window_state; ?>"><?php echo $entry_window_state; ?></span></label>
            <div class="col-sm-10">
                <select name="bambora_online_checkout_window_state" id="input-window-state" class="form-control">
                
                <?php if($bambora_online_checkout_window_state == 1) { ?>
                
                <option value="1" selected="selected"><?php echo $text_window_state_fullscreen; ?></option>
                <option value="2"><?php echo $text_window_state_overlay; ?></option>
                
                <?php }else { ?>
                
                <option value="1"><?php echo $text_window_state_fullscreen; ?></option>
                <option value="2" selected="selected"><?php echo $text_window_state_overlay; ?></option>
                
                <?php } ?>              
              </select>
            </div>
          </div>
            <div class="form-group">
                <label class="col-sm-2 control-label" for="input-window-id"><span data-toggle="tooltip" title="<?php echo $help_window_id; ?>"><?php echo $entry_window_id; ?></span></label>
                <div class="col-sm-10">
                  <input type="text" name="bambora_online_checkout_window_id" value="<?php echo $bambora_online_checkout_window_id; ?>" placeholder="<?php echo $entry_window_id; ?>" id="input-windowid" class="form-control" />
                </div>
            </div>
            <div class="form-group">
            <label class="col-sm-2 control-label" for="input-instant-capture"><span data-toggle="tooltip" title="<?php echo $help_instant_capture; ?>"><?php echo $entry_instant_capture; ?></span></label>
            <div class="col-sm-10">
              <select name="bambora_online_checkout_instant_capture" id="input-instant-capture" class="form-control">
                
                <?php if($bambora_online_checkout_instant_capture) { ?>
                
                <option value="1" selected="selected"><?php echo $text_enabled;?></option>
                <option value="0"><?php echo $text_disabled; ?></option>
                
                <?php }else { ?>
                
                <option value="1"><?php echo $text_enabled; ?></option>
                <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
                
                <?php } ?>
              
              </select>
            </div>
          </div>     
            <div class="form-group">
            <label class="col-sm-2 control-label" for="input-immediate-redirect-to-accept"><span data-toggle="tooltip" title="<?php echo $help_immediate_redirect_to_accept;?>"><?php echo $entry_immediate_redirect_to_accept; ?></span></label>
            <div class="col-sm-10">
              <select name="bambora_online_checkout_immediate_redirect_to_accept" id="input-immediate_redirect_to_accept" class="form-control">
                
                <?php if($bambora_online_checkout_immediate_redirect_to_accept) { ?>
                
                <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
                <option value="0"><?php echo $text_disabled; ?></option>
                
                <?php }else { ?>
                
                <option value="1"><?php echo $text_enabled; ?></option>
                <option value="0" selected="selected"><?php echo $text_disabled; ?></option>
                
                <?php } ?>
              </select>
            </div>
          </div> 
        <div class="form-group">
            <label class="col-sm-2 control-label" for="input-rounding-mode"><span data-toggle="tooltip" title="<?php echo $help_rounding_mode; ?>"><?php echo $entry_rounding_mode; ?></span></label>
            <div class="col-sm-10">
                <select name="bambora_online_checkout_rounding_mode" id="input-rounding-mode" class="form-control">
                
                <?php if($bambora_online_checkout_rounding_mode == 1) { ?>
                
                <option value="default" selected="selected"><?php echo $text_rounding_mode_default; ?></option>
                <option value="up"><?php echo $text_rounding_mode_always_up; ?></option>
                <option value="down"><?php echo $text_rounding_mode_always_down; ?></option>
                
                <?php }elseif($bambora_online_checkout_rounding_mode == 2) { ?>

                <option value="default"><?php echo $text_rounding_mode_default; ?></option>
                <option value="up" selected="selected"><?php echo $text_rounding_mode_always_up; ?></option>
                <option value="down"><?php echo $text_roundingmode_alwaysdown; ?></option>

                <?php }elseif($bambora_online_checkout_rounding_mode == 3) { ?>
                    
                <option value="default"><?php echo $text_rounding_mode_default; ?></option>
                <option value="up"><?php echo $text_rounding_mode_always_up; ?></option>
                <option value="down" selected="selected"><?php echo $text_rounding_mode_always_down;?></option>

                <?php }else { ?>
                
                <option value="default" selected="selected"><?php echo $text_rounding_mode_default;?></option>
                <option value="up"><?php echo $text_rounding_mode_always_up; ?></option>
                <option value="down"><?php echo $text_rounding_mode_always_down; ?></option>
                
                <?php } ?>
              
              </select>
            </div>
          </div>
            <div class="form-group">
                <label class="col-sm-2 control-label" for="input-payment-method-title"><span data-toggle="tooltip" title="<?php echo $help_payment_method_title; ?>"><?php echo $entry_payment_method_title; ?></span></label>
                <div class="col-sm-10">
                  <input type="text" name="bambora_online_checkout_payment_method_title" value="<?php echo $bambora_online_checkout_payment_method_title; ?>" placeholder="<?php echo $entry_payment_method_title; ?>" id="input-payment-method-title" class="form-control" />
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label" for="input-payment-method-update"><span data-toggle="tooltip" title="<?php echo $help_payment_method_update; ?>"><?php echo $entry_payment_method_update; ?></span></label>
                <div class="col-sm-10">
                  <select name="payment_bambora_online_checkout_payment_method_update" id="input-payment-method-update" class="form-control">

                      <?php if ($payment_bambora_online_checkout_payment_method_update) { ?>

                      <option value="1" selected="selected"><?php echo $text_yes; ?></option>
                      <option value="0"><?php echo $text_no; ?></option>

                      <?php }else { ?>

                      <option value="1"><?php echo $text_yes; ?></option>
                      <option value="0" selected="selected"><?php echo $text_no; ?></option>

                      <?php } ?>

                  </select>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label" for="input-total"><span data-toggle="tooltip" title="<?php echo $help_total; ?>"><?php echo $entry_total; ?></span></label>
                <div class="col-sm-10">
                  <input type="text" name="bambora_online_checkout_total" value="<?php echo $bambora_online_checkout_total; ?>" placeholder="<?php echo $entry_total; ?>" id="input-total" class="form-control" />
                </div>
          </div>
        <div class="form-group">
            <label class="col-sm-2 control-label" for="input-order-status-completed"><span data-toggle="tooltip" title="<?php echo $help_order_status_completed; ?>"><?php echo $entry_order_status_completed; ?></span></label>
            <div class="col-sm-10">
              <select name="bambora_online_checkout_order_status_completed" id="input-order-status-completed" class="form-control">
                
                <?php foreach($order_statuses as $order_status) {
                if($order_status['order_status_id'] == $bambora_online_checkout_order_status_completed) { ?>
                <option value="<?php echo $order_status['order_status_id']; ?>" selected="selected"><?php echo $order_status['name']; ?></option>
                
                <?php }else { ?>
                
                <option value="<?php echo $order_status['order_status_id']; ?>"><?php echo $order_status['name']; ?></option>
                
                <?php } } ?>             
              </select>
            </div>
          </div>
          <div class="form-group">
            <label class="col-sm-2 control-label" for="input-geo-zone"><span data-toggle="tooltip" title="<?php echo $help_geo_zone; ?>"><?php echo $entry_geo_zone; ?></span></label>
            <div class="col-sm-10">
              <select name="bambora_online_checkout_geo_zone" id="input-geo-zone" class="form-control">
                <option value="0"><?php echo $text_all_zones; ?></option>
                
                <?php foreach($geo_zones as $geo_zone) { 
                if($geo_zone['geo_zone_id'] == $bambora_online_checkout_geo_zone) {?>
                
                <option value="<?php echo $geo_zone['geo_zone_id']; ?>" selected="selected"><?php echo $geo_zone['name']; ?></option>
                
                <?php }else { ?>
                
                <option value="<?php echo $geo_zone['geo_zone_id']; ?>"><?php echo $geo_zone['name']; ?></option>
                
                  <?php } } ?>
              
              </select>
            </div>
          </div>
        <div class="form-group">
            <label class="col-sm-2 control-label" for="input-sort-order"><?php echo $entry_sort_order; ?></label>
            <div class="col-sm-10">
              <input type="text" name="bambora_online_checkout_sort_order" value="<?php echo $bambora_online_checkout_sort_order; ?>" placeholder="<?php echo $entry_sort_order; ?>" id="input-sort-order" class="form-control" />
            </div>
          </div>
            <div class="form-group">
                <label class="col-sm-2 control-label" for="input-allow_low_value_exemptions"><span data-toggle="tooltip" title="<?php echo $help_allow_low_value_exemptions; ?>"><?php echo $entry_allow_low_value_exemptions; ?></span></label>
                    <select name="bambora_online_checkout_allow_low_value_exemptions" id="input-allow_low_value_exemptions" class="form-control">

                        <?php if ($bambora_online_checkout_allow_low_value_exemptions) { ?>

                        <option value="1" selected="selected"><?php echo $text_enabled; ?></option>
                        <option value="0"><?php echo $text_disabled; ?></option>

                        <?php }else { ?>

                        <option value="1"><?php echo $text_enabled; ?></option>
                        <option value="0" selected="selected"><?php echo $text_disabled; ?></option>

                        <?php } ?>

                    </select>
                </div>
            </div>
            <div class="form-group">
                <label class="col-sm-2 control-label" for="input-limit_for_low_value_exemption"><span data-toggle="tooltip" title="<?php echo $help_limit_for_low_value_exemption; ?>"><?php echo $entry_limit_for_low_value_exemption; ?></span></label>
                <div class="col-sm-10">
                    <input type="text" name="bambora_online_checkout_limit_for_low_value_exemption" value="<?php echo $bambora_online_checkout_limit_for_low_value_exemption; ?>" placeholder="<?php echo $entry_limit_for_low_value_exemption; ?>" id="input-limit_for_low_value_exemption" class="form-control" />
                </div>
                <?php if ($error_limit_for_low_value_exemption) { ?>
                 <div class="text-danger"><?php echo $error_limit_for_low_value_exemption; ?></div>
                <?php } ?>
                <div class="col-sm-12">
                    <a href="https://developer.bambora.com/europe/checkout/psd2/lowvalueexemption"  target="_blank">More information regarding Low Value Exemption here.</a>
                </div>
            </div>

        </form>
      </div>
    </div>
  </div>
</div>
<?php echo $footer; ?>
