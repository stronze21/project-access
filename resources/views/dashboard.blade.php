<!DOCTYPE html>
<html>

<head>
    <title>QR Scanner Test</title>
    <script src="https://unpkg.com/html5-qrcode" type="text/javascript"></script>
</head>

<body>
    <div id="qr-reader" style="width: 500px; height: 500px;"></div>
    <div id="result"></div>

    <script>
        const html5QrCode = new Html5Qrcode("qr-reader");
        html5QrCode.start({
                facingMode: "environment"
            }, {
                fps: 10,
                qrbox: 250
            },
            (decodedText) => {
                document.getElementById("result").innerHTML =
                    `<p>Detected: ${decodedText}</p>`;
            },
            (err) => console.error(err)
        );
    </script>
</body>

</html>
