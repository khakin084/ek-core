@php $edit = isset($item_group); @endphp

<style>
	#main_modal {
		width: 40%;
		left: 30%;
	}
</style>

<form name="item-group-reg-form" action="{{ route('item-group-store') }}" method="POST" class="item-group-form" autocomplete="off">
	@csrf
	<div class="modal-header">
		<h5 class="modal-title" id="item_group_form_label">{{ $edit ? 'Edit Item Group' : 'Add Item Group' }}</h5>
		<button type="button" class="close" data-dismiss="modal" aria-label="Close">
			<span aria-hidden="true">&times;</span>
		</button>
	</div>
	<div class="modal-body">
		<div class="col-lg-12">
			<div class="card-body card-block">
				<div class="row form-group" style="margin-bottom: 0.2rem;">
					<div class="col col-md-3">
						<label for="name" class="form-control-label">Name:</label>
					</div>
					<div class="col col-md-9">
						<input type="text" name="name" placeholder="Item Group Name" class="form-control" value="{{ $edit ? $item_group->name : '' }}">
						<input type="hidden" name="id" value="{{ $edit ? $item_group->id : '' }}">
					</div>
				</div>
				<div class="row form-group" style="margin-bottom: 0.2rem;">
					<div class="col col-md-3">
						<label for="code" class="form-control-label">Code:</label>
					</div>
					<div class="col col-md-9">
						<input type="text" name="code" placeholder="Item Group Code" class="form-control" value="{{ $edit ? $item_group->code : '' }}">
					</div>
				</div>
				<div class="row form-group" style="margin-bottom: 0.2rem;">
					<div class="col col-md-3">
						<label for="descriptions" class="form-control-label">Descriptions:</label>
					</div>
					<div class="col col-md-9">
						<textarea name="descriptions" rows="3" placeholder="Any descriptions..?" class="form-control">{{ $edit ? $item_group->descriptions : '' }}</textarea>
					</div>
				</div>
			</div>
		</div>
	</div>
	<div class="modal-footer">
		<button type="submit" class="btn btn-primary btn-sm submit_item_group">
			<i class="fa fa-save"></i> Submit
		</button>
	</div>
</form>
