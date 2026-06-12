@extends('layouts.master')
@section('body-contents')
	<style>
		.link:link,
		.link:visited {
			transition: all .1s ease-in;
		}

		.link {
			text-decoration: none;
			transition: all .1s ease-in;
		}

		.custom-menu-link {
			justify-content: center;
			display: flex;
			align-items: stretch;
			flex-direction: column;
			border-radius: .25rem;
			color: inherit;
			text-align: center;
			width: 6.455rem;

		}

		.custom-menu-link-lower-span {
			justify-content: center;
			display: flex;
			align-items: stretch;
			flex-direction: column;
			border-radius: .25rem;
			font-size: .8rem;
			padding-left: .2rem;
			padding-right: .2rem;
			background-color: rgba(0, 0, 0, 0.05);
			font-weight: bold;
			flex-basis: auto;
			flex-shrink: 0;
			flex-grow: 1;
			width: 6.05rem;

		}

		.pa3 {
			padding: 1rem .2rem;
			width: 6.05rem;
		}

		.pv2 {
			padding-top: .5rem;
			padding-bottom: .5rem;
		}

		.items-center {
			align-items: center;
		}

		.br--bottom {
			border-top-left-radius: 0;
			border-top-right-radius: 0;
		}

		*,
		*::before,
		*::after {
			box-sizing: inherit;
		}

		.cust-ul {
			height: 500px;
			width: 600px;
			display: flex;
			flex-direction: column;
			flex-wrap: wrap;
			list-style-type: none;
		}

		ul.cust-ul li {
			padding-bottom: 1.5rem;
			padding-right: 1.5rem;
			width: 6.8rem;
		}

		.menu-box {
			transition: background .15s, color .15s, box-shadow .15s;
		}

		.menu-box:hover {
			color: #fff;
			box-shadow: 5px 4px 13px -2px rgb(22, 12, 30);
		}
	</style>
	<!-- BREADCRUMB-->
	<section class="au-breadcrumb2">
		<div class="container">
			<div class="row">
				<div class="col-lg-12">
					<div class="au-breadcrumb-content">
						<div class="au-breadcrumb-left">
							<nav class="breadcrumb">
								<span class="breadcrumb-item active"><i class="fa fa-home"></i>&nbsp;Home</span>
							</nav>
						</div>
						<form class="au-form-icon--sm" action="" method="post">
							<input class="au-input--w300 au-input--style2" type="text" placeholder="Search for datas &amp; reports...">
							<button class="au-btn--submit2" type="submit">
								<i class="zmdi zmdi-search"></i>
							</button>
						</form>
					</div>
				</div>
			</div>
		</div>
	</section>
	<!-- END BREADCRUMB-->
	<div style="width: 770px; height: 100%; margin: auto" class="home-main-div">
		<ul class="cust-ul">
			<li><a href="#" class="custom-menu-link link menu-box">
					<span class="pa3"><i class="far fa-handshake fa-2x"></i></span>
					<span class="pv2 custom-menu-link-lower-span br--bottom items-center">Stakeholders</span>
				</a></li>
			<li><a href="#" class="custom-menu-link link menu-box">
					<span class="pa3"><i class="fas fa-shopping-basket fa-2x"></i></span>
					<span class="pv2 custom-menu-link-lower-span br--bottom items-center">Purchases</span>
				</a></li>
			<li><a href="{{ route('catalogs') }}" class="custom-menu-link link menu-box">
					<span class="pa3"><i class="fas fa-boxes fa-2x"></i></span>
					<span class="pv2 custom-menu-link-lower-span br--bottom items-center">Item Master</span>
				</a></li>
			<li><a href="#" class="custom-menu-link link menu-box">
					<span class="pa3"><i class="fas fa-truck-moving fa-2x"></i></span>
					<span class="pv2 custom-menu-link-lower-span br--bottom items-center">Fleet</span>
				</a></li>
			<li><a href="#" class="custom-menu-link link menu-box">
					<span class="pa3"><i class="fas fa-warehouse fa-2x"></i></span>
					<span class="pv2 custom-menu-link-lower-span br--bottom items-center">Warehouses</span>
				</a></li>
			<li><a href="#" class="custom-menu-link link menu-box">
					<span class="pa3"><i class="fas fa-project-diagram fa-2x"></i></span>
					<span class="pv2 custom-menu-link-lower-span br--bottom items-center">Projects</span>
				</a></li>
			<li><a href="#" class="custom-menu-link link menu-box">
					<span class="pa3"><i class="fas fa-tasks fa-2x"></i></span>
					<span class="pv2 custom-menu-link-lower-span br--bottom items-center">To Do List</span>
				</a></li>
			<li><a href="#" class="custom-menu-link link menu-box">
					<span class="pa3"><i class="fas fa-industry fa-2x"></i></span>
					<span class="pv2 custom-menu-link-lower-span br--bottom items-center">Production</span>
				</a></li>
			<li><a href="#" class="custom-menu-link link menu-box">
					<span class="pa3"><i class="fas fa-dolly-flatbed fa-2x"></i></span>
					<span class="pv2 custom-menu-link-lower-span br--bottom items-center">Sales</span>
				</a></li>
			<li><a href="#" class="custom-menu-link link menu-box">
					<span class="pa3"><i class="fas fa-file-invoice fa-2x"></i></span>
					<span class="pv2 custom-menu-link-lower-span br--bottom items-center">Invoices</span>
				</a></li>
			<li><a href="#" class="custom-menu-link link menu-box">
					<span class="pa3"><i class="fas fa-people-carry fa-2x"></i></span>
					<span class="pv2 custom-menu-link-lower-span br--bottom items-center">HR</span>
				</a></li>
			<li><a href="#" class="custom-menu-link link menu-box">
					<span class="pa3"><i class="fas fa-calculator fa-2x"></i></span>
					<span class="pv2 custom-menu-link-lower-span br--bottom items-center">Accounts</span>
				</a></li>
			<li><a href="#" class="custom-menu-link link menu-box">
					<span class="pa3"><i class="fas fa-barcode fa-2x"></i></span>
					<span class="pv2 custom-menu-link-lower-span br--bottom items-center">Assets</span>
				</a></li>
			<li><a href="#" class="custom-menu-link link menu-box">
					<span class="pa3"><i class="fas fa-flag-checkered fa-2x"></i></span>
					<span class="pv2 custom-menu-link-lower-span br--bottom items-center">Reports</span>
				</a></li>
			<li><a href="#" class="custom-menu-link link menu-box">
					<span class="pa3"><i class="fas fa-truck-loading fa-2x"></i></span>
					<span class="pv2 custom-menu-link-lower-span br--bottom items-center">Loadings</span>
				</a></li>
			<li><a href="#" class="custom-menu-link link menu-box">
					<span class="pa3"><i class="fas fa-coins fa-2x"></i></span>
					<span class="pv2 custom-menu-link-lower-span br--bottom items-center">Expenses</span>
				</a></li>
			<li><a href="#" class="custom-menu-link link menu-box">
					<span class="pa3"><i class="fas fa-calendar-check fa-2x"></i></span>
					<span class="pv2 custom-menu-link-lower-span br--bottom items-center">Approvals</span>
				</a></li>
			<li><a href="#" class="custom-menu-link link menu-box">
					<span class="pa3"><i class="fas fa-tools fa-2x"></i></span>
					<span class="pv2 custom-menu-link-lower-span br--bottom items-center">Settings</span>
				</a></li>
		</ul>
	</div>
