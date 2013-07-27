var create_name = '';
function check() {
    var v = $F('create_name');
    if (v == create_name) return;
    create_name = v;
    var parameters = $('create').serialize(true);
    $('check_true').hide();
    if (create_name.length < 4) {
        $('check_false').show();
        return;
    }
    $('check_false').hide();
    new Ajax.Request('/json/'+create_name, {
        parameters: parameters,
        onComplete: function(r) {
            $('check_'+r.responseJSON.available).show();
            $('create_name').enable();
            if (r.responseJSON.available) {
                $('create').enable();
            }
            $('create_name').focus();
        }
    });
}
$('create_name').observe('keyup', function(e) { check(); });
$('create').reset();
$('create').disable();
$('create_name').enable();
$('create_name').focus();
