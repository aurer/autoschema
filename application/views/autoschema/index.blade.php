@layout('layouts/default')

@section('pagetitle') Tables @endsection

@section('main')

	<h3 class="inner"><a href="/">&larr; Back</a></h3>
	
	<table class="layout autoschema">
		@foreach($tables as $table)
			<tr class="{{ $table->error_type }}">
				<th>{{ $table->name }}</th>
				@if( $table->valid )
					<td class="details"></td>
					<td class="action">
						<a class="btn" href="{{ URI::current() }}/update/{{ $table->name }}">Refresh</a>
					</td>
				@else
					<td class="details">
						<h4>Errors</h4>
						{{ HTML::ol($table->errors) }}
					</td>
					<td class="action">
						@if( $table->error_type == 'missing_from_database' )
							<a class="btn btn-green" href="{{ URI::current() }}/create/{{ $table->name }}">Create</a>
						@elseif( $table->error_type == 'missing_from_definition' )
							<a class="btn btn-red" href="{{ URI::current() }}/drop/{{ $table->name }}">Drop</a>
						@else
							<a class="btn" href="{{ URI::current() }}/update/{{ $table->name }}">Update</a>
						@endif	
					</td>
				@endif
			</tr>
		@endforeach
	</table>

@endsection