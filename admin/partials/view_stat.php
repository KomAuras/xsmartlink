<div class="wrap">
    <h1><?= $heading ?></h1>

    <div>
        <div style="float:left;width:300px;height:300px">
        	<canvas id="chart_posts1" height="200" width="200"></canvas>
        </div>
        <div style="float:left;width:300px;height:300px">
        	<canvas id="chart_posts2" height="200" width="200"></canvas>
        </div>
    </div>
</div>

<script>

window.chartColors = {
	red: 'rgb(255, 99, 132)',
	orange: 'rgb(255, 159, 64)',
	yellow: 'rgb(255, 205, 86)',
	green: 'rgb(75, 192, 192)',
	blue: 'rgb(54, 162, 235)',
	purple: 'rgb(153, 102, 255)',
	grey: 'rgb(201, 203, 207)'
};

var ctx = document.getElementById('chart_posts1').getContext('2d');
var chart = new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ["Доноры", "Акцепторы"],
        datasets: [{
            label: "Записи",
            data: [<?= $donors ?>, <?= $acceptors ?>],
            backgroundColor: [
				window.chartColors.green,
            	window.chartColors.red,
			],
	        borderWidth: 0,
        }],
    },
    options: {
        responsive: true,
        legend: {
            position: 'bottom',
        },
        title: {
            display: true,
            text: 'Записи'
        },
        animation: {
            animateScale: true,
            animateRotate: true
        }
    }
});

var ctx = document.getElementById('chart_posts2').getContext('2d');
var chart = new Chart(ctx, {
    type: 'doughnut',
    data: {
        labels: ["Внешних", "Локальных"],
        datasets: [{
            label: "Требуется ссылкок",
            data: [<?= $need_g_links ?>, <?= $need_l_links ?>],
            backgroundColor: [
				window.chartColors.orange,
            	window.chartColors.blue,
			],
	        borderWidth: 0,
        }]
    },
    options: {
        responsive: true,
        legend: {
            position: 'bottom',
        },
        title: {
            display: true,
            text: 'Требуется ссылкок'
        },
        animation: {
            animateScale: true,
            animateRotate: true
        }
    }
});

</script>
