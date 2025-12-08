<!DOCTYPE html>
<html>
<head>
    <title>Processing Payment...</title>
    <script>
    window.onload = function() {

        const status = "{{ $status ?? 'success' }}";  // pass from controller

        if (window.opener) {
            window.opener.postMessage({ payu: status }, "*");
            window.opener.location.reload();
        }

        window.close();
    }
    </script>
</head>
<body>
    <p style="font-family: sans-serif; padding:20px;">
        Finishing Payment... You can close this window if it doesn't close automatically.
    </p>
</body>
</html>
