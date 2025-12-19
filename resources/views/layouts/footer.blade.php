<script>
    var navbarPosition = localStorage.getItem('navbarPosition');
    var navbarVertical = document.querySelector('.navbar-vertical');
    var navbarTopVertical = document.querySelector('.content .navbar-top');
    var navbarTop = document.querySelector('[data-layout] .navbar-top:not([data-double-top-nav');
    var navbarDoubleTop = document.querySelector('[data-double-top-nav]');
    var navbarTopCombo = document.querySelector('.content [data-navbar-top="combo"]');

    if (localStorage.getItem('navbarPosition') === 'double-top') {
        document.documentElement.classList.toggle('double-top-nav-layout');
    }

    if (navbarPosition === 'top') {
        navbarTop.removeAttribute('style');
        navbarTopVertical.remove(navbarTopVertical);
        navbarVertical.remove(navbarVertical);
        navbarTopCombo.remove(navbarTopCombo);
        navbarDoubleTop.remove(navbarDoubleTop);
    } else if (navbarPosition === 'combo') {
        navbarVertical.removeAttribute('style');
        navbarTopCombo.removeAttribute('style');
        navbarTop.remove(navbarTop);
        navbarTopVertical.remove(navbarTopVertical);
        navbarDoubleTop.remove(navbarDoubleTop);
    } else if (navbarPosition === 'double-top') {
        navbarDoubleTop.removeAttribute('style');
        navbarTopVertical.remove(navbarTopVertical);
        navbarVertical.remove(navbarVertical);
        navbarTop.remove(navbarTop);
        navbarTopCombo.remove(navbarTopCombo);
    } else {
        navbarVertical.removeAttribute('style');
        navbarTopVertical.removeAttribute('style');
        navbarTop.remove(navbarTop);
        navbarDoubleTop.remove(navbarDoubleTop);
        navbarTopCombo.remove(navbarTopCombo);
    }
</script>
<footer class="footer">
    <div class="row g-0 justify-content-between fs-10 mt-4 mb-3">
        <div class="col-12 col-sm-auto text-center">
            <p class="mb-0 text-600">Developed By <span class="d-none d-sm-inline-block">
                </span><a href="javascript:void(0);" target="_blank">Santanu Manna</a></p>
        </div>
        <div class="col-12 col-sm-auto text-center">
            <p class="mb-0 text-600">
                <span class="d-none d-sm-inline-block">
                </span><br class="d-sm-none" />&copy; {{ date('Y') }}
                <a href="https://www.worldweb-directory.com/" target="_blank"> World Web Directory
                </a>
            </p>
        </div>
    </div>
</footer>
</div>
<div class="modal fade" id="authentication-modal" tabindex="-1" role="dialog"
    aria-labelledby="authentication-modal-label" aria-hidden="true">
    <div class="modal-dialog mt-6" role="document">
        <div class="modal-content border-0">
            <div class="modal-header px-5 position-relative modal-shape-header bg-shape">
                <div class="position-relative z-1">
                    <h4 class="mb-0 text-white" id="authentication-modal-label">Register</h4>
                    <p class="fs-10 mb-0 text-white">Please create your free Falcon account</p>
                </div><button class="btn-close position-absolute top-0 end-0 mt-2 me-2" data-bs-dismiss="modal"
                    aria-label="Close"></button>
            </div>
            <div class="modal-body py-4 px-5">
                <form>
                    <div class="mb-3"><label class="form-label" for="modal-auth-name">Name</label><input
                            class="form-control" type="text" autocomplete="on" id="modal-auth-name" /></div>
                    <div class="mb-3"><label class="form-label" for="modal-auth-email">Email address</label><input
                            class="form-control" type="email" autocomplete="on" id="modal-auth-email" /></div>
                    <div class="row gx-2">
                        <div class="mb-3 col-sm-6"><label class="form-label"
                                for="modal-auth-password">Password</label><input class="form-control" type="password"
                                autocomplete="on" id="modal-auth-password" /></div>
                        <div class="mb-3 col-sm-6"><label class="form-label" for="modal-auth-confirm-password">Confirm
                                Password</label><input class="form-control" type="password" autocomplete="on"
                                id="modal-auth-confirm-password" /></div>
                    </div>
                    <div class="form-check"><input class="form-check-input" type="checkbox"
                            id="modal-auth-register-checkbox" /><label class="form-label"
                            for="modal-auth-register-checkbox">I accept the <a href="#!">terms </a>and <a
                                class="white-space-nowrap" href="#!">privacy policy</a></label></div>
                    <div class="mb-3"><button class="btn btn-primary d-block w-100 mt-3" type="submit"
                            name="submit">Register</button></div>
                </form>
                <div class="position-relative mt-5">
                    <hr />
                    <div class="divider-content-center">or register with</div>
                </div>
                <div class="row g-2 mt-2">
                    <div class="col-sm-6">
                        <a class="btn btn-outline-google-plus btn-sm d-block w-100" href="#">
                            <span class="fab fa-google-plus-g me-2" data-fa-transform="grow-8"></span>
                            google
                        </a>
                    </div>
                    <div class="col-sm-6"><a class="btn btn-outline-facebook btn-sm d-block w-100" href="#"><span
                                class="fab fa-facebook-square me-2" data-fa-transform="grow-8"></span> facebook</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
