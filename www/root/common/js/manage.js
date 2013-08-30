/*
 *  Copyright (C) 2013 RWW.IO
 *  
 *  Permission is hereby granted, free of charge, to any person obtaining a copy
 *  of this software and associated documentation files (the "Software"), to deal 
 *  in the Software without restriction, including without limitation the rights 
 *  to use, copy, modify, merge, publish, distribute, sublicense, and/or sell 
 *  copies of the Software, and to permit persons to whom the Software is furnished 
 *  to do so, subject to the following conditions:

 *  The above copyright notice and this permission notice shall be included in all 
 *  copies or substantial portions of the Software.

 *  THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED, 
 *  INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR A 
 *  PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT 
 *  HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION 
 *  OF CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE 
 *  SOFTWARE OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
 
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
