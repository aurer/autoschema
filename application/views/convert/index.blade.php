@layout('layouts/default')

@section('main')
	
	<div class="cell">
		{{ Form::open_for_files() }}
			<div class="field">
				{{ Form::label('file','Select an XML config file...') }}
				{{ Form::file('file') }}
			</div>
			<div class="field submit">
				{{ Form::submit('Upload') }}
			</div>
		{{ Form::close() }}
		
		@if( isset($result) )
		<textarea style="width:100%;height:40em;border:none">{{ $result }}</textarea>
		@endif

	</div>

@endsection