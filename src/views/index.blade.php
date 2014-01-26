@extends('autoschema::layouts.master')

@section('main')
	<h2 class="cell">Tables</h2>

	<table class="layout autoschema">
		@foreach($tables as $table)
			<tr class="{{ $table->error_type }}">
				<th class="name">{{ $table->name }}</th>
				@if( $table->valid )
					<td colspan="2" class="action">
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

	@if( count($views) > 0 )
		<h2 class="cell">Views</h2>
		<table class="layout autoschema">
			@foreach( $views as $view )
				<tr>
					<th class="name">{{ $view->name }}</th>
					@if( $view->valid )
						<td colspan="2" class="action">
							<a class="btn" href="/autoschema/update_view/{{ $view->name }}">Refresh</a>
						</td>
					@else
						<td class="details">
							<h4>Errors</h4>
							{{ HTML::ol($view->errors) }}
						</td>
						<td class="action">
							@if( $view->error_type == 'missing_from_database' )
								<a class="btn btn-green" href="/autoschema/create_view/{{ $view->name }}">Create</a>
							@elseif( $view->error_type == 'missing_from_definition' )
								<a class="btn btn-red" href="/autoschema/drop_view/{{ $view->name }}">Drop</a>
							@else
								<a class="btn" href="/autoschema/update_view{{ $view->name }}">Update</a>
							@endif	
						</td>
					@endif
				</tr>
			@endforeach
		</table>
	@endif

@stop