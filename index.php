<!DOCTYPE html>
<html>
<head>
	<title>Docker Monitor</title>
	<meta charset="utf-8" />
	<meta http-equiv="X-UA-Compatible" content="IE=edge">
	<meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
	<link rel="stylesheet" href="bootstrap/css/bootstrap.min.css" />
	<link rel="stylesheet" href="font-awesome/css/font-awesome.min.css" />
	<script type="text/javascript" src="jquery/jquery-3.2.1.min.js"></script>
	<script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
	<style type="text/css">
		.center-wrapper {
			display: block;
			width: 100%;
			max-width: 1700px;
			margin-left: auto;
			margin-right: auto;
		}
		main {
			padding-bottom: 100px;
		}
		h1.display-4 {
			font-size: 25px;
			border-bottom: 1px solid #eee;
			padding-bottom: 8px;
			margin-bottom: 20px;
			margin-top: 30px;
		}
		.card.card-container {
			display: inline-block;
			margin-right: 10px;
			margin-bottom: 10px;
		}
		.card.card-container .card-block {
			padding: 10px;
		}
		.card.card-container .card-title {
			text-align: center;
		}
	</style>
	<script type="text/javascript">
		var containers = [];

		function drawContainerStatisticsCard(container, card, statAttrib, color) {
			color = color || "blue";

			var rawData = [['Time', statAttrib]];
			$.each(container.Statistics, function(i, stat) {
				rawData.push([ stat.Timestamp, stat[statAttrib] ]);
			});

			var data = google.visualization.arrayToDataTable(rawData);
			var chart = new google.visualization.LineChart(card.find(".chart")[0]);
			chart.draw(data, {
				legend: { position: 'none' },
				hAxis: { textPosition: 'none' },
				chartArea: { width: '80%', height: '90%' },
				colors: [color],
			});
		}

		function drawContainerStatisticsCards(container) {
			var cards = [
				{ card: $('#cpu-usage-' + container.Id), statAttrib: 'CPUUsagePerc', color: 'blue' },
				{ card: $('#mem-usage-' + container.Id), statAttrib: 'MemUsagePerc', color: 'green' },
			];

			$.each(cards, function(i, card) {
				drawContainerStatisticsCard(container, card.card, card.statAttrib, card.color);
			});
		}

		function pollContainers() {
			$.getJSON("poll.php", function(data) {
				// Merge new data into existing container array
				$.each(data, function(icontainer, container) {

					for (var i = 0; i < containers.length; ++i) {
						if (containers[i].Id == container.Id) {
							var newContainer = JSON.parse(JSON.stringify(container));
							containers[i].Statistics.push(newContainer.Statistics);
							newContainer.Statistics = containers[i].Statistics;
							containers[i] = newContainer;
							drawContainerStatisticsCards(containers[i]);
							return;
						}
					}

					// Not found - add a new container
					container.Statistics = [ container.Statistics ];
					containers.push(container);

					// Add cards
					var cpuUsageCard = $('#card-container-template').clone(true);
					cpuUsageCard.attr('id', 'cpu-usage-' + container.Id);
					cpuUsageCard.find('.card-title').text(container.Name);
					cpuUsageCard.appendTo($('section#cpu-usage'));

					var memUsageCard = $('#card-container-template').clone(true);
					memUsageCard.attr('id', 'mem-usage-' + container.Id);
					memUsageCard.find('.card-title').text(container.Name);
					memUsageCard.appendTo($('section#mem-usage'));

					drawContainerStatisticsCards(container);
				});

				window.setTimeout(pollContainers, 5000);
			});
		}

		google.charts.load('current', {'packages':['corechart']});
		google.charts.setOnLoadCallback(pollContainers);
	</script>
</head>
<body>
	<nav class="navbar navbar-toggleable-md navbar-light bg-faded">
		<button class="navbar-toggler navbar-toggler-right" type="button" data-toggle="collapse" data-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
			<span class="navbar-toggler-icon"></span>
		</button>
		<a class="navbar-brand" href="#">Docker Monitor</a>

		<div class="collapse navbar-collapse" id="navbarSupportedContent">
			<ul class="navbar-nav mr-auto">
				<li class="nav-item active"><a class="nav-link" href="#"><i class="fa fa-dashboard"></i> Dashboard</a></li>
			</ul>
		</div>
	</nav>
	<br />
	<div class="container center-wrapper">
		<main>
			<div style="display: none">
				<div class="card card-container" style="width: 300px" id="card-container-template">
					<div class="card-block">
						<h6 class="card-title">?Name?</h6>
						<div class="card-text">
							<div class="chart" style="width: 100%; height: 200px;"></div>
						</div>
					</div>
				</div>
			</div>

			<section id="cpu-usage">
				<h1 class="display-4" style="margin-top: 0px;">CPU Usage</h1>
			</section>

			<section id="mem-usage">
				<h1 class="display-4">Memory Usage</h1>
			</section>
		</main>
	</div>
</body>
</html>