@endsection
@section('scripts')
	<script>
		$(function() {
			const faintColors = ["#E3FAFC", "#F4FCE3", "#FFF4E6", "#F8F0FC", "#F1F3F5", "#A6E5EE", "#A9D3F6", "#FFF9DB"];
			const shadow = "5px 4px 13px -2px rgb(22,12,30)";
			let lastPick = null;

			function pickFaintColor() {
				if (faintColors.length === 1) return faintColors[0];

				let color;
				do {
					color = faintColors[Math.floor(Math.random() * faintColors.length)];
				} while (color === lastPick);

				lastPick = color;
				return color;
			}

			function randomStrongColor() {
				return "#" + Math.floor(Math.random() * 0xffffff).toString(16).padStart(6, "0").toUpperCase();
			}

			// Set initial faint bg for links
			$(".home-main-div a").each(function() {
				this.style.background = pickFaintColor();
			});

			// Event delegation: one handler for all current/future .menu-box
			$(document)
				.on("mouseenter", ".menu-box", function() {
					this.style.background = randomStrongColor();
					this.style.color = "#FFFFFF";
					this.style.boxShadow = shadow;
				})
				.on("mouseleave", ".menu-box", function() {
					this.style.background = pickFaintColor();
					this.style.color = "#666666";
					this.style.boxShadow = "none";
				});
		});
	</script>
@endsection
