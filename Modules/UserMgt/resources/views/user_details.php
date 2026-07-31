<div class="componentWraper">
	<p class="componentTitle">Personal Details</p>
	<div class="row col-md-12 col-sm-12" style="padding: 0; margin: 3px;">
		<div class="col-md-1 col-sm-12" style="padding: 0; margin: 3px;">
			<div class="text-center">
				<img class="img-fluid" src="<?= base_url('images/icon/avatar-01.jpg') ?>" alt="User profile picture">
			</div>
		</div>

		<div class="col-md-1 col-sm-12" style="padding: 0; margin: 3px;">
		</div>

		<div class="col-md-4 col-sm-12" style="padding: 0; margin: 3px;">
			<h3 class="profile-username" style="font-family: 'Open Sans', sans-serif;"><?= $staff->fullName() ?></h3>
			<p class="text-muted">Software Engineer</p>
			<div class="row form-group" style="margin-bottom: 0.2rem;">
				<div class="col col-md-1">
					<strong><i class="fas fa-phone-square"></i></strong>
				</div>
				<div class="col-12 col-md-10">
					<span><?= $staff->getPhone() ?></span>
				</div>
			</div>
		</div>

		<div class="col-md-4 col-sm-12" style="padding: 0; margin: 3px;">
			<div class="row form-group" style="margin-bottom: 0.2rem;">
				<div class="col col-md-1">
					<strong><i class="fas fa-envelope"></i></strong>
				</div>
				<div class="col-12 col-md-10">
					<span><?= $staff->getEmail() ?></span>
				</div>
			</div>
			<div class="row form-group" style="margin-bottom: 0.2rem;">
				<div class="col col-md-1">
					<strong><i class="fas fa-map-marker-alt"></i></strong>
				</div>
				<div class="col-12 col-md-10">
					<span><?= nl2br($staff->getAddress()) ?></span>
				</div>
			</div>
		</div>
	</div>
</div>