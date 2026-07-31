<?= $this->extend('layouts/master') ?>
<?= $this->section('body-contents') ?>
<!-- BREADCRUMB-->
<section>
	<div class="row">
		<div class="col-lg-12" style="padding-right: 0; padding-left: 0;">
			<div class="au-breadcrumb-content">
				<div class="au-breadcrumb-left">
					<nav class="breadcrumb" style="margin-bottom: .5rem">
						<a class="breadcrumb-item" href="<?= base_url(route_to('home-page')) ?>"><i class="fa fa-home"></i>&nbsp;Home</a>
						<a class="breadcrumb-item" href="<?= base_url(route_to('human-resources-index')) ?>">&nbsp;Human Resources</a>
						<a class="breadcrumb-item" href="<?= base_url(route_to('registration-index')) ?>">&nbsp;Registration</a>
						<span class="breadcrumb-item active">&nbsp;<?= strtoupper($staff->name) ?></span>
					</nav>
				</div>
			</div>
		</div>
	</div>
</section>
<!-- END BREADCRUMB-->
<div class="row">
	<div class="col-lg-12" style="padding-left: 0; padding-right: 0">
		<div class="card">
			<div class="card-header">
				<h4>About Me</h4>
			</div>
			<div class="card-body">
				<div class="col-lg-12">
					<div class="row form-wrap" id="hrs_staff_profile" staff_id="<?= $staff->id ?>" user_id="<?= $user->id ?>" style="padding: 0;">

						<div class="col-lg-12" style="margin-bottom: 1.3rem">
							<div class="componentWraper">
								<p class="componentTitle">About Me</p>
								<div class="row col-md-12 col-sm-12" style="padding: 0; margin: 3px;">
									<div class="col-md-1 col-sm-12" style="padding: 0; margin: 3px;">
										<div class="text-center" style="margin-bottom: .5rem">
											<img class="img-fluid" src="<?= base_url('images/icon/avatar-01.jpg') ?>" alt="User profile picture">
										</div>
										<div>
											<a title="Edit About Me" href="<?= base_url(route_to('edit-user', $staff->user()->id, 1)) ?>" class="btn btn-primary btn-block" style="width: inherit; height: 2rem"><b>Edit</b></a>
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
										<div class="row form-group" style="margin-bottom: 0.2rem;">
											<div class="col col-md-1">
												<strong><i class="fas fa-envelope"></i></strong>
											</div>
											<div class="col-12 col-md-10">
												<span><?= $staff->getEmail() ?></span>
											</div>
										</div>
									</div>

									<div class="col-md-4 col-sm-12" style="padding: 0; margin: 3px;">
										<div class="row form-group" style="margin-bottom: 0.2rem;">
											<div class="col col-md-1">
												<strong><i class="fas fa-globe-africa"></i></strong>
											</div>
											<div class="col-12 col-md-10">
												<span><?= $staff->getWebsite() ?></span>
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
						</div>

						<div class="col-lg-12">
							<ul class="nav nav-tabs" id="purchase-o-sale-nav-tabs" role="tablist">
								<li class="nav-item">
									<a class="nav-link" id="pills-access-control-tab" data-toggle="pill" href="#pills-access-control" role="tab" aria-controls="pills-access-control" aria-selected="false">Access Controls</a>
								</li>
								<li class="nav-item">
									<a class="nav-link" id="pills-authorized-approval-tab" data-toggle="pill" href="#pills-authorized-approval" role="tab" aria-controls="pills-authorized-approval" aria-selected="false">Authorized Approvals</a>
								</li>
								<li class="nav-item">
									<a class="nav-link active show" id="pills-contracts-tab" data-toggle="pill" href="#pills-contracts" role="tab" aria-controls="pills-contracts" aria-selected="true">Contracts</a>
								</li>
								<li class="nav-item">
									<a class="nav-link" id="pills-salary-adjustments-tab" data-toggle="pill" href="#pills-salary-adjustments" role="tab" aria-controls="pills-salary-adjustments" aria-selected="true">Salary Adjustmnets</a>
								</li>
								<li class="nav-item">
									<a class="nav-link" id="pills-loans-tab" data-toggle="pill" href="#pills-loans" role="tab" aria-controls="pills-loans" aria-selected="false">Loans</a>
								</li>
							</ul>

							<div class="tab-content" id="myTabContent">
								<div class="tab-pane" id="pills-access-control" style="min-height: 450px;" role="tabpanel" aria-labelledby="pills-access-control-tab">
									<div class="table-responsive table--no-card m-b-30" id="access_control_container">
									</div>
								</div>
								<div class="tab-pane" id="pills-authorized-approval" style="min-height: 450px;" role="tabpanel" aria-labelledby="pills-authorized-approval-tab">
									<?= view('human_resources/registration/staff/profile/authorized_approvals/index') ?>
								</div>
								<div class="tab-pane active" id="pills-contracts" style="height: 450px;" role="tabpanel" aria-labelledby="pills-contracts-tab">
									<?= view('human_resources/registration/staff/profile/contracts/index') ?>
								</div>
								<div class="tab-pane" id="pills-salary-adjustments" style="height: 450px;" role="tabpanel" aria-labelledby="pills-salary-adjustments-tab">
									<?= view('human_resources/registration/staff/profile/adjustments/index') ?>
								</div>
								<div class="tab-pane" id="pills-loans" style="min-height: 450px;" role="tabpanel" aria-labelledby="pills-loans-tab">

								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
<?= $this->endSection() ?>