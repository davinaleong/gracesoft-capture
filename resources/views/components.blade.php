<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>{{ config('app.name', 'Laravel') }} - Components</title>
	<link rel="icon" type="image/svg+xml" href="{{ asset('logo.svg') }}">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
	@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
	<main class="container mx-auto p-4 space-y-4">
		<section class="space-y-4">
			<header class="space-y-2">
				<h1 class="text-4xl font-bold">Components</h1>
				<p>All components used by the application are listed below.</p>
			</header>

			<h2 class="text-2xl font-bold">Card</h2>
			<div class="bg-white border border-gray-300 rounded p-4 shadow">
				<p>Lorem ipsum dolor sit amet, consectetur adipiscing elit.</p>
			</div>
		</section>
	</main>
</body>
</html>
