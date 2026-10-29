    </main>
    <footer class="app-footer">
        <span>&copy; {{ date('Y') }} <a href="https://www.worldweb-directory.com/" target="_blank" rel="noopener">World Web Directory</a></span>
        <span>Developed by Santanu Manna</span>
    </footer>
</div>{{-- .app-main --}}
</div>{{-- .app --}}

<script src="/assets/vendors/popper/popper.min.js"></script>
<script src="/assets/vendors/bootstrap/bootstrap.min.js"></script>
<script src="/assets/vendors/jquery/jquery.min.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.3/jquery.validate.min.js"></script>
<script src="/assets/js/custom.js"></script>

<script>
    // Sidebar: icon rail on desktop (remembered), slide-in panel on smaller screens
    (function() {
        var root = document.documentElement;
        document.getElementById('sidebarToggle').addEventListener('click', function() {
            if (window.matchMedia('(min-width: 1200px)').matches) {
                var collapsed = root.classList.toggle('sidebar-collapsed');
                try {
                    localStorage.setItem('wwdSidebarCollapsed', collapsed ? '1' : '0');
                } catch (e) {}
            } else {
                root.classList.toggle('sidebar-open');
            }
        });
        document.getElementById('appBackdrop').addEventListener('click', function() {
            root.classList.remove('sidebar-open');
        });
    })();

    $.validator.addMethod("customEmail", function(value, element) {
        return this.optional(element) || /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.(com|in|co\.in|net)$/i.test(value);
    }, "Please enter a valid email address.");

    $.validator.addMethod("noStartingSpaceWithMinLength", function(value, element) {
        return this.optional(element) || /^[^\s]{4,}.*$/.test(value);
    }, "Input should not start with a space and must contain at least four characters before any space.");

    // Format (123) 456-7890 on phone/fax inputs, where a page has them
    ['mobileNumber', 'faxNumber'].forEach(function(id) {
        var input = document.getElementById(id);
        if (!input) {
            return;
        }
        input.addEventListener('input', function(e) {
            var value = e.target.value.replace(/\D/g, '');
            if (value.length > 0) {
                value = value.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3');
            }
            e.target.value = value;
        });
    });

    $(document).ready(function() {
        $('#changePasswordForm').on('submit', function(e) {
            e.preventDefault();

            $('#old_password_error').text('');
            $('#password_error').text('');
            $('#password_confirmation_error').text('');
            $('#old_password_match_error').text('');

            $.ajax({
                url: "{{ route('change_password') }}",
                type: 'POST',
                data: $(this).serialize(),
                success: function(response) {
                    if (response.success) {
                        $('#exampleModal').modal('hide');
                        window.location.href = "{{ route('login') }}";
                    }
                },
                error: function(xhr) {
                    if (xhr.status === 422) {
                        let response = xhr.responseJSON;

                        if (response.errors) {
                            if (response.errors.old_password) {
                                $('#old_password_error').text(response.errors.old_password[0]);
                            }
                            if (response.errors.password) {
                                $('#password_error').text(response.errors.password[0]);
                            }
                            if (response.errors.password_confirmation) {
                                $('#password_confirmation_error').text(response.errors.password_confirmation[0]);
                            }
                        }

                        if (response.message === 'Old Password does not match, Please try again.') {
                            $('#old_password_match_error').text(response.message);
                        }
                    } else {
                        alert('Something went wrong. Please try again.');
                    }
                }
            });
        });
    });
</script>
@stack('vendor_scripts')
@yield('scripts')
@stack('scripts')
</body>

</html>
