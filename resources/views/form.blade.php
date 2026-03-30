<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>{{ $form->name }}</title>
	<link rel="icon" type="image/svg+xml" href="{{ asset('logo.svg') }}">
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
	@vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="bg-gs-black-50 text-gs-black-900 min-h-screen py-8 px-4">
	<main class="mx-auto w-full max-w-3xl">
		<x-ui.card class="space-y-4">
			<h1 class="text-2xl font-bold">{{ $form->name }}</h1>
			<p class="text-gs-black-700">Fill out the form below and we will get back to you soon.</p>

			@if (session('status'))
				<x-ui.alert variant="success">{{ session('status') }}</x-ui.alert>
			@endif

			@if ($errors->any())
				<x-ui.alert variant="error">Please check your input and try again.</x-ui.alert>
			@endif

			<form action="{{ route('forms.submit', $form->public_token) }}" method="post" novalidate>
				@csrf

				<div class="sr-only" aria-hidden="true">
					<label for="website">Leave this field empty</label>
					<x-ui.input id="website" name="website" tabindex="-1" autocomplete="off" />
				</div>

				<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
					<x-ui.field for="name" label="Full Name" required>
						<x-ui.input type="text" id="name" name="name" autocomplete="name" :value="old('name')" required />
					</x-ui.field>

					<x-ui.field for="email" label="Email Address" required>
						<x-ui.input type="email" id="email" name="email" autocomplete="email" :value="old('email')" required />
					</x-ui.field>

					<x-ui.field for="subject" label="Subject" required class="md:col-span-2">
						<x-ui.input type="text" id="subject" name="subject" :value="old('subject')" required />
					</x-ui.field>

					<x-ui.field for="message" label="Message" required class="md:col-span-2">
						<x-ui.textarea id="message" name="message" rows="5" required>{{ old('message') }}</x-ui.textarea>
					</x-ui.field>

					<div class="md:col-span-2">
						<label class="flex items-start gap-2 text-sm text-gs-black-800" for="consent_accepted">
							<input
								id="consent_accepted"
								type="checkbox"
								name="consent_accepted"
								value="1"
								@checked(old('consent_accepted'))
								class="mt-1 h-4 w-4 rounded border-gray-300"
							>
							<span>I agree to the privacy notice and consent to processing my enquiry data.</span>
						</label>
					</div>

					<div class="md:col-span-2">
						<x-ui.button type="submit">Send Message</x-ui.button>
					</div>
				</div>
			</form>
		</x-ui.card>
	</main>
</body>
</html>
