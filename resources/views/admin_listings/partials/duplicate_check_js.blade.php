{{-- Live duplicate check for the listing form: website and name must be unique across current listings.
     $submit_ids: ids of buttons to disable while a duplicate is shown. --}}
<script>
    (function() {
        const form = document.getElementById('listingForm');
        const fields = ['business_website', 'business_name', 'business_email'].map(id => document.getElementById(id));
        const buttons = @json($submit_ids ?? ['listingSubmit']).map(id => document.getElementById(id)).filter(Boolean);
        let timer = null;

        function hint(key, html) {
            const box = form.querySelector('.dup-hint[data-for="' + key + '"]');
            box.hidden = !html;
            box.innerHTML = html || '';
        }

        function escape(text) {
            return $('<div>').text(text || '').html();
        }

        function check() {
            $.post(@json(route('business_check')), {
                _token: @json(csrf_token()),
                business_website: fields[0].value,
                business_name: fields[1].value,
                business_email: fields[2].value
            }).done(function(res) {
                hint('website', res.website ? '<i class="bi bi-exclamation-circle"></i> Already listed as <a href="' + res.website.url + '" target="_blank">' + escape(res.website.name) + '</a>' : '');
                hint('name', res.name ? '<i class="bi bi-exclamation-circle"></i> Name already used by <a href="' + res.name.url + '" target="_blank">' + escape(res.name.name) + '</a> (' + escape(res.name.website || 'no website') + ')' : '');
                hint('email', res.email ? '<i class="bi bi-info-circle"></i> ' + res.email + ' other business' + (res.email > 1 ? 'es use' : ' uses') + ' this email. It will be linked to the same owner account.' : '');
                buttons.forEach(b => b.disabled = !!res.website || !!res.name);
            });
        }

        fields.forEach(function(field) {
            field.addEventListener('input', function() {
                clearTimeout(timer);
                timer = setTimeout(check, 450);
            });
        });
        if (fields[0].value || fields[1].value) {
            check();
        }
    })();
</script>
