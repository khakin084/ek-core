<!DOCTYPE html>
<html lang="en">

<head>
	<!-- Required meta tags-->
	<meta charset="UTF-8">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<meta name="description" content="erpkin-kahkin traders">
	<meta name="author" content="Sinnerman084">
	<meta name="keywords" content="erpkin">
	<meta name="csrf-token" content="{{ csrf_token() }}" />

	<!-- Title Page-->
	<title>EK</title>
	<link rel="icon" href="{{ asset('images/icon/favicon.ico') }}">

	<!-- Fontfaces CSS-->
	<link href="{{ asset('css/font-face.css') }}" rel="stylesheet" media="all">
	<link href="{{ asset('vendor/font-awesome-4.7/css/font-awesome.min.css') }}" rel="stylesheet" media="all">
	<link href="{{ asset('vendor/font-awesome-5/css/all.min.css') }}" rel="stylesheet" media="all">
	<link href="{{ asset('vendor/mdi-font/css/material-design-iconic-font.min.css') }}" rel="stylesheet" media="all">

	<!-- Multselect CSS-->
	<link href="{{ asset('css/jquery.multiselect.css') }}" rel="stylesheet" media="all">

	<!-- Spin CSS-->
	<link href="{{ asset('css/spin.css') }}" rel="stylesheet" media="all">

	<!-- Bootstrap CSS-->
	<link href="{{ asset('vendor/bootstrap-4.1/bootstrap.min.css') }}" rel="stylesheet" media="all">

	<!-- Vendor CSS-->
	<link href="{{ asset('vendor/dragtable/dragtable.min.css') }}" rel="stylesheet" media="all">
	<link href="{{ asset('vendor/animsition/animsition.min.css') }}" rel="stylesheet" media="all">
	<link href="{{ asset('vendor/bootstrap-progressbar/bootstrap-progressbar-3.3.4.min.css') }}" rel="stylesheet" media="all">
	<link href="{{ asset('vendor/wow/animate.css') }}" rel="stylesheet" media="all">
	<link href="{{ asset('vendor/css-hamburgers/hamburgers.min.css') }}" rel="stylesheet" media="all">
	<link href="{{ asset('vendor/slick/slick.css') }}" rel="stylesheet" media="all">
	<link href="{{ asset('vendor/select2/select2.min.css') }}" rel="stylesheet" media="all">
	<link href="{{ asset('vendor/perfect-scrollbar/perfect-scrollbar.css') }}" rel="stylesheet" media="all">

	<!-- SweetAlert2 -->
	<link href="{{ asset('plugins/sweetalert2-theme-bootstrap-4/bootstrap-4.min.css') }}" rel="stylesheet" type="text/css" media="all">

	<!-- Main CSS-->
	<link href="{{ asset('css/theme.css') }}" rel="stylesheet" media="all">

	<!-- daterange picker -->
	<link href="{{ asset('plugins/daterangepicker/daterangepicker.css') }}" rel="stylesheet" media="all">

	<!-- Other CSS-->
	<link href="{{ asset('plugins/dataTables/datatables.css') }}" rel="stylesheet" type="text/css" media="all">
	<link href="{{ asset('plugins/dataTables/Buttons-1.6.1/css/buttons.dataTables.min.css') }}" rel="stylesheet" type="text/css" media="all">
	<link href="{{ asset('plugins/dataTables/FixedHeader-3.1.6/css/fixedHeader.dataTables.css') }}" rel="stylesheet" type="text/css" media="all">
	<link href="{{ asset('plugins/dataTables/Responsive-2.2.3/css/responsive.dataTables.min.css') }}" rel="stylesheet" type="text/css" media="all">
	<link href="{{ asset('plugins/dataTables/Scroller-2.0.1/css/scroller.dataTables.min.css') }}" rel="stylesheet" type="text/css" media="all">
	<link href="{{ asset('plugins/dataTables/DataTables-1.10.20/css/dataTables.jqueryui.min.css') }}" rel="stylesheet" type="text/css" media="all">
	<link href="{{ asset('plugins/datepicker/datepicker3.css') }}" rel="stylesheet" type="text/css" media="all">
	<link href="{{ asset('plugins/datetimepicker/datetimepicker.css') }}" rel="stylesheet" type="text/css" media="all">
	<link href="{{ asset('js/toastr.min.css') }}" rel="stylesheet" type="text/css" media="all">

	<!--Custom CSS-->
	<link href="{{ asset('css/main.css') }}" rel="stylesheet" media="all">

	<script defer src="{{ asset('vendor/font-awesome-5/js/all.js') }}"></script>
