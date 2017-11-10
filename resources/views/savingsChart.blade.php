{{Html::style('css/chart.css')}}
<html>
<head>
    <!--Load the AJAX API-->
    <script type="text/javascript" src="https://www.gstatic.com/charts/loader.js"></script>
    <script type="text/javascript">

        // Load the Visualization API and the corechart package.
        google.charts.load('current', {'packages':['corechart', 'line']});

        // Set a callback to run when the Google Visualization API is loaded.
        google.charts.setOnLoadCallback(drawChart);

        // Callback that creates and populates a data table,
        // instantiates the pie chart, passes in the data and
        // draws it.
        function drawChart() {

            // Create the data table.
            var data = new google.visualization.DataTable();
            var dateFormatter = new google.visualization.DateFormat({formatType: 'short'});

            data.addColumn('date', 'epoch');
            data.addColumn('number', 'value');
            data.addColumn({type: 'string', role: 'tooltip'});
            data.addColumn('number', 'savings');


            var jsonData = {!! $data !!};
            var keys = Object.keys(jsonData);
            for(var i=0;i<keys.length;i++){
                var key = keys[i];
                var bitcoinSaved = +jsonData[key].bitcoin_saved.toFixed(5);
                var savingsGain = +((jsonData[key].savings_gain - 1 ) * 100).toFixed(2);
                var totalSaved = jsonData[key].total_saved;
                var bitcoinValue = jsonData[key].bitcoin_value;
                var toolTip = bitcoinSaved + " BTC\n" + savingsGain + "%";
                var formattedDate = new Date(key*1000); // need to convert to milliseconds first
                data.addRow([formattedDate, bitcoinValue, toolTip, totalSaved]);
                dateFormatter.format(data, 1);
            }

            // Set chart options
            var options = {
                'title': 'MyBitcoinSaver Historical Savings Gain Calculator',
                hAxis: {
                    title: 'Time (Weeks)',
                    gridlines: {
                        color: 'none'
                    }
                },
                vAxis: {
                    title: 'Value (NZD)',
                    gridlines: {
                        color: 'none'
                    }
                }
            };

            // Instantiate and draw our chart, passing in some options.
            var chart = new google.visualization.LineChart(document.getElementById('chart_div'));
            chart.draw(data, options);
        }
    </script>
</head>

<body>
<div class="title">MyBitcoinSaver Historical Savings Gains Calculator</div>
<br>
<div>
    {{ Form::open(['route' => ['updateChart']]) }}
    What if I saved
    {{ Form::select('amount', array('10' => '10', '20' => '20', '50' => '50', '100' => '100', '200' => '200')) }}
    per week for
    {{ Form::select('months', range(1,60)) }}
    months
    {{ Form::submit('Submit') }}
    {{ Form::close() }}
</div>
<!--Div that will hold the pie chart-->
<div id="chart_div"></div>
<br>
<div>
    <div class="boxed">
        <?php
            $encodedJson = json_decode($data);
            $finalDatapoint =  end($encodedJson);
            echo round($finalDatapoint->bitcoin_saved,5) . "<br>Bitcoin Saved";
        ?>
    </div>
    <div class="boxed">
        <?php
            echo round(($finalDatapoint->savings_gain - 1) * 100,2) . "%<br>Savings Gain";
        ?>

    </div>
    <div class="boxed">
        <?php
            echo "$".round($finalDatapoint->total_saved,2) . "<br>Total Saved";
        ?>
    </div>
    <div class="boxed">
        <?php
            echo "$".round($finalDatapoint->bitcoin_value,2) . "<br>Value of Bitcoin";
        ?>
    </div>
</div>
</body>
</html>