<!doctype html>
<html lang="en">
<head>
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1.0">
	<title>Contact Form</title>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600&display=swap" rel="stylesheet">
	<style>
		:root {
			--bg: #f5f7fb;
			--card: #ffffff;
			--text: #1f2937;
			--muted: #6b7280;
			--line: #d1d5db;
			--accent: #0f766e;
		}

		* {
			box-sizing: border-box;
		}

		body {
			margin: 0;
			font-family: "Montserrat", sans-serif;
			background: linear-gradient(160deg, #eef3ff 0%, var(--bg) 100%);
			color: var(--text);
			min-height: 100vh;
			display: grid;
			place-items: center;
			padding: 2rem 1rem;
		}

		.form-shell {
			width: 100%;
			max-width: 680px;
			background: var(--card);
			border: 1px solid #e5e7eb;
			border-radius: 14px;
			padding: 1.5rem;
			box-shadow: 0 10px 24px rgba(15, 23, 42, 0.08);
		}

		h1 {
			margin-top: 0;
			margin-bottom: 0.5rem;
			font-size: 1.5rem;
			font-weight: 600;
		}

		p {
			margin-top: 0;
			margin-bottom: 1.25rem;
			color: var(--muted);
		}

		.grid {
			display: grid;
			gap: 1rem;
			grid-template-columns: repeat(2, minmax(0, 1fr));
		}

		.field {
			display: flex;
			flex-direction: column;
			gap: 0.4rem;
		}

		.field.full {
			grid-column: 1 / -1;
		}

		label {
			font-size: 0.92rem;
			font-weight: 600;
		}

		input,
		textarea {
			width: 100%;
			border: 1px solid var(--line);
			border-radius: 10px;
			padding: 0.75rem 0.85rem;
			font: inherit;
			color: var(--text);
			background-color: #fff;
		}

		textarea {
			min-height: 140px;
			resize: vertical;
		}

		input:focus,
		textarea:focus {
			outline: 2px solid rgba(15, 118, 110, 0.2);
			border-color: var(--accent);
		}

		button {
			border: 0;
			border-radius: 10px;
			padding: 0.8rem 1.15rem;
			font: inherit;
			font-weight: 600;
			color: #fff;
			background: var(--accent);
			cursor: pointer;
		}

		.alert {
			padding: 0.75rem 0.9rem;
			border-radius: 10px;
			margin-bottom: 1rem;
			font-size: 0.9rem;
		}

		.alert.success {
			background: #ecfdf5;
			border: 1px solid #10b981;
			color: #065f46;
		}

		.alert.error {
			background: #fef2f2;
			border: 1px solid #ef4444;
			color: #991b1b;
		}

		.honeypot {
			position: absolute;
			left: -9999px;
			width: 1px;
			height: 1px;
			overflow: hidden;
		}

		@media (max-width: 640px) {
			.grid {
				grid-template-columns: 1fr;
			}
		}
	</style>
</head>
<body>
	<main class="form-shell">
		<h1>{{ $form->name }}</h1>
		<p>Fill out the form below and we will get back to you soon.</p>

		@if (session('status'))
			<div class="alert success">{{ session('status') }}</div>
		@endif

		@if ($errors->any())
			<div class="alert error">Please check your input and try again.</div>
		@endif

		<form action="{{ route('forms.submit', $form->public_token) }}" method="post" novalidate>
			@csrf

			<div class="honeypot" aria-hidden="true">
				<label for="website">Leave this field empty</label>
				<input type="text" id="website" name="website" tabindex="-1" autocomplete="off">
			</div>

			<div class="grid">
				<div class="field">
					<label for="name">Full Name</label>
					<input type="text" id="name" name="name" autocomplete="name" value="{{ old('name') }}" required>
				</div>

				<div class="field">
					<label for="email">Email Address</label>
					<input type="email" id="email" name="email" autocomplete="email" value="{{ old('email') }}" required>
				</div>

				<div class="field full">
					<label for="subject">Subject</label>
					<input type="text" id="subject" name="subject" value="{{ old('subject') }}" required>
				</div>

				<div class="field full">
					<label for="message">Message</label>
					<textarea id="message" name="message" required>{{ old('message') }}</textarea>
				</div>

				<div class="field full">
					<button type="submit">Send Message</button>
				</div>
			</div>
		</form>
	</main>
</body>
</html>
