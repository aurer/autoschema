@layout('layouts.default')

@section('main')
	<div class="cell">
		<h2>Config file converter</h2>
		{{ Form::open_for_files() }}
			<p class="field">
				{{ Form::label('file','Select an XML config file to convert...') }}
				{{ Form::file('file') }}
			</p>
			<p class="field submit">
				{{ Form::submit('Upload') }}
			</p>
		{{ Form::close() }}
		
		@if( isset($result) )
		<textarea style="width:100%;height:40em;border:none">{{ $result }}</textarea>
		@endif
	</div>
@endsection