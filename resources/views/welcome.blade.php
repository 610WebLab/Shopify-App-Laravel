<!DOCTYPE html>
<html lang="{{ app()->getLocale() }}">
<head>
	<meta charset="utf-8">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<title>ShipGrow</title>
    
    <script>
        var Config = {
            shop: "{{$shop}}",
            apiKey: "{{$apiKey}}",
            host: new URLSearchParams(location.search).get("host"),
            forceRedirect: true,
            shopOrigin:`https://{{$shop}}`,
            appUrl:"{{$appUrl}}",
            csrf_token: '<?php echo csrf_token(); ?>'
        };
    </script>
        @viteReactRefresh
        @vite('resources/js/app.jsx')
</head>
<body>
	<div id="root"></div>
        <!-- <script>
        const AppBridge = window['app-bridge'];
        var Config = {
            shop: "{{$shop}}",
            apiKey: '{{$apiKey}}',
            shopOrigin: `{{$shop}}`,
            host: new URLSearchParams(location.search).get("host"),
            csrf_token: ''
        };
        const app = AppBridge.createApp(Config);

        window.shop = "{{ $shop }}"
        
    </script> -->
        
</body>
</html>