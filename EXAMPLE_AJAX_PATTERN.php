<!-- 
    EXAMPLE: Payment Modal with New AJAX Pattern
    
    This is a reference implementation showing how to use the new ajaxPost() pattern
    for AJAX calls in InvoicePlane modals.
    
    Location: application/Modules/Payments/views/modal_add_payment_NEW_PATTERN.php
    Original: application/Modules/Payments/views/modal_add_payment.php
-->

<script>
    $(function () {
        $('#enter-payment').modal('show');

        $('#enter-payment').on('shown', function () {
            $('#payment_amount').focus();
        });

        // Select2 for all select inputs
        $(".simple-select").select2();

        // NEW PATTERN: Using ajaxPost() with promise-based API
        $('#btn_modal_payment_submit').click(function () {
            var $btn = $(this);
            
            // Use ajaxPost with the new pattern
            ajaxPost("<?php echo site_url('payments/ajax/add'); ?>", {
                invoice_id: $('#invoice_id').val(),
                payment_amount: $('#payment_amount').val(),
                payment_method_id: $('#payment_method_id').val(),
                payment_date: $('#payment_date').val(),
                payment_note: $('#payment_note').val()
            }, {
                // Optional: Disable button during request
                beforeSend: function() {
                    $btn.prop('disabled', true);
                },
                // Optional: Re-enable button when done
                always: function() {
                    $btn.prop('disabled', false);
                }
            }).done(function(response) {
                // Success! The response has already been validated
                if ($('#payment_cf_exist').val() === 'yes') {
                    // There are payment custom fields, display the payment form
                    window.location = "<?php echo site_url('payments/form'); ?>/" + response.payment_id;
                } else {
                    // There are no payment custom fields, return to invoice view
                    window.location = "<?php echo $_SERVER['HTTP_REFERER']; ?>";
                }
            }).fail(function(errors) {
                // Errors are automatically displayed by showErrors()
                // You can add custom error handling here if needed
                console.log('Payment submission failed:', errors);
            });
        });

        /* 
         * OLD PATTERN - For comparison (this is the original code)
         * 
         * $('#btn_modal_payment_submit').click(function () {
         *     $.post("<?php echo site_url('payments/ajax/add'); ?>", {
         *             invoice_id: $('#invoice_id').val(),
         *             payment_amount: $('#payment_amount').val(),
         *             payment_method_id: $('#payment_method_id').val(),
         *             payment_date: $('#payment_date').val(),
         *             payment_note: $('#payment_note').val()
         *         },
         *         function (data) {
         *             var response = json_parse(data, <?php echo (int) IP_DEBUG; ?>);
         *             if (response.success === 1) {
         *                 if ($('#payment_cf_exist').val() === 'yes') {
         *                     window.location = "<?php echo site_url('payments/form'); ?>/" + response.payment_id;
         *                 }
         *                 else {
         *                     window.location = "<?php echo $_SERVER['HTTP_REFERER']; ?>";
         *                 }
         *             }
         *             else {
         *                 // Manual error handling
         *                 $('.control-group').removeClass('has-error');
         *                 for (var key in response.validation_errors) {
         *                     if(response.validation_errors.hasOwnProperty(key)) {
         *                         $('#' + key).parent().parent().addClass('has-error');
         *                     }
         *                 }
         *             }
         *         });
         * });
         */
    });
</script>

<div id="enter-payment" class="modal col-xs-12 col-sm-10 col-sm-offset-1 col-md-8 col-md-offset-2"
     role="dialog" aria-labelledby="modal_enter_payment" aria-hidden="true">
    <div class="modal-content">
        <div class="modal-header">
            <a data-dismiss="modal" class="close"><i class="fa fa-close"></i></a>
            <h3><?php _trans('enter_payment'); ?></h3>
        </div>

        <div class="modal-body">
            <!-- Optional: Add a placeholder for error messages -->
            <div id="modal-status-placeholder"></div>
            
            <form>
                <input type="hidden" name="invoice_id" id="invoice_id" value="<?php echo $invoice_id; ?>">

                <div class="form-group">
                    <label for="payment_amount"><?php _trans('amount'); ?></label>
                    <div class="controls">
                        <input type="text" name="payment_amount" id="payment_amount" class="form-control"
                               value="<?php echo isset($invoice_balance) ? format_amount($invoice_balance) : ''; ?>">
                    </div>
                </div>

                <div class="form-group has-feedback">
                    <label class="payment_date"><?php _trans('payment_date'); ?></label>
                    <div class="input-group">
                        <input name="payment_date" id="payment_date"
                               class="form-control datepicker"
                               value="<?php echo date(date_format_setting()); ?>">
                        <span class="input-group-addon">
                            <i class="fa fa-calendar fa-fw"></i>
                        </span>
                    </div>
                </div>

                <div class="form-group">
                    <label for="payment_method_id"><?php _trans('payment_method'); ?></label>
                    <div class="controls">
<?php
// Add a hidden input field if a payment method was set to pass the disabled attribute
if ($this->mdl_payments->form_value('payment_method_id')) {
?>
                        <input type="hidden" name="payment_method_id" class="hidden"
                               value="<?php echo $this->mdl_payments->form_value('payment_method_id'); ?>">
<?php
}
?>
                        <select name="payment_method_id" id="payment_method_id" class="form-control simple-select"
                                <?php echo empty($invoice_payment_method) ? '' : 'disabled="disabled"'; ?>>
                            <option value=""><?php _trans('none'); ?></option>
<?php
foreach ($payment_methods as $payment_method) {
?>
                            <option value="<?php echo $payment_method->payment_method_id; ?>"
                                    <?php check_select(isset($invoice_payment_method) && $invoice_payment_method == $payment_method->payment_method_id); ?>>
                                <?php _htmlsc($payment_method->payment_method_name); ?>
                            </option>
<?php
} // End foreach
?>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="payment_note"><?php _trans('note'); ?></label>
                    <div class="controls">
                        <textarea name="payment_note" id="payment_note" class="form-control"></textarea>
                    </div>
                </div>

                <input type="hidden" name="payment_cf_exist" id="payment_cf_exist" value="<?php echo $payment_cf_exist; ?>">
            </form>
        </div>

        <div class="modal-footer">
            <div class="btn-group">
                <button class="btn btn-success" id="btn_modal_payment_submit" type="button">
                    <i class="fa fa-check"></i>
                    <?php _trans('submit'); ?>
                </button>
                <button class="btn btn-danger" type="button" data-dismiss="modal">
                    <i class="fa fa-times"></i>
                    <?php _trans('cancel'); ?>
                </button>
            </div>
        </div>
    </div>
</div>