</head>

@php
$user = authUser();
@endphp

<body class="animsition">
	<div class="page-wrapper">
		<div id="spinner_div"></div>
		<!-- HEADER DESKTOP-->
		<header class="header-desktop3 d-none d-lg-block">
			<div class="section__content section__content--p35">
				<div class="header3-wrap">
					<div class="header__logo">
						<a class="logo" href="{{ route('home-page') }}">
							<img src="{{ asset('images/icon/logo-horizontal-white.png') }}" alt="CoolAdmin" />
						</a>
					</div>
					<div class="header__tool">
						<div class="header-button-item has-noti js-item-menu">
							<i class="zmdi zmdi-notifications"></i>
							<div class="notifi-dropdown notifi-dropdown--no-bor js-dropdown">
								<div class="notifi__title">
									<p>You have 3 Notifications</p>
								</div>
								<div class="notifi__item">
									<div class="bg-c1 img-cir img-40">
										<i class="zmdi zmdi-email-open"></i>
									</div>
									<div class="content">
										<p>You got a email notification</p>
										<span class="date">April 12, 2018 06:50</span>
									</div>
								</div>
								<div class="notifi__item">
									<div class="bg-c2 img-cir img-40">
										<i class="zmdi zmdi-account-box"></i>
									</div>
									<div class="content">
										<p>Your account has been blocked</p>
										<span class="date">April 12, 2018 06:50</span>
									</div>
								</div>
								<div class="notifi__item">
									<div class="bg-c3 img-cir img-40">
										<i class="zmdi zmdi-file-text"></i>
									</div>
									<div class="content">
										<p>You got a new file</p>
										<span class="date">April 12, 2018 06:50</span>
									</div>
								</div>
								<div class="notifi__footer">
									<a href="#">All notifications</a>
								</div>
							</div>
						</div>
						<div class="account-wrap">
							<div class="account-item account-item--style2 clearfix js-item-menu">
								<div class="image">
									<img src="{{ asset('images/icon/avatar-01.jpg') }}" alt="John Doe" />
								</div>
								<div class="content">
									<a class="js-acc-btn" href="#">{{ $user->full_name }}</a>
								</div>
								<div class="account-dropdown js-dropdown">
									<div class="info clearfix">
										<div class="image">
											<a href="#">
												<img src="{{ asset('images/icon/avatar-01.jpg') }}" alt="John Doe" />
											</a>
										</div>
										<div class="content">
											<h5 class="name">
												<a href="#">{{ $user->full_name }}</a>
											</h5>
											<span class="email">{{ $user->email }}</span>
											<span class="email">{{ $user?->phone }}</span>
										</div>
									</div>
									<div class="account-dropdown__body">
										<div class="account-dropdown__item">
											<a href="#">
												<i class="zmdi zmdi-account"></i>Account</a>
										</div>
									</div>
									<div class="account-dropdown__footer">
										<a href="{{ route('logout') }}">
											<i class="zmdi zmdi-power"></i>Logout</a>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</header>
		<!-- END HEADER DESKTOP-->

		<!-- HEADER MOBILE-->
		<header class="header-mobile header-mobile-2 d-block d-lg-none">
			<div class="header-mobile__bar">
				<div class="container-fluid">
					<div class="header-mobile-inner">
						<a class="logo" href="{{ route('home-page') }}">
							<img src="{{ asset('images/icon/logo-horizontal-white.png') }}" alt="CoolAdmin" />
						</a>
						<button class="hamburger hamburger--slider" type="button">
							<span class="hamburger-box">
								<span class="hamburger-inner"></span>
							</span>
						</button>
					</div>
				</div>
			</div>
		</header>
		<div class="sub-header-mobile-2 d-block d-lg-none">
			<div class="header__tool">
				<div class="header-button-item has-noti js-item-menu">
					<i class="zmdi zmdi-notifications"></i>
					<div class="notifi-dropdown notifi-dropdown--no-bor js-dropdown">
						<div class="notifi__title">
							<p>You have 3 Notifications</p>
						</div>
						<div class="notifi__item">
							<div class="bg-c1 img-cir img-40">
								<i class="zmdi zmdi-email-open"></i>
							</div>
							<div class="content">
								<p>You got a email notification</p>
								<span class="date">April 12, 2018 06:50</span>
							</div>
						</div>
						<div class="notifi__item">
							<div class="bg-c2 img-cir img-40">
								<i class="zmdi zmdi-account-box"></i>
							</div>
							<div class="content">
								<p>Your account has been blocked</p>
								<span class="date">April 12, 2018 06:50</span>
							</div>
						</div>
						<div class="notifi__item">
							<div class="bg-c3 img-cir img-40">
								<i class="zmdi zmdi-file-text"></i>
							</div>
							<div class="content">
								<p>You got a new file</p>
								<span class="date">April 12, 2018 06:50</span>
							</div>
						</div>
						<div class="notifi__footer">
							<a href="#">All notifications</a>
						</div>
					</div>
				</div>
				<div class="account-wrap">
					<div class="account-item account-item--style2 clearfix js-item-menu">
						<div class="image">
							<img src="{{ asset('images/icon/avatar-01.jpg') }}" alt="John Doe" />
						</div>
						<div class="content">
							<a class="js-acc-btn" href="#">{{ $user->full_name }}</a>
						</div>
						<div class="account-dropdown js-dropdown">
							<div class="info clearfix">
								<div class="image">
									<a href="#">
										<img src="{{ asset('images/icon/avatar-01.jpg') }}" alt="John Doe" />
									</a>
								</div>
								<div class="content">
									<h5 class="name">
										<a href="#">{{ $user->full_name }}</a>
									</h5>
									<span class="email">{{ $user->email }}</span>
									<span class="email">{{ $user?->phone }}</span>
								</div>
							</div>
							<div class="account-dropdown__body">
								<div class="account-dropdown__item">
									<a href="#">
										<i class="zmdi zmdi-account"></i>Account</a>
								</div>
							</div>
							<div class="account-dropdown__footer">
								<a href="{{ route('logout') }}">
									<i class="zmdi zmdi-power"></i>Logout</a>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
		<!-- END HEADER MOBILE -->
		<!-- MAIN MODAL -->
		<div id="main_modal" role="dialog" class="modal fade">
			<div class="modal-dialog modal-lg">
				<div class="modal-content"></div>
			</div>
		</div>
		<!-- END MAIN MODAL -->
		<!-- PAGE CONTAINER-->
		<div>
			<!-- MAIN CONTENT-->
			<div class="main-content">
				<div class="section__content section__content--p30">
					<div class="container-fluid" style="padding-left: 5px; padding-right: 5px">

						@yield('body-contents')

						<div class="row">
							<div class="col-md-12">
								<div style="text-align: right">
									<p>Copyright © {{ date('Y') }} KahkinTraders. All rights reserved.</p>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
			<!-- END MAIN CONTENT-->
			<!-- END PAGE CONTAINER-->
		</div>
	</div>
	<script>
		let base_url = "{{ url('/') }}";
	</script>
	<!-- Jquery JS-->
	<script src="{{ asset('vendor/jquery-3.6.x.min.js') }}"></script>

	<!---DataTable---->
	<script src="{{ asset('plugins/dataTables/datatables.js') }}" type="text/javascript" charset="utf8"></script>
	<script src="{{ asset('plugins/dataTables/Buttons-1.6.1/js/dataTables.buttons.min.js') }}" type="text/javascript"></script>
	<script src="{{ asset('plugins/dataTables/Scroller-2.0.1/js/scroller.dataTables.js') }}" type="text/javascript"></script>

	<!-- Bootstrap JS-->
	<script src="{{ asset('vendor/bootstrap-4.1/popper.min.js') }}"></script>
	<script src="{{ asset('vendor/bootstrap-4.1/bootstrap.min.js') }}"></script>

	<!-- Jquery Additional plugins JS-->
	<script src="{{ asset('vendor/jquery-ui.min.js') }}" type="text/javascript"></script>
	<script src="{{ asset('vendor/dragtable/jquery.dragtable.js') }}" type="text/javascript"></script>
	<script src="{{ asset('js/jquery.multiselect.js') }}"></script>

	<!--- Other CSS --->
	<script src="{{ asset('vendor/select2/select2.min.js') }}"></script>
	<script src="{{ asset('plugins/price_format.js') }}"></script>
	<script src="{{ asset('plugins/datepicker/bootstrap-datepicker.js') }}"></script>
	<script src="{{ asset('plugins/datetimepicker/datetimepicker.js') }}"></script>


	<!-- Vendor JS       -->
	<script src="{{ asset('vendor/slick/slick.min.js') }}"></script>
	<script src="{{ asset('vendor/wow/wow.min.js') }}"></script>
	<script src="{{ asset('vendor/animsition/animsition.min.js') }}"></script>
	<script src="{{ asset('vendor/bootstrap-progressbar/bootstrap-progressbar.min.js') }}"></script>
	<script src="{{ asset('vendor/counter-up/jquery.waypoints.min.js') }}"></script>
	<script src="{{ asset('vendor/counter-up/jquery.counterup.min.js') }}"></script>
	<script src="{{ asset('vendor/circle-progress/circle-progress.min.js') }}"></script>
	<script src="{{ asset('vendor/perfect-scrollbar/perfect-scrollbar.js') }}"></script>
	<script src="{{ asset('vendor/chartjs/Chart.bundle.min.js') }}"></script>
	<script src="{{ asset('js/TweenMax-1.18.0.js') }}"></script>
	<script src="{{ asset('js/Draggable-0.14.1.js') }}"></script>

	<!-- SweetAlert2 -->
	<script src="{{ asset('plugins/sweetalert2/sweetalert2.min.js') }}"></script>

	<!-- Price Format -->
	<script src="{{ asset('plugins/price_format.js') }}"></script>

	<!-- date-range-picker -->
	<script src="{{ asset('plugins/moment/moment.min.js') }}"></script>
	<script src="{{ asset('plugins/inputmask/min/jquery.inputmask.bundle.min.js') }}"></script>
	<script src="{{ asset('plugins/daterangepicker/daterangepicker.js') }}"></script>

	<!-- Main JS-->
	<script src="{{ asset('js/toastr.min.js') }}"></script>
	<script src="{{ asset('js/spin.min.js') }}" type="module"></script>
	<script src="{{ asset('js/main.js') }}"></script>
	<script src="{{ asset('js/image_viewer.js') }}"></script>
	<!-- core JS-->
	<script type="text/javascript">
		$.ajaxSetup({
			headers: {
				'X-CSRF-TOKEN': $('meta[name="csrf-token"]').attr('content')
			}
		});
	</script>
	<script src="{{ asset('js/core.js') }}"></script>
	<script src="{{ asset('js/tab-tables.js') }}"></script>

	<!-- Modal JS-->
	<script src="{{ asset('js/modal_scripts.js') }}"></script>

	<script src='https://cdn.jsdelivr.net/npm/jsbarcode@3.8.0/dist/barcodes/JsBarcode.code128.min.js'></script>
	<script src='https://cdn.jsdelivr.net/npm/davidshimjs-qrcodejs@0.0.2/qrcode.min.js'></script>

	@yield('scripts')
</body>

</html>
<!-- end document-->
