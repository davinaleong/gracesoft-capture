@extends('layouts.embed', ['title' => $form->name])

@section('content')
	<x-form.wrapper :title="$form->name" description="Fill out the form below and we will get back to you soon.">
		@if (session('status'))
			<x-form.success-state :message="session('status')" />
		@endif

		@if ($errors->any())
			<x-form.error-state />
		@endif

		<form action="{{ route('forms.submit', $form->public_token) }}" method="post" novalidate>
			@csrf

			<div class="sr-only" aria-hidden="true">
				<label for="website">Leave this field empty</label>
				<x-ui.input id="website" name="website" tabindex="-1" autocomplete="off" />
			</div>

			<div class="grid grid-cols-1 gap-4 md:grid-cols-2">
				<x-form.input
					id="name"
					name="name"
					label="Full Name"
					type="text"
					autocomplete="name"
					:value="old('name')"
					:required="true"
				/>

				<x-form.input
					id="email"
					name="email"
					label="Email Address"
					type="email"
					autocomplete="email"
					:value="old('email')"
					:required="true"
				/>

				<x-form.input
					id="subject"
					name="subject"
					label="Subject"
					type="text"
					:value="old('subject')"
					:required="true"
					class="md:col-span-2"
				/>

				<x-form.textarea
					id="message"
					name="message"
					label="Message"
					:value="old('message')"
					:rows="5"
					:required="true"
					class="md:col-span-2"
				/>

				<div class="md:col-span-2">
					<x-form.consent-notice />
				</div>

				<div class="md:col-span-2">
					<x-form.button type="submit">Send Message</x-form.button>
				</div>
			</div>
		</form>
	</x-form.wrapper>
@endsection
