@layout('layouts/default')

@section('main')
	<h3 class="inner"><a href="/">&larr; Back</a></h3>
	<h2>Tables</h2>
	<table class="layout autoschema">
		@foreach($tables as $table)
			<tr class="{{ $table->error_type }}">
				<th>{{ $table->name }}</th>
				@if( $table->valid )
					<td class="details"></td>
					<td class="action">
						<a class="btn" href="/autoschema/update_table/{{ $table->name }}">Refresh</a>
					</td>
				@else
					<td class="details">
						<h4>Errors</h4>
						{{ HTML::ol($table->errors) }}
					</td>
					<td class="action">
						@if( $table->error_type == 'missing_from_database' )
							<a class="btn btn-green" href="/autoschema/create_table/{{ $table->name }}">Create</a>
						@elseif( $table->error_type == 'missing_from_definition' )
							<a class="btn btn-red" href="/autoschema/drop_table/{{ $table->name }}">Drop</a>
						@else
							<a class="btn" href="/autoschema/update_table/{{ $table->name }}">Update</a>
						@endif	
					</td>
				@endif
			</tr>
		@endforeach
	</table>

	<h2>Views</h2>
	<table class="layout autoschema">
		@foreach( $views as $view )
			<tr>
				<th>{{ $view }}</th>
			</tr>
		@endforeach
	</table>

@endsection