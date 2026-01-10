<!DOCTYPE html>
<html>
<head>
    <title>Order Confirmation OTP</title>
</head>
<body>
    <h1>Hello, {{ $userName }}</h1>
    <p>Thank you for booking <strong>{{ $serviceName }}</strong>.</p>
    <p>Your Order Completion OTP is:</p>
    <h2 style="color: blue;">{{ $otp }}</h2>
    <p>Please share this OTP with the staff member only after the service is completed.</p>
    <br>
    <p>Thank you!</p>
</body>
</html>