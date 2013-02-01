@layout('layouts/default')

@section('pagetitle') Form @endsection

@section('main')
	<h3><a href="/">&larr; Back</a></h3>

	{{ Form::open() }}
		@foreach($fields as $field)

			{{ AutoSchema\AutoForm::field('bills', $field['name'])->form_field() }}

		@endforeach
	{{ Form::submit('save') }}
	{{ Form::close() }}

@endsection