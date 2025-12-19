<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>Set Your Password</title>
    <!-- Google Font -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet" />
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: 'Inter', sans-serif;
            background-color: #f3f4f6;
            display: flex;
            align-items: center;
            justify-content: center;
            min-height: 100vh;
            padding: 1rem;
        }

        .form-container {
            width: 450px;
            max-width: 100%;
            background-color: #ffffff;
            border-radius: 1rem;
            box-shadow: 0 10px 15px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            padding: 2rem;
        }

        .form-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .form-header h1 {
            font-size: 1.875rem;
            font-weight: 700;
            color: #1f2937;
            margin-bottom: 0.25rem;
        }

        .form-header p {
            color: #6b7280;
            font-size: 0.95rem;
        }

        form {
            display: flex;
            flex-direction: column;
            gap: 1rem;
        }

        label {
            display: block;
            font-size: 0.875rem;
            font-weight: 500;
            color: #374151;
            margin-bottom: 0.25rem;
        }

        input[type="password"] {
            width: 100%;
            padding: 0.5rem 1rem;
            background-color: #f9fafb;
            border: 1px solid #d1d5db;
            border-radius: 0.5rem;
            font-size: 1rem;
            transition: border-color 0.2s, box-shadow 0.2s;
        }

        input[type="password"]:focus {
            outline: none;
            border-color: #3b82f6;
            box-shadow: 0 0 0 2px rgba(59, 130, 246, 0.4);
        }

        .validation-messages p {
            font-size: 0.875rem;
            color: #6b7280;
            display: flex;
            align-items: center;
        }

        .validation-messages span {
            margin-right: 0.5rem;
        }

        .submit-btn {
            padding: 0.75rem 1rem;
            width: 100%;
            background-color: #2563eb;
            color: white;
            font-weight: 600;
            font-size: 1rem;
            border: none;
            border-radius: 0.5rem;
            cursor: pointer;
            box-shadow: 0 2px 6px rgba(0, 0, 0, 0.1);
            transition: background-color 0.2s;
        }

        .submit-btn:hover {
            background-color: #1d4ed8;
        }

        .submit-btn:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(59, 130, 246, 0.5);
        }

        .message-box.success {
            color: #065f46;
        }

        .message-box.error {
            color: #991b1b;
        }

        #response-message p {
            margin: 4px 0;
            font-size: 13px;
            color: #6B7280;
        }
        .message-box.error {
            color: #991b1b;
        }
    </style>
</head>

<body>
    <div class="form-container">
        <!-- Header -->
        <div class="form-header">
            <h1>Set Your Password</h1>
            <p>Create a new password to activate your account.</p>
        </div>

        <!-- Password Set Form -->
        <form id="set-password-form">
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">
            <div>
                <label for="password">New Password</label>
                <input type="password" id="password" name="password" required />
            </div>
            <div>
                <label for="confirm-password">Confirm Password</label>
                <input type="password" id="password_confirmation" name="password_confirmation" required />
            </div>
            <!-- Message Box for Success or Validation Errors -->
            <div id="response-message" class="message-box" style="display:none;"></div>
            <button type="submit" class="submit-btn">Set Password</button>
        </form>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#set-password-form').on('submit', function(e) {
                e.preventDefault();

                let form = $(this);
                let formData = form.serialize();

                // Clear previous messages
                $('#response-message').hide().removeClass('success error').html('');

                $.ajax({
                    url: '{{ route("set_password_action") }}',
                    method: 'POST',
                    data: formData,
                    headers: {
                        'X-CSRF-TOKEN': $('input[name="_token"]').val()
                    },
                    success: function(response) {
                        $('#response-message')
                            .removeClass('error')
                            .addClass('success')
                            .html('<p>✅ ' + response.message + '</p>')
                            .fadeIn();

                        form[0].reset();

                        setTimeout(function() {
                            window.location.href = '{{ route("login") }}';
                        }, 3000);
                    },
                    error: function(xhr) {
                        if (xhr.status === 422) {
                            const errors = xhr.responseJSON.errors;
                            let errorHtml = '';
                            $.each(errors, function(key, value) {
                                errorHtml += '<p>❌ ' + value[0] + '</p>';
                            });

                            $('#response-message')
                                .removeClass('success')
                                .addClass('error')
                                .html(errorHtml)
                                .fadeIn();
                        } else {
                            $('#response-message')
                                .removeClass('success')
                                .addClass('error')
                                .html('<p>❌ An unexpected error occurred.</p>')
                                .fadeIn();
                        }
                    }
                });
            });
        });
    </script>


</body>

</html>