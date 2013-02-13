@layout('layouts/default')

@section('pagetitle') Form @endsection

@section('main')
<div class="cell">
	<h3><a href="/admin/{{ $table }}">&larr; Back</a></h3>

	@if( count($errors->all()) > 0 )
		<p>There were {{ count($errors->all()) }} errors found, please check the form.</p>
	@endif

	{{ Form::open("/admin/$table/$id/update") }}
		@foreach($fields as $field)

			{{ AutoSchema\AutoForm::field($table, $field['name'])->form_field( $item->$field['name'], Input::old( $field['name'] ) ) }}

		@endforeach
	{{ Form::submit('save') }}
	{{ Form::close() }}

</div>
@endsection