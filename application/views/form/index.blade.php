@layout('layouts/default')

@section('pagetitle') Form @endsection

@section('main')
	<h3><a href="/">&larr; Back</a></h3>

	{{ Form::open(null, 'get') }}
		{{ Form::select('table', array('bills'=>'Bill','users'=>'Users','emails'=>'Emails','pages'=>'Pages',)) }}
		{{ Form::submit('Update') }}
	{{ Form::close() }}
	
	{{ Form::open() }}
	@foreach($fields as $field)

		<p class="field type-{{ $field['type'] }}">
			<div class="label">
				{{ Form::label('in_'.$field['name'], $field['label']) }}
			</div>
			<span class="input">
				@if( $field['type'] != 'select' )
				
					{{ Form::input($field['type'], $field['name']) }}
				
				@elseif( isset($field['formtype']) && is_array($field['values']) )
				
					@foreach( $field['values'] as $key=>$val )
				
						@if( $field['formtype'] == 'radio' )
				
							{{ Form::radio($field['name'], $key, null, array('id'=>$field['name'].$key)) }}
							{{ Form::label($field['name'].$key, $val) }}
				
						@else
				
							{{ Form::checkbox($key, $val, null, array('id'=>$field['name'].$key)) }}
							{{ Form::label($field['name'].$key, $val) }}
				
						@endif
				
					@endforeach
				
				@else
					{{ Form::select($field['name'], $field['values']) }}
				
				@endif
			</span>
		</p>

	@endforeach
	{{ Form::submit('save') }}
	{{ Form::close() }}

@endsection