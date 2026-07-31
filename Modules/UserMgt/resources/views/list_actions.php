<?php ?>
<div class="table-data-feature pull-left">
	<a class="item edit_expense" href="<?= base_url(route_to('edit-user', $staff->user()->id, 0)) ?>" type="button" id="edit_user" user_id="<?= $staff->user()->id ?>" data-toggle="tooltip" data-placement="top" title="Edit Staff">
		<i class="zmdi zmdi-edit"></i>
	</a>
	<!-- <button onclick="destroy('<?= '' //base_url(route_to('item-delete', base64urlEncode($staff->id))) ?>','DTTBL-BTN','hrs_reg_container')" class="item type=" button" id="delete_<?= '' //$staff->id ?>" data-toggle="tooltip" data-placement="top" title="Delete Staff"> -->
	<button onclick="('#','DTTBL-BTN','hrs_reg_container')" class="item type=" button" id="delete_<?= $staff->id ?>" data-toggle="tooltip" data-placement="top" title="Delete Staff">
		<i class="zmdi zmdi-delete"></i>
	</button>
</div>