</div>
</main>
<!-- ===============================================-->
<!--    JavaScripts-->
<!-- ===============================================-->
<script src="/assets/vendors/choices/choices.min.js"></script>
<script src="/assets/vendors/popper/popper.min.js"></script>
<script src="/assets/vendors/bootstrap/bootstrap.min.js"></script>
<script src="/assets/vendors/anchorjs/anchor.min.js"></script>
<script src="/assets/vendors/is/is.min.js"></script>
<script src="/assets/vendors/echarts/echarts.min.js"></script>
<script src="/assets/vendors/fontawesome/all.min.js"></script>
<script src="/assets/vendors/lodash/lodash.min.js"></script>
<script src="/assets/vendors/list.js/list.min.js"></script>
<script src="/assets/js/theme.js"></script>
{{-- //Datatable  --}}
<script src="/assets/vendors/jquery/jquery.min.js"></script>
<script src="/assets/vendors/datatables.net/jquery.dataTables.min.js"></script>
<script src="/assets/vendors/datatables.net-bs5/dataTables.bootstrap5.min.js"></script>
<script src="/assets/vendors/datatables.net-fixedcolumns/dataTables.fixedColumns.min.js"></script>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>

{{-- //date picker --}}
<script src="/assets/js/flatpickr.js"></script>
<script src="https://cdnjs.cloudflare.com/ajax/libs/jquery-validate/1.19.3/jquery.validate.min.js"></script>

{{-- Cunstom JS --}}
<script src="/assets/js/custom.js"></script>

<script>
    $.validator.addMethod("customEmail", function(value, element) {
        return this.optional(element) || /^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.(com|in|co\.in|net)$/i.test(value);
    }, "Please enter a valid email address.");

    $.validator.addMethod("noStartingSpaceWithMinLength", function(value, element) {
        return this.optional(element) || /^[^\s]{4,}.*$/.test(value);
    }, "Input should not start with a space and must contain at least four characters before any space.");


    $(document).ready(function() {
        $("#add_complaint_form").validate({
            rules: {
                name: {
                    required: true,
                    minlength: 6,
                    noStartingSpaceWithMinLength: true
                },
                number: {
                    required: true,
                    maxlength: 14,
                    minlength: 10
                },
                fax: {
                    maxlength: 14,
                    minlength: 10
                },
                email: {
                    required: true,
                    email: true,
                    customEmail: true
                },
                address: {
                    maxlength: 50,
                    minlength: 5
                },
                lot_number: {
                    min: 1,
                    minlength: 10,
                    maxlength: 10,
                }
            },
            messages: {
                name: {
                    required: "Please enter a name.",
                    minlength: "Please enter more than 6 characters.",
                    noStartingSpaceWithMinLength: "Please insert a valide name.",
                },
                number: {
                    required: "Please enter a number.",
                    maxlength: "Please enter no more than 10 characters.",
                    minlength: "Please enter at least 10 characters."
                },
                fax: {
                    maxlength: "Please enter no more than 10 characters.",
                    minlength: "Please enter at least 10 characters."
                },
                email: {
                    required: "Please enter an email address.",
                    email: "Please enter a valid email address.",
                    customEmail: "Please enter a valid email address."
                },
                address: {
                    maxlength: "Please enter no more than 50 characters.",
                    minlength: "Please enter at least 5 characters."
                },
                lot_number: {
                    min: "The value should be in negetiv or Zero.",
                    minlength: "Please enter 10 Number.",
                    maxlength: "Please enter 10 Number.",
                }
            },
            // submitHandler: function(form) {
            //     const fileInput = document.getElementById('files');
            //     const files = fileInput.files;
            //     const maxFiles = 10;
            //     const maxSize = 10 * 1024 * 1024;

            //     if (files.length > maxFiles) {
            //         alert("You can upload a maximum of 10 files.");
            //         return false;
            //     }

            //     for (let i = 0; i < files.length; i++) {
            //         if (files[i].size > maxSize) {
            //             alert("Each file should be less than 10 MB.");
            //             return false;
            //         }
            //     }
            // }
        });
    });
</script>
<script>
    document.getElementById('mobileNumber').addEventListener('input', function(e) {
        let inputValue = e.target.value.replace(/\D/g, '');
        if (inputValue.length > 0) {
            inputValue = inputValue.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3');
        }
        e.target.value = inputValue;
    });

    document.getElementById('faxNumber').addEventListener('input', function(e) {
        let inputValue = e.target.value.replace(/\D/g, '');
        if (inputValue.length > 0) {
            inputValue = inputValue.replace(/(\d{3})(\d{3})(\d{4})/, '($1) $2-$3');
        }
        e.target.value = inputValue;
    });
</script>
<script>
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
                                $('#old_password_error').text(response.errors.old_password[
                                    0]);
                            }
                            if (response.errors.password) {
                                $('#password_error').text(response.errors.password[0]);
                            }
                            if (response.errors.password_confirmation) {
                                $('#password_confirmation_error').text(response.errors
                                    .password_confirmation[0]);
                            }
                        }

                        if (response.message ===
                            'Old Password does not match, Please try again.') {
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
<script>
    const profile_input = document.getElementById('profile_pic');
    const profile_preview = document.getElementById('profile_preview');
    const remove_btn = document.getElementById('removeImage');

    profile_preview.addEventListener('click', () => {
        profile_input.click();
    });

    profile_input.addEventListener('change', function () {
        const file = this.files[0];
        if (file) {
            const reader = new FileReader();
            reader.onload = function (e) {
                profile_preview.innerHTML = `<img src="${e.target.result}" alt="Profile Image">`;
                remove_btn.classList.remove('d-none');
            };
            reader.readAsDataURL(file);
        }
    });

    remove_btn.addEventListener('click', () => {
        profile_input.value = '';
        @if(Auth::user()->profile_pic)
            profile_preview.innerHTML = `<img src="{{ asset(Auth::user()->profile_pic) }}" alt="Profile Image">`;
        @else
            profile_preview.innerHTML = '<span>Select Image</span>';
        @endif
        remove_btn.classList.add('d-none');
    });
</script>
@yield('scripts')
@stack('scripts')
</body>

</html>