@layout('layouts/default')

@section('pagetitle') Form @endsection

@section('main')
	
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

@endsection