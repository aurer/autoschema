@layout('layouts/default')

@section('pagetitle') Tables @endsection

@section('main')
	
	<div class="autoschema">
		<ul class="tables">
		@foreach($tables as $table)
			<li class="valid-{{ $table['valid'] ? 'true' : 'false' }} {{ $table['error'] }}">
				<h3>{{ $table['name'] }}</h3>
				@if( $table['error'] == 'missing_from_database')
					<h4>Errors</h4>
					<ol><li>This table is not in the database yet </li></ol>
					<div class="action">
						<a href="{{ URI::current() }}/create/{{ $table['name'] }}">Create</a>
					</div>
				@endif
				@if( $table['error'] == 'missing_from_definition')
					<h4>Errors</h4>
					<ol><li>This table is not in the schema definition</li></ol>
					<div class="action">
						<a href="{{ URI::current() }}/drop/{{ $table['name'] }}">Drop</a>
					</div>
				@endif
				@if( count($table['schema_errors']) )
					<h4>Errors</h4>
					<ol>
						@foreach( $table['schema_errors'] as $error)
							<li>{{ $error }}</li>
						@endforeach
					</ol>
					<div class="action">
						<a href="{{ URI::current() }}/update/{{ $table['name'] }}">Update database</a>
					</div>
				@endif
			</li>
		@endforeach
		</ul>
	</div>

@endsection