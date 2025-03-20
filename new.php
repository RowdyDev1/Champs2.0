<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Average Handling Time Chart</title>
    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/chartjs-plugin-datalabels@2.0.0/dist/chartjs-plugin-datalabels.min.js"></script>
</head>
<body>
    <div class="container mt-5">
        <div class="row">
            <div class="col-md-12">
                <div class="card">
                    <div class="card-header">
                        <h3 class="card-title">Average Handling Time - 2024</h3>
                    </div>
                    <div class="card-body">
                        <canvas id="myLineChart"></canvas>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script>
        // Fetch data from PHP script
        fetch('fetch.php')
            .then(response => response.json())
            .then(data => {
                // Extract data for chart
                const months = data.map(item => item.Month);
                const ahtValues = data.map(item => item.AHT);

                // Create the line chart
                const ctx = document.getElementById('myLineChart').getContext('2d');
                const myLineChart = new Chart(ctx, {
                    type: 'line',
                    data: {
                        labels: months,
                        datasets: [{
                            label: 'Average Handling Time (AHT)',
                            data: ahtValues,
                            borderColor: '#36A2EB',
                            backgroundColor: 'rgba(54, 162, 235, 0.2)',
                            fill: true,
                            tension: 0.1
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                position: 'top',
                            },
                            tooltip: {
                                callbacks: {
                                    label: function(tooltipItem) {
                                        return tooltipItem.label + ': ' + tooltipItem.raw;
                                    }
                                }
                            },
                            datalabels: {
                                color: '#000', // Set text color to black
                                backgroundColor: null, // Remove background color
                                borderRadius: 4,
                                padding: 6,
                                font: {
                                    weight: 'bold',
                                    size: 14
                                },
                                formatter: (value) => {
                                    return value;
                                },
                                anchor: 'end',
                                align: 'top'
                            }
                        }
                    },
                    plugins: [ChartDataLabels] // Add this line to register the datalabels plugin
                });
            })
            .catch(error => console.error('Error fetching data:', error));
    </script>
</body>
</html>