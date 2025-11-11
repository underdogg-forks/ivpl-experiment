<script>
    var delay = (function () {
        var timer = 0;
        return function (callback, ms) {
            clearTimeout(timer);
            timer = setTimeout(callback, ms);
        };
    })();

    $(function () {
        $('#filter').keyup(function () {
            delay(function () {
                ajaxPost('<?php echo site_url('filter/ajax/' . $filter_method); ?>', {
                    filter_query: $('#filter').val()
                }).done(function (data) {
                    <?php echo IP_DEBUG ? 'console.log(data);' : ''; ?>
                    $('#filter_results').html(data.html);
                }).fail(function (errors) {
                    // Errors are automatically displayed by ajaxPost
                });
            }, 1000);
        });
    });
</script>