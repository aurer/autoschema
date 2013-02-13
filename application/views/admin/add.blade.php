@layout('layouts/default')

@section('pagetitle') Form @endsection

@section('main')
<div class="cell">
	<h3><a href="/admin">&larr; Back</a></h3>

	{{ HTML::ol( $errors->all() ) }}

	{{ Form::open("/admin/$table/add") }}
		@foreach($fields as $field)

			{{ AutoSchema\AutoForm::field($table, $field['name'])->form_field( Input::old( $field['name'], $field['default']) ) }}

		@endforeach
	{{ Form::submit('save') }}
	{{ Form::close() }}

</div>
@endsection